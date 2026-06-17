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

/**
 * Custom uninstall cleanup
 */
function xmldb_local_wsmanager_uninstall() {
    global $DB;

    // We need to delete all resources created by the schemas (users, roles, services).
    // The tables will be dropped automatically by Moodle after this function returns.
    
    try {
        if ($schemas = $DB->get_records('local_wsmanager_schemas')) {
            $manager = new \local_wsmanager\schema\manager();
            foreach ($schemas as $schema) {
                // Delete schema resources.
                // We use the manager's delete_schema method which handles cleaning up
                // the associated user, role, service, and tokens.
                $manager->delete_schema($schema->id);
            }
        }
    } catch (Exception $e) {
        // Log error but allow uninstall to proceed.
        debugging('Error cleaning up local_wsmanager resources: ' . $e->getMessage());
    }

    return true;
}
