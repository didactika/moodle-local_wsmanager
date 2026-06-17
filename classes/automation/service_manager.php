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
 * Manager for external services
 *
 * @package    local_wsmanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service_manager {

    /**
     * Create an external service (restricted to authorized users only)
     *
     * name: Web Service - {meta.name}
     * shortname: ws_{id} (dots converted to underscores)
     *
     * @param string $schemaid Schema ID (e.g., "crm.integration")
     * @param string $metaname Display name from meta.name
     * @return int Service ID
     */
    public function create_external_service(
        string $schemaid,
        string $metaname,
        bool $downloadfiles = false,
        bool $uploadfiles = false
    ): int {
        global $CFG;
        require_once($CFG->dirroot . '/webservice/lib.php');

        $shortname = 'ws_' . str_replace('.', '_', $schemaid);
        $name = 'Web Service - ' . $metaname;

        $service = new \stdClass();
        $service->name = $name;
        $service->shortname = $shortname;
        $service->enabled = 1;
        $service->restrictedusers = 1; // Only authorized users.
        $service->downloadfiles = (int) $downloadfiles;
        $service->uploadfiles = (int) $uploadfiles;
        $service->component = 'local_wsmanager';

        $webservicemanager = new \webservice();
        return $webservicemanager->add_external_service($service);
    }

    /**
     * Update external service name
     *
     * @param int $serviceid Service ID
     * @param string $metaname New display name
     * @return bool
     */
    public function update_external_service(
        int $serviceid,
        string $metaname,
        bool $downloadfiles = false,
        bool $uploadfiles = false
    ): bool {
        global $DB;

        $service = new \stdClass();
        $service->id = $serviceid;
        $service->name = 'Web Service - ' . $metaname;
        $service->downloadfiles = (int) $downloadfiles;
        $service->uploadfiles = (int) $uploadfiles;
        $service->timemodified = time();

        return $DB->update_record('external_services', $service);
    }

    /**
     * Add functions to service
     *
     * @param int $serviceid Service ID
     * @param array $functions Functions with 'name' key
     * @return void
     */
    public function add_functions_to_service(int $serviceid, array $functions): void {
        global $CFG;
        require_once($CFG->dirroot . '/webservice/lib.php');

        $webservicemanager = new \webservice();

        foreach ($functions as $func) {
            $functionname = is_array($func) ? $func['name'] : $func;
            if (!$webservicemanager->service_function_exists($functionname, $serviceid)) {
                $webservicemanager->add_external_function_to_service($functionname, $serviceid);
            }
        }
    }

    /**
     * Remove all functions from service (for updates)
     *
     * @param int $serviceid Service ID
     * @return void
     */
    public function reset_functions(int $serviceid): void {
        global $DB;
        $DB->delete_records('external_services_functions', ['externalserviceid' => $serviceid]);
    }

    /**
     * Authorize a user for service
     *
     * @param int $serviceid Service ID
     * @param int $userid User ID
     * @return void
     */
    public function authorize_user(int $serviceid, int $userid): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/webservice/lib.php');

        // Check if already authorized.
        if ($DB->record_exists('external_services_users', [
            'externalserviceid' => $serviceid,
            'userid' => $userid,
        ])) {
            return;
        }

        $webservicemanager = new \webservice();
        $user = new \stdClass();
        $user->externalserviceid = $serviceid;
        $user->userid = $userid;
        $webservicemanager->add_ws_authorised_user($user);
    }

    /**
     * Authorize additional users by email
     *
     * @param int $serviceid Service ID
     * @param array $emails Email addresses
     * @return array Warnings for non-existent emails
     */
    public function authorize_additional_users(int $serviceid, array $emails): array {
        global $DB;

        $warnings = [];

        foreach ($emails as $email) {
            $user = $DB->get_record('user', ['email' => $email, 'deleted' => 0]);
            if ($user) {
                $this->authorize_user($serviceid, $user->id);
            } else {
                $warnings[] = get_string('warning_user_email_not_found', 'local_wsmanager', $email);
            }
        }

        return $warnings;
    }

    /**
     * Get service by ID
     *
     * @param int $serviceid Service ID
     * @return \stdClass|null
     */
    public function get_service(int $serviceid): ?\stdClass {
        global $DB;
        return $DB->get_record('external_services', ['id' => $serviceid]) ?: null;
    }

    /**
     * Delete a service and its related data
     *
     * @param int $serviceid Service ID
     * @return void
     */
    public function delete_service(int $serviceid): void {
        global $DB;

        // Delete authorized users.
        $DB->delete_records('external_services_users', ['externalserviceid' => $serviceid]);

        // Delete service functions.
        $DB->delete_records('external_services_functions', ['externalserviceid' => $serviceid]);

        // Delete tokens for this service.
        $DB->delete_records('external_tokens', ['externalserviceid' => $serviceid]);

        // Delete the service.
        $DB->delete_records('external_services', ['id' => $serviceid]);
    }

    /**
     * Check if service exists
     *
     * @param int $serviceid Service ID
     * @return bool
     */
    public function service_exists(int $serviceid): bool {
        global $DB;
        return $DB->record_exists('external_services', ['id' => $serviceid]);
    }

    /**
     * Check if service is enabled
     *
     * @param int $serviceid Service ID
     * @return bool
     */
    public function is_enabled(int $serviceid): bool {
        global $DB;
        return (bool) $DB->get_field('external_services', 'enabled', ['id' => $serviceid]);
    }

    /**
     * Get functions assigned to a service
     *
     * @param int $serviceid Service ID
     * @return array Function names
     */
    public function get_service_functions(int $serviceid): array {
        global $DB;
        $records = $DB->get_records('external_services_functions', ['externalserviceid' => $serviceid]);
        return array_column($records, 'functionname');
    }
}
