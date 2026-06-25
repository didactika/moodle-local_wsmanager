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
 * Manager for service users
 *
 * @package    local_wsmanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_manager {
    /**
     * Create a service user following naming convention
     *
     * username: ws.{id}
     * firstname: User Webservice
     * lastname: {meta.name}
     * email: ws.{id}@devnull.{parent_domain}
     *
     * @param string $schemaid Schema ID (e.g., "crm.integration")
     * @param string $metaname Display name from meta.name
     * @return int User ID
     */
    public function create_service_user(string $schemaid, string $metaname): int {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/user/lib.php');

        // Extract domain from wwwroot, stripping any leading subdomain.
        $parsed = parse_url($CFG->wwwroot);
        $domain = $parsed['host'] ?? 'localhost';
        $parts = explode('.', $domain);
        if (count($parts) > 2) {
            $domain = implode('.', array_slice($parts, 1));
        }

        $user = new \stdClass();
        $user->username = 'ws.' . $schemaid;
        $user->firstname = 'User Webservice';
        $user->lastname = $metaname;
        $user->email = 'ws.' . $schemaid . '@devnull.' . $domain;
        $user->auth = 'webservice';
        $user->confirmed = 1;
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->timecreated = time();
        $user->timemodified = time();

        return user_create_user($user, false, false);
    }

    /**
     * Update user display name
     *
     * @param int $userid User ID
     * @param string $metaname New display name
     * @return bool
     */
    public function update_user_name(int $userid, string $metaname): bool {
        global $DB;

        $user = new \stdClass();
        $user->id = $userid;
        $user->lastname = $metaname;
        $user->timemodified = time();

        return $DB->update_record('user', $user);
    }

    /**
     * Get user for a schema
     *
     * @param int $userid User ID
     * @return \stdClass|null
     */
    public function get_user(int $userid): ?\stdClass {
        global $DB;
        return $DB->get_record('user', ['id' => $userid, 'deleted' => 0]) ?: null;
    }

    /**
     * Suspend a user
     *
     * @param int $userid User ID
     * @return bool
     */
    public function suspend_user(int $userid): bool {
        global $DB;
        return $DB->set_field('user', 'suspended', 1, ['id' => $userid]);
    }

    /**
     * Unsuspend a user
     *
     * @param int $userid User ID
     * @return bool
     */
    public function unsuspend_user(int $userid): bool {
        global $DB;
        return $DB->set_field('user', 'suspended', 0, ['id' => $userid]);
    }

    /**
     * Delete a user
     *
     * @param int $userid User ID
     * @return bool
     */
    public function delete_user(int $userid): bool {
        global $CFG;
        require_once($CFG->dirroot . '/user/lib.php');

        $user = $this->get_user($userid);
        if ($user) {
            delete_user($user);
            return true;
        }
        return false;
    }

    /**
     * Check if a user exists
     *
     * @param int $userid User ID
     * @return bool
     */
    public function user_exists(int $userid): bool {
        global $DB;
        return $DB->record_exists('user', ['id' => $userid, 'deleted' => 0]);
    }

    /**
     * Check if a user is suspended
     *
     * @param int $userid User ID
     * @return bool
     */
    public function is_suspended(int $userid): bool {
        global $DB;
        return (bool) $DB->get_field('user', 'suspended', ['id' => $userid]);
    }
}
