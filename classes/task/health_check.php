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

namespace local_wsmanager\task;

use local_wsmanager\schema\manager;
use local_wsmanager\schema\yaml_parser;
use local_wsmanager\automation\user_manager;
use local_wsmanager\automation\service_manager;
use local_wsmanager\automation\token_manager;
use local_wsmanager\automation\capability_calculator;
use local_wsmanager\notification\manager as notification_manager;

/**
 * Scheduled task for health checks
 *
 * @package    local_wsmanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class health_check extends \core\task\scheduled_task {

    /**
     * Get task name
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('healthcheck_task', 'local_wsmanager');
    }

    /**
     * Execute the task
     */
    public function execute(): void {
        $schemamanager = new manager();
        $schemas = $schemamanager->get_all_schemas();

        $issues = [];

        // Collect issues and healthy schemas.
        $healthyschemas = [];
        foreach ($schemas as $schema) {
            if (!$schema->enabled) {
                continue;
            }

            $result = $this->check_schema($schema);

            // Update schema status.
            $schemamanager->update_status($schema->id, $result['status']);

            // Log the check.
            $this->log_check($schema->id, $result);

            // Collect issues.
            if ($result['status'] !== 'healthy') {
                $issues[] = [
                    'schema' => $schema,
                    'result' => $result,
                ];
            } else {
                $healthyschemas[] = $schema;
            }
        }

        // Send notifications based on configuration.
        $notifylevel = get_config('local_wsmanager', 'notification_level');
        $shouldnotify = !empty($issues);

        // If no issues, but level is 'all', notify anyway.
        if (empty($issues) && $notifylevel === 'all') {
            $shouldnotify = true;
        }

        if ($shouldnotify) {
            $notifier = new notification_manager();
            $notifier->send_daily_report($issues, $healthyschemas);
        }

        mtrace('Service Schema Health Check completed. Checked ' . count($schemas) .
            ' schemas, found ' . count($issues) . ' with issues.');
    }

    /**
     * Check a single schema's health
     *
     * @param \stdClass $schema Schema record
     * @return array ['status' => string, 'message' => string, 'details' => array]
     */
    protected function check_schema(\stdClass $schema): array {
        $parser = new yaml_parser();
        $usermanager = new user_manager();
        $servicemanager = new service_manager();
        $tokenmanager = new token_manager();
        $capcalc = new capability_calculator();

        $details = [];
        $status = 'healthy';

        // Parse YAML to get functions.
        try {
            $yamldata = $parser->parse($schema->yaml_content);
            $functions = $parser->extract_functions($yamldata);
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Failed to parse YAML: ' . $e->getMessage(),
                'details' => [],
            ];
        }

        // Check functions exist.
        $functioncheck = $this->check_functions_exist($functions, $capcalc);
        $details['functions'] = $functioncheck;
        if ($functioncheck['critical_missing'] > 0) {
            $status = 'critical';
        } elseif ($functioncheck['noncritical_missing'] > 0 && $status === 'healthy') {
            $status = 'warning';
        }

        // Check user exists and is not deleted/suspended.
        if ($schema->userid) {
            $usercheck = $this->check_user_valid($schema->userid, $usermanager);
            $details['user'] = $usercheck;
            if (!$usercheck['valid']) {
                $status = 'critical';
            } elseif ($usercheck['suspended'] && $status !== 'critical') {
                $status = 'warning';
            }
        } else {
            $details['user'] = ['valid' => false, 'error' => 'No user ID'];
            $status = 'critical';
        }

        // Check service exists and is enabled.
        if ($schema->serviceid) {
            $servicecheck = $this->check_service_valid($schema->serviceid, $servicemanager);
            $details['service'] = $servicecheck;
            if (!$servicecheck['exists']) {
                $status = 'critical';
            } elseif (!$servicecheck['enabled'] && $status !== 'critical') {
                $status = 'warning';
            }
        } else {
            $details['service'] = ['exists' => false, 'error' => 'No service ID'];
            $status = 'critical';
        }

        // Check token is valid.
        if ($schema->tokenid) {
            $tokencheck = $this->check_token_valid($schema->tokenid, $tokenmanager);
            $details['token'] = $tokencheck;
            if (!$tokencheck['exists']) {
                $status = 'critical';
            } elseif (!$tokencheck['valid'] && $status !== 'critical') {
                $status = 'warning';
            }
        } else {
            $details['token'] = ['exists' => false, 'info' => 'No token generated'];
            // No token is not critical, just informational.
        }

        // Build summary message.
        $message = $this->build_status_message($status, $details);

        return [
            'status' => $status,
            'message' => $message,
            'details' => $details,
        ];
    }

    /**
     * Check if functions exist
     *
     * @param array $functions Functions to check
     * @param capability_calculator $capcalc Calculator instance
     * @return array
     */
    protected function check_functions_exist(array $functions, capability_calculator $capcalc): array {
        $criticalMissing = 0;
        $noncriticalMissing = 0;
        $missing = [];

        foreach ($functions as $func) {
            $functionname = is_array($func) ? $func['name'] : $func;
            $critical = is_array($func) ? ($func['critical'] ?? true) : true;

            if (!$capcalc->function_exists($functionname)) {
                $missing[] = $functionname;
                if ($critical) {
                    $criticalMissing++;
                } else {
                    $noncriticalMissing++;
                }
            }
        }

        return [
            'total' => count($functions),
            'critical_missing' => $criticalMissing,
            'noncritical_missing' => $noncriticalMissing,
            'missing' => $missing,
        ];
    }

    /**
     * Check if user is valid
     *
     * @param int $userid User ID
     * @param user_manager $usermanager Manager instance
     * @return array
     */
    protected function check_user_valid(int $userid, user_manager $usermanager): array {
        $exists = $usermanager->user_exists($userid);
        if (!$exists) {
            return ['valid' => false, 'error' => 'User deleted'];
        }

        $suspended = $usermanager->is_suspended($userid);
        return [
            'valid' => true,
            'exists' => true,
            'suspended' => $suspended,
        ];
    }

    /**
     * Check if service is valid
     *
     * @param int $serviceid Service ID
     * @param service_manager $servicemanager Manager instance
     * @return array
     */
    protected function check_service_valid(int $serviceid, service_manager $servicemanager): array {
        $exists = $servicemanager->service_exists($serviceid);
        if (!$exists) {
            return ['exists' => false, 'error' => 'Service deleted'];
        }

        $enabled = $servicemanager->is_enabled($serviceid);
        return [
            'exists' => true,
            'enabled' => $enabled,
        ];
    }

    /**
     * Check if token is valid
     *
     * @param int $tokenid Token ID
     * @param token_manager $tokenmanager Manager instance
     * @return array
     */
    protected function check_token_valid(int $tokenid, token_manager $tokenmanager): array {
        $exists = $tokenmanager->token_exists($tokenid);
        if (!$exists) {
            return ['exists' => false, 'error' => 'Token deleted'];
        }

        $valid = $tokenmanager->is_token_valid($tokenid);
        $lastaccess = $tokenmanager->get_last_access($tokenid);

        return [
            'exists' => true,
            'valid' => $valid,
            'last_access' => $lastaccess,
        ];
    }

    /**
     * Build status message
     *
     * @param string $status Status
     * @param array $details Details
     * @return string
     */
    protected function build_status_message(string $status, array $details): string {
        $messages = [];

        if (!empty($details['functions']['missing'])) {
            $messages[] = 'Missing functions: ' . implode(', ', $details['functions']['missing']);
        }

        if (isset($details['user']) && !$details['user']['valid']) {
            $messages[] = 'User issue: ' . ($details['user']['error'] ?? 'unknown');
        } elseif (isset($details['user']['suspended']) && $details['user']['suspended']) {
            $messages[] = 'User is suspended';
        }

        if (isset($details['service']) && !$details['service']['exists']) {
            $messages[] = 'Service issue: ' . ($details['service']['error'] ?? 'unknown');
        } elseif (isset($details['service']['enabled']) && !$details['service']['enabled']) {
            $messages[] = 'Service is disabled';
        }

        if (isset($details['token']) && !$details['token']['exists'] && isset($details['token']['error'])) {
            $messages[] = 'Token issue: ' . $details['token']['error'];
        } elseif (isset($details['token']['valid']) && !$details['token']['valid']) {
            $messages[] = 'Token expired';
        }

        if (empty($messages)) {
            return 'All checks passed';
        }

        return implode('; ', $messages);
    }

    /**
     * Log health check result
     *
     * @param int $schemaid Schema ID
     * @param array $result Check result
     */
    protected function log_check(int $schemaid, array $result): void {
        global $DB;

        $record = new \stdClass();
        $record->schemaid = $schemaid;
        $record->status = $result['status'];
        $record->message = $result['message'];
        $record->details = json_encode($result['details']);
        $record->timecreated = time();

        $DB->insert_record('local_wsmanager_healthlog', $record);

        // Clean old logs (keep last 30 days).
        $cutoff = time() - (30 * 24 * 60 * 60);
        $DB->delete_records_select('local_wsmanager_healthlog',
            'schemaid = :schemaid AND timecreated < :cutoff',
            ['schemaid' => $schemaid, 'cutoff' => $cutoff]
        );
    }
}
