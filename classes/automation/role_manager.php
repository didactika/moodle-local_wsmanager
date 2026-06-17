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
 * Manager for service roles at system level
 *
 * @package    local_wsmanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class role_manager {

    /**
     * Create a service role at system level
     *
     * shortname: ws_{id} (dots converted to underscores)
     * name: Servicio {meta.name}
     * description: {meta.description}
     *
     * @param string $schemaid Schema ID (e.g., "crm.integration")
     * @param string $metaname Display name from meta.name
     * @param string $metadescription Description from meta.description
     * @return int Role ID
     */
    public function create_service_role(string $schemaid, string $metaname, string $metadescription): int {
        global $CFG;
        require_once($CFG->dirroot . '/lib/accesslib.php');

        // Convert dots to underscores for shortname.
        $shortname = 'ws_' . str_replace('.', '_', $schemaid);
        $name = 'Role for ' . $metaname;

        // Create role with no archetype.
        $roleid = create_role($name, $shortname, $metadescription, '');

        // Set role context to system level only.
        set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);

        return $roleid;
    }

    /**
     * Update service role name and description
     *
     * @param int $roleid Role ID
     * @param string $metaname New display name
     * @param string $metadescription New description
     * @return bool
     */
    public function update_service_role(int $roleid, string $metaname, string $metadescription): bool {
        global $DB;

        $role = new \stdClass();
        $role->id = $roleid;
        $role->name = 'Role for ' . $metaname;
        $role->description = $metadescription;

        return $DB->update_record('role', $role);
    }

    /**
     * Assign role to user at system level
     *
     * @param int $roleid Role ID
     * @param int $userid User ID
     * @return int Role assignment ID
     */
    public function assign_role_to_user(int $roleid, int $userid): int {
        $systemcontext = \context_system::instance();
        return role_assign($roleid, $userid, $systemcontext->id, 'local_wsmanager');
    }

    /**
     * Unassign role from user
     *
     * @param int $roleid Role ID
     * @param int $userid User ID
     * @return bool
     */
    public function unassign_role_from_user(int $roleid, int $userid): bool {
        $systemcontext = \context_system::instance();
        role_unassign($roleid, $userid, $systemcontext->id, 'local_wsmanager');
        return true;
    }

    /**
     * Assign capabilities to role at system level
     *
     * @param int $roleid Role ID
     * @param array $capabilities List of capability names
     * @return void
     */
    public function assign_capabilities(int $roleid, array $capabilities): void {
        $systemcontext = \context_system::instance();
        foreach ($capabilities as $cap) {
            assign_capability($cap, CAP_ALLOW, $roleid, $systemcontext->id, true);
        }
    }

    /**
     * Remove all capabilities from role (for updates)
     *
     * @param int $roleid Role ID
     * @return void
     */
    public function reset_capabilities(int $roleid): void {
        global $DB;
        $systemcontext = \context_system::instance();
        $DB->delete_records('role_capabilities', ['roleid' => $roleid, 'contextid' => $systemcontext->id]);
    }

    /**
     * Get role by ID
     *
     * @param int $roleid Role ID
     * @return \stdClass|null
     */
    public function get_role(int $roleid): ?\stdClass {
        global $DB;
        return $DB->get_record('role', ['id' => $roleid]) ?: null;
    }

    /**
     * Delete a role
     *
     * @param int $roleid Role ID
     * @return bool
     */
    public function delete_role(int $roleid): bool {
        delete_role($roleid);
        return true;
    }

    /**
     * Check if role exists
     *
     * @param int $roleid Role ID
     * @return bool
     */
    public function role_exists(int $roleid): bool {
        global $DB;
        return $DB->record_exists('role', ['id' => $roleid]);
    }

    /**
     * Get capabilities assigned to a role
     *
     * @param int $roleid Role ID
     * @return array Capability names
     */
    public function get_role_capabilities(int $roleid): array {
        global $DB;
        $systemcontext = \context_system::instance();
        $records = $DB->get_records('role_capabilities', [
            'roleid' => $roleid,
            'contextid' => $systemcontext->id,
            'permission' => CAP_ALLOW,
        ]);

        return array_column($records, 'capability');
    }
}
