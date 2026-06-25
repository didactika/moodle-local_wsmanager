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

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/local/wsmanager/classes/schema/manager.php");

/**
 * External API for local_wsmanager
 *
 * @package    local_wsmanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_wsmanager_external extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_schemas_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Get all schemas
     * @return array
     */
    public static function get_schemas() {
        global $DB;

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/wsmanager:view', $context);

        $schemas = $DB->get_records('local_wsmanager_schemas');
        $result = [];

        foreach ($schemas as $schema) {
            $result[] = [
                'id' => $schema->id,
                'schemaid' => $schema->schemaid,
                'name' => $schema->name,
                'version' => $schema->version,
                'enabled' => $schema->enabled,
                'timecreated' => $schema->timecreated,
                'timemodified' => $schema->timemodified,
            ];
        }

        return $result;
    }

    /**
     * Returns description of method result value
     * @return external_multiple_structure
     */
    public static function get_schemas_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'id' => new external_value(PARAM_INT, 'Internal ID of the schema'),
                    'schemaid' => new external_value(PARAM_TEXT, 'Schema unique ID'),
                    'name' => new external_value(PARAM_TEXT, 'Schema name'),
                    'version' => new external_value(PARAM_TEXT, 'Schema version'),
                    'enabled' => new external_value(PARAM_BOOL, 'Whether the schema is enabled'),
                    'timecreated' => new external_value(PARAM_INT, 'Time created'),
                    'timemodified' => new external_value(PARAM_INT, 'Time modified'),
                ]
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_schema_parameters() {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'Internal ID of the schema'),
        ]);
    }

    /**
     * Get single schema details
     * @param int $id
     * @return array
     */
    public static function get_schema($id) {
        global $DB;

        $params = self::validate_parameters(self::get_schema_parameters(), ['id' => $id]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/wsmanager:view', $context);

        $schema = $DB->get_record('local_wsmanager_schemas', ['id' => $params['id']], '*', MUST_EXIST);

        return [
            'id' => $schema->id,
            'schemaid' => $schema->schemaid,
            'name' => $schema->name,
            'version' => $schema->version,
            'description' => $schema->description,
            'yaml_content' => $schema->yaml_content,
            'enabled' => $schema->enabled,
            'timecreated' => $schema->timecreated,
            'timemodified' => $schema->timemodified,
        ];
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function get_schema_returns() {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'Internal ID of the schema'),
                'schemaid' => new external_value(PARAM_TEXT, 'Schema unique ID'),
                'name' => new external_value(PARAM_TEXT, 'Schema name'),
                'version' => new external_value(PARAM_TEXT, 'Schema version'),
                'description' => new external_value(PARAM_RAW, 'Schema description'),
                'yaml_content' => new external_value(PARAM_RAW, 'YAML content'),
                'enabled' => new external_value(PARAM_BOOL, 'Whether the schema is enabled'),
                'timecreated' => new external_value(PARAM_INT, 'Time created'),
                'timemodified' => new external_value(PARAM_INT, 'Time modified'),
            ]
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function create_schema_parameters() {
        return new external_function_parameters([
            'yamlcontent' => new external_value(PARAM_RAW, 'YAML content of the schema'),
            'generatetoken' => new external_value(PARAM_BOOL, 'Generate token automatically', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Create new schema
     * @param string $yamlcontent
     * @param bool $generatetoken
     * @return array
     */
    public static function create_schema($yamlcontent, $generatetoken = false) {
        $params = self::validate_parameters(self::create_schema_parameters(), [
            'yamlcontent' => $yamlcontent,
            'generatetoken' => $generatetoken,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/wsmanager:manage', $context);

        $manager = new \local_wsmanager\schema\manager();

        try {
            $parsed = $manager->validate_yaml($params['yamlcontent']);
            $result = $manager->create_schema($parsed, $params['yamlcontent']);

            $token = '';
            if ($params['generatetoken']) {
                $token = $manager->generate_token($result->id);
            }

            return [
                'id' => $result->id,
                'status' => 'success',
                'message' => 'Schema created successfully',
                'token' => $token,
            ];
        } catch (Exception $e) {
            throw new moodle_exception('error', 'core', '', $e->getMessage());
        }
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function create_schema_returns() {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'ID of the created schema'),
                'status' => new external_value(PARAM_TEXT, 'Status of operation'),
                'message' => new external_value(PARAM_TEXT, 'Message'),
                'token' => new external_value(PARAM_TEXT, 'Generated token if requested', VALUE_OPTIONAL),
            ]
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function update_schema_parameters() {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'Internal ID of the schema'),
            'yamlcontent' => new external_value(PARAM_RAW, 'New YAML content'),
        ]);
    }

    /**
     * Update existing schema
     * @param int $id
     * @param string $yamlcontent
     * @return array
     */
    public static function update_schema($id, $yamlcontent) {
        $params = self::validate_parameters(self::update_schema_parameters(), [
            'id' => $id,
            'yamlcontent' => $yamlcontent,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/wsmanager:manage', $context);

        $manager = new \local_wsmanager\schema\manager();

        try {
            $manager->update_schema($params['id'], $params['yamlcontent']);

            return [
                'status' => 'success',
                'message' => 'Schema updated successfully',
            ];
        } catch (Exception $e) {
            throw new moodle_exception('error', 'core', '', $e->getMessage());
        }
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function update_schema_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_TEXT, 'Status of operation'),
                'message' => new external_value(PARAM_TEXT, 'Message'),
            ]
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function delete_schema_parameters() {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'Internal ID of the schema'),
        ]);
    }

    /**
     * Delete schema
     * @param int $id
     * @return array
     */
    public static function delete_schema($id) {
        $params = self::validate_parameters(self::delete_schema_parameters(), ['id' => $id]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/wsmanager:manage', $context);

        $manager = new \local_wsmanager\schema\manager();

        try {
            $manager->delete_schema($params['id']);

            return [
                'status' => 'success',
                'message' => 'Schema deleted successfully',
            ];
        } catch (Exception $e) {
            throw new moodle_exception('error', 'core', '', $e->getMessage());
        }
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function delete_schema_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_TEXT, 'Status of operation'),
                'message' => new external_value(PARAM_TEXT, 'Message'),
            ]
        );
    }
}
