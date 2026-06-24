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

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for role manager class.
 *
 * @package    local_wsmanager
 * @category   test
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_wsmanager\automation\role_manager
 */
final class role_manager_test extends \advanced_testcase {

    /**
     * Test creating a service role.
     */
    public function test_create_role(): void {
        global $DB;
        $this->resetAfterTest();

        $manager = new \local_wsmanager\automation\role_manager();
        $roleid = $manager->create_service_role('test.service', 'Test Service', 'Test description');

        $this->assertIsInt($roleid);
        $this->assertGreaterThan(0, $roleid);

        // Verify role was created with correct shortname pattern and name.
        $role = $DB->get_record('role', ['id' => $roleid]);
        $this->assertNotFalse($role);
        $this->assertEquals('ws_test_service', $role->shortname);
        $this->assertEquals('Role for Test Service', $role->name);
    }

    /**
     * Test checking whether a role exists.
     */
    public function test_role_exists(): void {
        $this->resetAfterTest();

        $manager = new \local_wsmanager\automation\role_manager();

        // Role doesn't exist yet.
        $this->assertFalse($manager->role_exists(0));

        // Create role.
        $roleid = $manager->create_service_role('test.service', 'Test Service', 'Test description');

        // Now it should exist.
        $this->assertTrue($manager->role_exists($roleid));
    }

    /**
     * Test shortname pattern conversion (dots to underscores).
     */
    public function test_shortname_pattern(): void {
        global $DB;
        $this->resetAfterTest();

        $manager = new \local_wsmanager\automation\role_manager();

        // Test with dots.
        $roleid = $manager->create_service_role('myapp.users.v2', 'My App', 'Description');
        $role = $DB->get_record('role', ['id' => $roleid]);
        $this->assertEquals('ws_myapp_users_v2', $role->shortname);
    }

    /**
     * Test assigning capabilities to a role.
     */
    public function test_assign_capabilities(): void {
        global $DB;
        $this->resetAfterTest();

        $manager = new \local_wsmanager\automation\role_manager();

        // Create role.
        $roleid = $manager->create_service_role('test.service', 'Test Service', 'Test description');

        // Assign some basic capabilities.
        $capabilities = ['moodle/user:viewalldetails', 'moodle/course:view'];
        $manager->assign_capabilities($roleid, $capabilities);

        // Verify capabilities were assigned.
        $context = \context_system::instance();
        foreach ($capabilities as $cap) {
            $assigned = $DB->get_record('role_capabilities', [
                'roleid' => $roleid,
                'capability' => $cap,
                'contextid' => $context->id,
            ]);
            $this->assertNotFalse($assigned);
            $this->assertEquals(CAP_ALLOW, $assigned->permission);
        }

        // get_role_capabilities should reflect the same set.
        $this->assertEqualsCanonicalizing($capabilities, $manager->get_role_capabilities($roleid));
    }

    /**
     * Test role deletion.
     */
    public function test_delete_role(): void {
        $this->resetAfterTest();

        $manager = new \local_wsmanager\automation\role_manager();

        // Create role.
        $roleid = $manager->create_service_role('test.service', 'Test Service', 'Test description');
        $this->assertTrue($manager->role_exists($roleid));

        // Delete role.
        $manager->delete_role($roleid);

        // Role should no longer exist.
        $this->assertFalse($manager->role_exists($roleid));
    }
}
