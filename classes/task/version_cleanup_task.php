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

namespace local_servicemanager\task;

/**
 * Task to clean up old schema versions.
 *
 * @package    local_servicemanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class version_cleanup_task extends \core\task\scheduled_task {
    /**
     * Get the name of the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_version_cleanup', 'local_servicemanager');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        // Check if enabled.
        if (!get_config('local_servicemanager', 'version_retention_enabled')) {
            mtrace('Version retention is disabled.');
            return;
        }

        $maxversions = (int)get_config('local_servicemanager', 'version_retention_max');
        if ($maxversions <= 0) {
            mtrace('Invalid max versions setting. Skipping.');
            return;
        }

        mtrace("Running version cleanup. Maintaining max $maxversions versions per schema.");

        // Get all schemas.
        $schemas = $DB->get_records('local_servicemanager_schemas');

        foreach ($schemas as $schema) {
            mtrace("Processing schema: {$schema->name} (ID: {$schema->id})");

            // Get all history records for this schema, ordered by timecreated DESC.
            // We want to KEEP the top N records.
            $history = $DB->get_records(
                'local_servicemanager_history',
                ['schemaid' => $schema->id],
                'timecreated DESC',
                'id, timecreated'
            );

            $count = count($history);
            if ($count <= $maxversions) {
                mtrace("  - Has $count versions. No cleanup needed.");
                continue;
            }

            // Slice to get records to DELETE (skip the first $maxversions).
            $todelete = array_slice($history, $maxversions);
            $deletecount = count($todelete);

            mtrace("  - Has $count versions. Deleting $deletecount old versions.");

            $ids = array_keys($todelete);
            $DB->delete_records_list('local_servicemanager_history', 'id', $ids);

            mtrace("  - Deleted IDs: " . implode(', ', $ids));
        }

        mtrace('Version cleanup completed.');
    }
}
