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
 * Unit tests for user manager class.
 *
 * @package    local_wsmanager
 * @category   test
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_wsmanager\automation\user_manager
 */
final class user_manager_test extends \advanced_testcase {

    /**
     * Test creating a service user.
     */
    public function test_create_user(): void {
        global $DB;
        $this->resetAfterTest();

        $manager = new \local_wsmanager\automation\user_manager();
        $userid = $manager->create_user('test.service', 'Test Service');

        $this->assertIsInt($userid);
        $this->assertGreaterThan(0, $userid);

        // Verify user was created with correct username pattern.
        $user = $DB->get_record('user', ['id' => $userid]);
        $this->assertNotFalse($user);
        $this->assertEquals('ws.test.service', $user->username);
        $this->assertStringStartsWith('ws.test.service@devnull.', $user->email);
        $this->assertEquals('Test Service', $user->firstname);
    }

    /**
     * Test getting existing user ID.
     */
    public function test_get_user_id(): void {
        global $DB;
        $this->resetAfterTest();

        $manager = new \local_wsmanager\automation\user_manager();

        // User doesn't exist yet.
        $this->assertNull($manager->get_user_id('test.service'));

        // Create user.
        $userid = $manager->create_user('test.service', 'Test Service');

        // Now it should return the ID.
        $this->assertEquals($userid, $manager->get_user_id('test.service'));
    }

    /**
     * Test username generation pattern.
     */
    public function test_username_pattern(): void {
        global $DB;
        $this->resetAfterTest();

        $manager = new \local_wsmanager\automation\user_manager();

        // Test with dots.
        $userid = $manager->create_user('myapp.users.v2', 'My App');
        $user = $DB->get_record('user', ['id' => $userid]);
        $this->assertEquals('ws.myapp.users.v2', $user->username);

        // Test simple ID.
        $userid2 = $manager->create_user('simple', 'Simple Service');
        $user2 = $DB->get_record('user', ['id' => $userid2]);
        $this->assertEquals('ws.simple', $user2->username);
    }

    /**
     * Test user deletion.
     */
    public function test_delete_user(): void {
        global $DB;
        $this->resetAfterTest();

        $manager = new \local_wsmanager\automation\user_manager();

        // Create user.
        $userid = $manager->create_user('test.service', 'Test Service');
        $this->assertNotFalse($DB->get_record('user', ['id' => $userid]));

        // Delete user.
        $manager->delete_user('test.service');

        // User should be deleted (marked as deleted in Moodle).
        $user = $DB->get_record('user', ['id' => $userid]);
        $this->assertEquals(1, $user->deleted);
    }
}
