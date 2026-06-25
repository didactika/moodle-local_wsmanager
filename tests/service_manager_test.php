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

namespace local_wsmanager;

/**
 * Unit tests for service manager class.
 *
 * @package    local_wsmanager
 * @category   test
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_wsmanager\automation\service_manager
 */
final class service_manager_test extends \advanced_testcase {
    /**
     * Test creating an external service.
     */
    public function test_create_service(): void {
        global $DB;
        $this->resetAfterTest();

        $manager = new \local_wsmanager\automation\service_manager();
        $serviceid = $manager->create_external_service('test.service', 'Test Service');

        $this->assertIsInt($serviceid);
        $this->assertGreaterThan(0, $serviceid);

        // Verify service was created.
        $service = $DB->get_record('external_services', ['id' => $serviceid]);
        $this->assertNotFalse($service);
        $this->assertEquals('ws_test_service', $service->shortname);
        $this->assertEquals('Web Service - Test Service', $service->name);
        $this->assertEquals(1, $service->restrictedusers);
        $this->assertEquals(1, $service->enabled);
        $this->assertEquals(0, $service->downloadfiles);
        $this->assertEquals(0, $service->uploadfiles);
    }

    /**
     * Test checking whether a service exists.
     */
    public function test_service_exists(): void {
        $this->resetAfterTest();

        $manager = new \local_wsmanager\automation\service_manager();

        // Service doesn't exist yet.
        $this->assertFalse($manager->service_exists(0));

        // Create service.
        $serviceid = $manager->create_external_service('test.service', 'Test Service');

        // Now it should exist.
        $this->assertTrue($manager->service_exists($serviceid));
    }

    /**
     * Test adding functions to a service.
     */
    public function test_add_functions(): void {
        global $DB;
        $this->resetAfterTest();

        $manager = new \local_wsmanager\automation\service_manager();

        // Create service.
        $serviceid = $manager->create_external_service('test.service', 'Test Service');

        // Add functions that exist in Moodle core.
        $functions = ['core_webservice_get_site_info'];
        $manager->add_functions_to_service($serviceid, $functions);

        // Verify functions were added.
        $servicefunction = $DB->get_record('external_services_functions', [
            'externalserviceid' => $serviceid,
            'functionname' => 'core_webservice_get_site_info',
        ]);
        $this->assertNotFalse($servicefunction);
        $this->assertEquals(['core_webservice_get_site_info'], $manager->get_service_functions($serviceid));
    }

    /**
     * Test authorizing a user for a service.
     */
    public function test_authorize_user(): void {
        global $DB;
        $this->resetAfterTest();

        // Create a test user.
        $user = $this->getDataGenerator()->create_user();

        $manager = new \local_wsmanager\automation\service_manager();

        // Create service.
        $serviceid = $manager->create_external_service('test.service', 'Test Service');

        // Authorize user.
        $manager->authorize_user($serviceid, $user->id);

        // Verify authorization.
        $auth = $DB->get_record('external_services_users', [
            'externalserviceid' => $serviceid,
            'userid' => $user->id,
        ]);
        $this->assertNotFalse($auth);
    }

    /**
     * Test service deletion.
     */
    public function test_delete_service(): void {
        $this->resetAfterTest();

        $manager = new \local_wsmanager\automation\service_manager();

        // Create service.
        $serviceid = $manager->create_external_service('test.service', 'Test Service');
        $this->assertTrue($manager->service_exists($serviceid));

        // Delete service.
        $manager->delete_service($serviceid);

        // Service should no longer exist.
        $this->assertFalse($manager->service_exists($serviceid));
    }
}
