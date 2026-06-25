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
 * Scheduled task for log cleanup
 *
 * @package    local_servicemanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_logs extends \core\task\scheduled_task {
    /**
     * Get task name
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('cleanup_task', 'local_servicemanager');
    }

    /**
     * Execute the task
     */
    public function execute(): void {
        global $DB;

        // Check if cleanup is enabled.
        $enabled = get_config('local_servicemanager', 'cleanup_enabled');
        if (!$enabled) {
            mtrace('Service Schema log cleanup is disabled.');
            return;
        }

        // Get retention days.
        $retentiondays = get_config('local_servicemanager', 'cleanup_retention_days');
        if (!$retentiondays || $retentiondays < 1) {
            $retentiondays = 30; // Default to 30 days.
        }

        // Calculate cutoff timestamp.
        $cutoff = time() - ($retentiondays * 24 * 60 * 60);

        // Delete old health logs.
        $deleted = $DB->count_records_select('local_servicemanager_logs', 'timecreated < :cutoff', ['cutoff' => $cutoff]);
        $DB->delete_records_select('local_servicemanager_logs', 'timecreated < :cutoff', ['cutoff' => $cutoff]);

        mtrace("Service Schema log cleanup completed. Deleted {$deleted} records older than {$retentiondays} days.");
    }
}
