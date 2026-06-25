<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_servicemanager\schema;

use local_servicemanager\automation\user_manager;
use local_servicemanager\automation\role_manager;
use local_servicemanager\automation\service_manager;
use local_servicemanager\automation\token_manager;
use local_servicemanager\automation\capability_calculator;

/**
 * Manager for service schema CRUD operations
 *
 * @package    local_servicemanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /** @var yaml_parser */
    protected $parser;

    /** @var validator */
    protected $validator;

    /** @var user_manager */
    protected $usermanager;

    /** @var role_manager */
    protected $rolemanager;

    /** @var service_manager */
    protected $servicemanager;

    /** @var token_manager */
    protected $tokenmanager;

    /** @var capability_calculator */
    protected $capcalc;

    /**
     * Constructor
     */
    public function __construct() {
        $this->parser = new yaml_parser();
        $this->validator = new validator();
        $this->usermanager = new user_manager();
        $this->rolemanager = new role_manager();
        $this->servicemanager = new service_manager();
        $this->tokenmanager = new token_manager();
        $this->capcalc = new capability_calculator();
    }

    /**
     * Create a new schema from YAML content
     *
     * @param string $yamlcontent YAML content
     * @param bool $generatetoken Whether to generate a token
     * @return array ['id' => int, 'token' => string|null, 'warnings' => array]
     * @throws \moodle_exception If validation fails
     */
    public function create_schema(string $yamlcontent, bool $generatetoken = false): array {
        global $DB;

        $validation = $this->validator->validate_content($yamlcontent);
        if (!empty($validation['errors'])) {
            throw new \moodle_exception(
                'error_invalid_yaml',
                'local_servicemanager',
                '',
                implode('; ', $validation['errors'])
            );
        }

        $data = $validation['data'];
        $meta = $this->parser->extract_meta($data);
        $functions = $this->parser->extract_functions($data);
        $extracaps = $this->parser->extract_extra_capabilities($data);
        $additionalusers = $this->parser->extract_additional_users($data);
        $servicesettings = $this->parser->extract_service_settings($data);

        $userid = null;
        $roleid = null;
        $serviceid = null;

        try {
            $userid = $this->usermanager->create_service_user($meta['id'], $meta['name']);

            $roleid = $this->rolemanager->create_service_role($meta['id'], $meta['name'], $meta['description']);

            $functioncaps = $this->capcalc->get_capabilities_for_functions($functions);
            $allcaps = array_unique(array_merge($functioncaps, $extracaps, ['webservice/rest:use', 'webservice/soap:use']));
            $this->rolemanager->assign_capabilities($roleid, $allcaps);

            $this->rolemanager->assign_role_to_user($roleid, $userid);

            $serviceid = $this->servicemanager->create_external_service(
                $meta['id'],
                $meta['name'],
                $servicesettings['download_files'],
                $servicesettings['upload_files']
            );

            $this->servicemanager->add_functions_to_service($serviceid, $functions);

            $this->servicemanager->authorize_user($serviceid, $userid);

            $warnings = $validation['warnings'];
            $additionalwarnings = $this->servicemanager->authorize_additional_users($serviceid, $additionalusers);
            $warnings = array_merge($warnings, $additionalwarnings);

            $tokenid = null;
            $tokenvalue = null;
            if ($generatetoken) {
                $tokenresult = $this->tokenmanager->generate_token($userid, $serviceid, $meta['name']);
                $tokenid = $tokenresult['tokenid'];
                $tokenvalue = $tokenresult['token'];
            }

            $now = time();
            $record = new \stdClass();
            $record->schema_id = $meta['id'];
            $record->name = $meta['name'];
            $record->description = $meta['description'];
            $record->version = $meta['version'];
            $record->maintainer = $meta['maintainer'];
            $record->yaml_content = $yamlcontent;
            $record->yaml_hash = $this->parser->get_hash($yamlcontent);
            $record->enabled = 1;
            $record->status = empty($warnings) ? 'healthy' : 'warning';
            $record->userid = $userid;
            $record->roleid = $roleid;
            $record->serviceid = $serviceid;
            $record->tokenid = $tokenid;
            $record->timecreated = $now;
            $record->timemodified = $now;

            $id = $DB->insert_record('local_servicemanager_schemas', $record);

            // Create initial history entry.
            $historymanager = new history_manager();
            if (!$historymanager->version_exists($id, $meta['version'])) {
                $historymanager->save_version(
                    $id,
                    $meta['version'],
                    $yamlcontent,
                    get_string('schema_created_success', 'local_servicemanager', $meta['version'])
                );
            }

            return [
                'id' => $id,
                'token' => $tokenvalue,
                'warnings' => $warnings,
            ];
        } catch (\Exception $e) {
            // Rollback any partially created resources.
            if ($serviceid) {
                $this->servicemanager->delete_service($serviceid);
            }
            if ($roleid) {
                $this->rolemanager->delete_role($roleid);
            }
            if ($userid) {
                $this->usermanager->delete_user($userid);
            }
            throw $e;
        }
    }

    /**
     * Update an existing schema
     *
     * @param int $id Schema record ID
     * @param string $yamlcontent New YAML content
     * @param bool $isrollback If true, skip version increment validation (used for rollback)
     * @return array ['warnings' => array]
     * @throws \moodle_exception If validation fails
     */
    public function update_schema(int $id, string $yamlcontent, bool $isrollback = false): array {
        global $DB;

        $existing = $this->get_schema($id);
        if (!$existing) {
            throw new \moodle_exception('Schema not found');
        }

        $validation = $this->validator->validate_content($yamlcontent, $id);
        if (!empty($validation['errors'])) {
            throw new \moodle_exception(
                'error_invalid_yaml',
                'local_servicemanager',
                '',
                implode('; ', $validation['errors'])
            );
        }

        $data = $validation['data'];
        $meta = $this->parser->extract_meta($data);
        $newhash = $this->parser->get_hash($yamlcontent);

        // ID must not change.
        if ($meta['id'] !== $existing->schema_id) {
            throw new \moodle_exception('error_id_change_forbidden', 'local_servicemanager');
        }

        // Parse existing content to compare structural changes.
        $olddata = $this->parser->parse($existing->yaml_content);

        // Prepare "effective content" (exclude meta) to check for functional changes.
        $newcontentcheck = $data;
        unset($newcontentcheck['meta']);

        $oldcontentcheck = $olddata;
        unset($oldcontentcheck['meta']);

        // Check if functional content has changed.
        // Using strict comparison might fail on key order, but our parser is consistent.
        // Serialize is safer for deep comparison.
        $contentchanged = (serialize($newcontentcheck) !== serialize($oldcontentcheck));

        if ($contentchanged) {
            // Functional content changed: Version MUST change (increment).
            if ($meta['version'] === $existing->version) {
                throw new \moodle_exception('error_version_change_required', 'local_servicemanager');
            }
            if (!$isrollback && version_compare($meta['version'], $existing->version, '<=')) {
                throw new \moodle_exception(
                    'error_version_must_increment',
                    'local_servicemanager',
                    '',
                    (object)['current' => $existing->version, 'new' => $meta['version']]
                );
            }
        } else {
            // Content did NOT change (only metadata): Version MUST NOT change.
            if ($meta['version'] !== $existing->version && !$isrollback) {
                throw new \moodle_exception('error_version_change_forbidden', 'local_servicemanager');
            }
        }

        // Save history of the NEW version.
        // This ensures the history log reflects the timeline of installed versions.
        if ($newhash !== $existing->yaml_hash || $meta['version'] !== $existing->version) {
            $historymanager = new history_manager();
            if (!$historymanager->version_exists($id, $meta['version'])) {
                $historymanager->save_version(
                    $id,
                    $meta['version'],
                    $yamlcontent,
                    get_string('schema_updated_success', 'local_servicemanager', $meta['version'])
                );
            }
        }

        $functions = $this->parser->extract_functions($data);
        $extracaps = $this->parser->extract_extra_capabilities($data);
        $additionalusers = $this->parser->extract_additional_users($data);
        $servicesettings = $this->parser->extract_service_settings($data);

        // User: update name, or recreate if deleted.
        $userid = $existing->userid;
        if (!$userid || !$this->usermanager->user_exists($userid)) {
            $userid = $this->usermanager->create_service_user($meta['id'], $meta['name']);
            $DB->set_field('local_servicemanager_schemas', 'userid', $userid, ['id' => $id]);
        } else {
            $this->usermanager->update_user_name($userid, $meta['name']);
        }

        // Role: update, or recreate if deleted.
        $roleid = $existing->roleid;
        if (!$roleid || !$this->rolemanager->role_exists($roleid)) {
            $roleid = $this->rolemanager->create_service_role($meta['id'], $meta['name'], $meta['description']);
            $DB->set_field('local_servicemanager_schemas', 'roleid', $roleid, ['id' => $id]);
            $this->rolemanager->assign_role_to_user($roleid, $userid);
        } else {
            $this->rolemanager->update_service_role($roleid, $meta['name'], $meta['description']);
        }

        $functioncaps = $this->capcalc->get_capabilities_for_functions($functions);
        $allcaps = array_unique(array_merge($functioncaps, $extracaps, ['webservice/rest:use', 'webservice/soap:use']));
        $this->rolemanager->reset_capabilities($roleid);
        $this->rolemanager->assign_capabilities($roleid, $allcaps);

        // Service: update, or recreate if deleted.
        $serviceid = $existing->serviceid;
        if (!$serviceid || !$this->servicemanager->service_exists($serviceid)) {
            $serviceid = $this->servicemanager->create_external_service(
                $meta['id'],
                $meta['name'],
                $servicesettings['download_files'],
                $servicesettings['upload_files']
            );
            $DB->set_field('local_servicemanager_schemas', 'serviceid', $serviceid, ['id' => $id]);
            $this->servicemanager->authorize_user($serviceid, $userid);

            // Reattach the existing token to the new service if it survived,
            // otherwise clear the stale tokenid reference.
            if ($existing->tokenid) {
                if ($this->tokenmanager->token_exists($existing->tokenid)) {
                    $this->tokenmanager->reattach_token($existing->tokenid, $serviceid);
                } else {
                    $DB->set_field('local_servicemanager_schemas', 'tokenid', 0, ['id' => $id]);
                }
            }
        } else {
            $this->servicemanager->update_external_service(
                $serviceid,
                $meta['name'],
                $servicesettings['download_files'],
                $servicesettings['upload_files']
            );
        }
        $this->servicemanager->reset_functions($serviceid);
        $this->servicemanager->add_functions_to_service($serviceid, $functions);

        $warnings = $validation['warnings'];
        $additionalwarnings = $this->servicemanager->authorize_additional_users($serviceid, $additionalusers);
        $warnings = array_merge($warnings, $additionalwarnings);

        $record = new \stdClass();
        $record->id = $id;
        $record->name = $meta['name'];
        $record->description = $meta['description'];
        $record->version = $meta['version'];
        $record->maintainer = $meta['maintainer'];
        $record->yaml_content = $yamlcontent;
        $record->yaml_hash = $newhash;
        $record->status = empty($warnings) ? 'healthy' : 'warning';
        $record->timemodified = time();

        $DB->update_record('local_servicemanager_schemas', $record);

        return ['warnings' => $warnings];
    }

    /**
     * Delete a schema and all associated resources
     *
     * @param int $id Schema record ID
     * @return bool
     */
    public function delete_schema(int $id): bool {
        global $DB;

        $schema = $this->get_schema($id);
        if (!$schema) {
            return false;
        }

        if ($schema->tokenid) {
            $this->tokenmanager->delete_token($schema->tokenid);
        }

        if ($schema->serviceid) {
            $this->servicemanager->delete_service($schema->serviceid);
        }

        if ($schema->roleid) {
            $this->rolemanager->delete_role($schema->roleid);
        }

        if ($schema->userid) {
            $this->usermanager->delete_user($schema->userid);
        }

        $DB->delete_records('local_servicemanager_logs', ['schemaid' => $id]);
        $DB->delete_records('local_servicemanager_history', ['schemaid' => $id]);

        $DB->delete_records('local_servicemanager_schemas', ['id' => $id]);

        return true;
    }

    /**
     * Get a schema by ID
     *
     * @param int $id Schema record ID
     * @return \stdClass|null
     */
    public function get_schema(int $id): ?\stdClass {
        global $DB;
        return $DB->get_record('local_servicemanager_schemas', ['id' => $id]) ?: null;
    }

    /**
     * Get a schema by schema_id
     *
     * @param string $schemaid Schema ID (e.g., 'crm.integration')
     * @return \stdClass|null
     */
    public function get_schema_by_schema_id(string $schemaid): ?\stdClass {
        global $DB;
        return $DB->get_record('local_servicemanager_schemas', ['schema_id' => $schemaid]) ?: null;
    }

    /**
     * Get all schemas
     *
     * @return array
     */
    public function get_all_schemas(): array {
        global $DB;
        return $DB->get_records('local_servicemanager_schemas', null, 'name ASC');
    }

    /**
     * Get schemas by status
     *
     * @param string $status Status filter
     * @return array
     */
    public function get_schemas_by_status(string $status): array {
        global $DB;
        return $DB->get_records('local_servicemanager_schemas', ['status' => $status], 'name ASC');
    }

    /**
     * Update schema status
     *
     * @param int $id Schema ID
     * @param string $status New status
     * @return bool
     */
    public function update_status(int $id, string $status): bool {
        global $DB;
        return $DB->set_field('local_servicemanager_schemas', 'status', $status, ['id' => $id]);
    }

    /**
     * Toggle schema enabled state
     *
     * @param int $id Schema ID
     * @param bool $enabled New enabled state
     * @return bool
     */
    public function set_enabled(int $id, bool $enabled): bool {
        global $DB;
        $schema = $this->get_schema($id);
        if (!$schema) {
            return false;
        }

        // Also toggle the external service.
        if ($schema->serviceid) {
            $DB->set_field('external_services', 'enabled', $enabled ? 1 : 0, ['id' => $schema->serviceid]);
        }

        // Mirror enabled state on the service user.
        if ($schema->userid) {
            if ($enabled) {
                $this->usermanager->unsuspend_user($schema->userid);
            } else {
                $this->usermanager->suspend_user($schema->userid);
            }
        }

        return $DB->set_field('local_servicemanager_schemas', 'enabled', $enabled ? 1 : 0, ['id' => $id]);
    }

    /**
     * Get schemas with pagination and filters.
     *
     * @param int $page Current page (0-indexed).
     * @param int $perpage Items per page.
     * @param array $filters Optional filters: 'status', 'name', 'datefrom', 'dateto'.
     * @return array Array of schema records.
     */
    public function get_schemas_paginated(int $page = 0, int $perpage = 10, array $filters = []): array {
        global $DB;

        [$where, $params] = $this->build_filter_conditions($filters);

        // Select all from schemas, but override 'enabled' with the service's actual state.
        $sql = "SELECT s.*, es.enabled AS service_enabled
                  FROM {local_servicemanager_schemas} s
             LEFT JOIN {external_services} es ON s.serviceid = es.id";

        if ($where) {
            $sql .= " WHERE " . $where;
        }
        $sql .= " ORDER BY s.name ASC";

        $records = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

        // Normalize the enabled flag.
        foreach ($records as $record) {
            // If service exists, use its status. Otherwise fallback to schema status (shouldn't happen in healthy state).
            if (property_exists($record, 'service_enabled') && $record->service_enabled !== null) {
                // If there's a mismatch, we might want to update our local record,
                // but for display purposes, the service status is the truth.
                $record->enabled = $record->service_enabled;
            }
            unset($record->service_enabled);
        }

        return $records;
    }

    /**
     * Count schemas with filters applied.
     *
     * @param array $filters Optional filters: 'status', 'name', 'datefrom', 'dateto'.
     * @return int Total count.
     */
    public function count_schemas(array $filters = []): int {
        global $DB;

        [$where, $params] = $this->build_filter_conditions($filters);
        $sql = "SELECT COUNT(*) FROM {local_servicemanager_schemas} s";
        if ($where) {
            $sql .= " WHERE " . $where;
        }

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Build SQL WHERE conditions from filters.
     *
     * @param array $filters Filters array.
     * @return array [$whereClause, $params]
     */
    protected function build_filter_conditions(array $filters): array {
        global $DB;

        $conditions = [];
        $params = [];

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $conditions[] = 's.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['name'])) {
            $conditions[] = $DB->sql_like('s.name', ':name', false);
            $params['name'] = '%' . $DB->sql_like_escape($filters['name']) . '%';
        }

        if (!empty($filters['datefrom'])) {
            $conditions[] = 's.timecreated >= :datefrom';
            $params['datefrom'] = $filters['datefrom'];
        }

        if (!empty($filters['dateto'])) {
            // Add 1 day to include the entire end day.
            $conditions[] = 's.timecreated <= :dateto';
            $params['dateto'] = $filters['dateto'] + 86400;
        }

        $where = implode(' AND ', $conditions);
        return [$where, $params];
    }
}
