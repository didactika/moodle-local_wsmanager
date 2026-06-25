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

namespace local_wsmanager\automation;

/**
 * Calculator for capabilities required by web service functions
 *
 * @package    local_wsmanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capability_calculator {
    /**
     * Get all capabilities required by a list of functions
     *
     * @param array $functions List of functions (string names or arrays with 'name' key)
     * @return array Unique capability names
     */
    public function get_capabilities_for_functions(array $functions): array {
        global $DB;

        $capabilities = [];

        foreach ($functions as $func) {
            $functionname = is_array($func) ? $func['name'] : $func;
            $function = $DB->get_record('external_functions', ['name' => $functionname]);

            if ($function && !empty($function->capabilities)) {
                $caps = explode(',', $function->capabilities);
                foreach ($caps as $cap) {
                    $cap = trim($cap);
                    if (!empty($cap)) {
                        $capabilities[$cap] = true;
                    }
                }
            }
        }

        return array_keys($capabilities);
    }

    /**
     * Check if a function exists in Moodle
     *
     * @param string $functionname Function name
     * @return bool
     */
    public function function_exists(string $functionname): bool {
        global $DB;
        return $DB->record_exists('external_functions', ['name' => $functionname]);
    }

    /**
     * Get function info
     *
     * @param string $functionname Function name
     * @return \stdClass|null
     */
    public function get_function_info(string $functionname): ?\stdClass {
        global $DB;
        return $DB->get_record('external_functions', ['name' => $functionname]) ?: null;
    }

    /**
     * Get capabilities for a single function
     *
     * @param string $functionname Function name
     * @return array Capability names
     */
    public function get_function_capabilities(string $functionname): array {
        global $DB;

        $function = $DB->get_record('external_functions', ['name' => $functionname]);
        if (!$function || empty($function->capabilities)) {
            return [];
        }

        $caps = explode(',', $function->capabilities);
        return array_map('trim', $caps);
    }

    /**
     * Validate that a capability exists in Moodle
     *
     * @param string $capability Capability name
     * @return bool
     */
    public function capability_exists(string $capability): bool {
        global $DB;
        return $DB->record_exists('capabilities', ['name' => $capability]);
    }

    /**
     * Get all capabilities that exist from a list
     *
     * @param array $capabilities Capability names to check
     * @return array Existing capability names
     */
    public function filter_existing_capabilities(array $capabilities): array {
        global $DB;

        if (empty($capabilities)) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($capabilities, SQL_PARAMS_NAMED);
        $records = $DB->get_records_select('capabilities', "name $insql", $params);

        return array_column($records, 'name');
    }
}
