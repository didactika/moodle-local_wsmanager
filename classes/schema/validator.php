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

use local_servicemanager\automation\capability_calculator;

/**
 * Validator for service schema definitions
 *
 * @package    local_servicemanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class validator {
    /** @var yaml_parser */
    protected $parser;

    /** @var capability_calculator */
    protected $capcalc;

    /**
     * Constructor
     */
    public function __construct() {
        $this->parser = new yaml_parser();
        $this->capcalc = new capability_calculator();
    }

    /**
     * Validate a schema against current Moodle state
     *
     * @param array $data Parsed YAML data
     * @param int|null $excludeschemaid Schema ID to exclude from uniqueness check (for updates)
     * @return array ['errors' => [], 'warnings' => []]
     */
    public function validate(array $data, ?int $excludeschemaid = null): array {
        global $DB;

        $errors = [];
        $warnings = [];

        // Validate structure first.
        $structurerrors = $this->parser->validate_structure($data);
        if (!empty($structurerrors)) {
            return ['errors' => $structurerrors, 'warnings' => []];
        }

        $meta = $this->parser->extract_meta($data);
        $functions = $this->parser->extract_functions($data);
        $requiredplugins = $this->parser->extract_required_plugins($data);
        $additionalusers = $this->parser->extract_additional_users($data);

        // Check schema ID uniqueness.
        $existing = $DB->get_record('local_servicemanager_schemas', ['schema_id' => $meta['id']]);
        if ($existing && ($excludeschemaid === null || $existing->id != $excludeschemaid)) {
            $errors[] = get_string('error_schema_id_exists', 'local_servicemanager', $meta['id']);
        }

        // Check schema name uniqueness (role and service names are derived from meta.name and must be unique).
        $existingbyname = $DB->get_record('local_servicemanager_schemas', ['name' => $meta['name']]);
        if ($existingbyname && ($excludeschemaid === null || $existingbyname->id != $excludeschemaid)) {
            $errors[] = get_string('error_schema_name_exists', 'local_servicemanager', $meta['name']);
        }

        // Validate required plugins.
        foreach ($requiredplugins as $plugin) {
            if (!$this->plugin_exists($plugin)) {
                $warnings[] = get_string('warning_plugin_not_installed', 'local_servicemanager', $plugin);
            }
        }

        // Validate functions.
        $seenfunctions = [];
        foreach ($functions as $func) {
            $funcname = $func['name'];

            // Check for duplicates.
            if (isset($seenfunctions[$funcname])) {
                $errors[] = get_string('error_duplicate_function', 'local_servicemanager', $funcname);
                continue;
            }
            $seenfunctions[$funcname] = true;

            if (!$this->capcalc->function_exists($funcname)) {
                if ($func['critical']) {
                    $errors[] = get_string('error_critical_function_missing', 'local_servicemanager', $funcname);
                } else {
                    $warnings[] = get_string('warning_function_missing', 'local_servicemanager', $funcname);
                }
            }
        }

        // Validate additional user emails.
        foreach ($additionalusers as $email) {
            if (!$this->user_exists_by_email($email)) {
                $warnings[] = get_string('warning_user_email_not_found', 'local_servicemanager', $email);
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Check if a plugin exists
     *
     * @param string $pluginname Plugin name (e.g., 'local_campustools')
     * @return bool
     */
    protected function plugin_exists(string $pluginname): bool {
        $pluginman = \core_plugin_manager::instance();
        $parts = explode('_', $pluginname, 2);

        if (count($parts) < 2) {
            return false;
        }

        $type = $parts[0];
        $name = $parts[1];

        $plugins = $pluginman->get_plugins_of_type($type);
        return isset($plugins[$name]);
    }

    /**
     * Check if a user exists by email
     *
     * @param string $email Email address
     * @return bool
     */
    protected function user_exists_by_email(string $email): bool {
        global $DB;
        return $DB->record_exists('user', ['email' => $email, 'deleted' => 0]);
    }

    /**
     * Validate YAML content string
     *
     * @param string $content YAML content
     * @param int|null $excludeschemaid Schema ID to exclude
     * @return array ['errors' => [], 'warnings' => [], 'data' => array|null]
     */
    public function validate_content(string $content, ?int $excludeschemaid = null): array {
        try {
            $data = $this->parser->parse($content);
            if ($data === null) {
                return [
                    'errors' => [get_string('error_invalid_yaml', 'local_servicemanager', 'Empty or invalid content')],
                    'warnings' => [],
                    'data' => null,
                ];
            }

            $result = $this->validate($data, $excludeschemaid);

            // Safety net against silent parse loss: if the raw text declares
            // "extra_capabilities:" as a block (nothing after the colon) but parsing
            // yielded none, the items were likely mis-indented and dropped.
            if (
                preg_match('/^\s*extra_capabilities\s*:\s*(#.*)?$/m', $content)
                    && empty($this->parser->extract_extra_capabilities($data))
            ) {
                $result['warnings'][] = get_string('warning_extra_capabilities_empty', 'local_servicemanager');
            }

            $result['data'] = $data;
            return $result;
        } catch (\moodle_exception $e) {
            return [
                'errors' => [$e->getMessage()],
                'warnings' => [],
                'data' => null,
            ];
        }
    }
}
