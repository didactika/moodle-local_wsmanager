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

/**
 * Task to validate all schemas periodically.
 *
 * @package    local_wsmanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduled_validation_task extends \core\task\scheduled_task {
    /**
     * Get the name of the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_scheduled_validation', 'local_wsmanager');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        mtrace('Starting scheduled schema validation...');

        $schemas = $DB->get_records('local_wsmanager_schemas');
        $validator = new \local_wsmanager\schema\validator();
        $issues = 0;

        foreach ($schemas as $schema) {
            mtrace("Validating schema: {$schema->name} (ID: {$schema->id})");

            $result = $validator->validate_content($schema->yaml_content, $schema->id);

            if (!empty($result['errors'])) {
                $issues++;
                mtrace("  [ERROR] Validation failed:");
                foreach ($result['errors'] as $error) {
                    mtrace("    - $error");
                }
                // Here we could potentially send a notification to admins.
            } else if (!empty($result['warnings'])) {
                mtrace("  [WARNING] Validation passed with warnings:");
                foreach ($result['warnings'] as $warning) {
                    mtrace("    - $warning");
                }
            } else {
                mtrace("  [OK] Schema is valid.");
            }
        }

        mtrace("Validation completed. Found issues in $issues schemas.");
    }
}
