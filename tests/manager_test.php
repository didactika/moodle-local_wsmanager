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
 * Unit tests for schema manager class.
 *
 * @package    local_wsmanager
 * @category   test
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_wsmanager\schema\manager
 */
final class manager_test extends \advanced_testcase {

    /**
     * Get a valid YAML content for testing.
     *
     * @return string
     */
    private function get_valid_yaml(): string {
        return <<<YAML
meta:
  id: "test.service"
  name: "Test Service"
  version: "1.0.0"
definition:
  functions:
    - core_webservice_get_site_info
YAML;
    }

    /**
     * Test creating a schema from YAML content.
     */
    public function test_create_schema(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $manager = new \local_wsmanager\schema\manager();
        $result = $manager->create_schema($this->get_valid_yaml(), true);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertNotEmpty($result['token']);

        // Verify database record.
        $schema = $DB->get_record('local_wsmanager_schemas', ['id' => $result['id']]);
        $this->assertNotFalse($schema);
        $this->assertEquals('test.service', $schema->schema_id);
        $this->assertEquals('Test Service', $schema->name);
    }

    /**
     * Test getting a schema by ID.
     */
    public function test_get_schema(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $manager = new \local_wsmanager\schema\manager();
        $result = $manager->create_schema($this->get_valid_yaml(), false);

        $schema = $manager->get_schema($result['id']);

        $this->assertNotNull($schema);
        $this->assertEquals('test.service', $schema->schema_id);
    }

    /**
     * Test getting all schemas.
     */
    public function test_get_all_schemas(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $manager = new \local_wsmanager\schema\manager();

        // Initially empty.
        $schemas = $manager->get_all_schemas();
        $initialcount = count($schemas);

        // Create a schema.
        $manager->create_schema($this->get_valid_yaml(), false);

        // Should have one more.
        $schemas = $manager->get_all_schemas();
        $this->assertCount($initialcount + 1, $schemas);
    }

    /**
     * Test updating a schema with content change (Valid).
     * Content changes -> Version MUST increment.
     */
    public function test_update_schema_valid_content_change(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $manager = new \local_wsmanager\schema\manager();
        $result = $manager->create_schema($this->get_valid_yaml(), false);

        // Add a function (content change) AND increment version.
        $updateyaml = <<<YAML
meta:
  id: "test.service"
  name: "Updated Test Service"
  version: "1.1.0"
definition:
  functions:
    - core_webservice_get_site_info
    - core_user_get_users 
YAML;

        $manager->update_schema($result['id'], $updateyaml);

        $schema = $DB->get_record('local_wsmanager_schemas', ['id' => $result['id']]);
        $this->assertEquals('Updated Test Service', $schema->name);
        $this->assertEquals('1.1.0', $schema->version);
    }

    /**
     * Test updating a schema with only metadata change (Valid).
     * Content same -> Version MUST stay same.
     */
    public function test_update_schema_valid_metadata_only(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $manager = new \local_wsmanager\schema\manager();
        $result = $manager->create_schema($this->get_valid_yaml(), false);

        // Change name only, keep version 1.0.0.
        $updateyaml = <<<YAML
meta:
  id: "test.service"
  name: "Renamed Service"
  version: "1.0.0"
definition:
  functions:
    - core_webservice_get_site_info
YAML;

        $manager->update_schema($result['id'], $updateyaml);

        $schema = $DB->get_record('local_wsmanager_schemas', ['id' => $result['id']]);
        $this->assertEquals('Renamed Service', $schema->name);
        $this->assertEquals('1.0.0', $schema->version);
    }

    /**
     * Test updating schema: Content changed but Version NOT incremented (Invalid).
     */
    public function test_update_schema_invalid_content_same_version(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $manager = new \local_wsmanager\schema\manager();
        $result = $manager->create_schema($this->get_valid_yaml(), false);

        // Add function, keep 1.0.0.
        $updateyaml = <<<YAML
meta:
  id: "test.service"
  name: "Test Service"
  version: "1.0.0"
definition:
  functions:
    - core_webservice_get_site_info
    - core_user_get_users
YAML;

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('error_version_change_required', 'local_wsmanager'));
        $manager->update_schema($result['id'], $updateyaml);
    }

    /**
     * Test updating schema: Content same but Version incremented (Invalid).
     */
    public function test_update_schema_invalid_metadata_new_version(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $manager = new \local_wsmanager\schema\manager();
        $result = $manager->create_schema($this->get_valid_yaml(), false);

        // Same content, but increment to 1.1.0.
        $updateyaml = <<<YAML
meta:
  id: "test.service"
  name: "Test Service"
  version: "1.1.0"
definition:
  functions:
    - core_webservice_get_site_info
YAML;

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('error_version_change_forbidden', 'local_wsmanager'));
        $manager->update_schema($result['id'], $updateyaml);
    }

    /**
     * Test deleting a schema.
     */
    public function test_delete_schema(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $manager = new \local_wsmanager\schema\manager();
        $result = $manager->create_schema($this->get_valid_yaml(), false);

        $this->assertNotFalse($DB->get_record('local_wsmanager_schemas', ['id' => $result['id']]));

        $manager->delete_schema($result['id']);

        $this->assertFalse($DB->get_record('local_wsmanager_schemas', ['id' => $result['id']]));
    }

    /**
     * Test enabling and disabling a schema.
     */
    public function test_enable_disable_schema(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $manager = new \local_wsmanager\schema\manager();
        $result = $manager->create_schema($this->get_valid_yaml(), false);

        // Initially enabled.
        $schema = $DB->get_record('local_wsmanager_schemas', ['id' => $result['id']]);
        $this->assertEquals(1, $schema->enabled);

        // Disable.
        $manager->set_enabled($result['id'], false);
        $schema = $DB->get_record('local_wsmanager_schemas', ['id' => $result['id']]);
        $this->assertEquals(0, $schema->enabled);

        // Re-enable.
        $manager->set_enabled($result['id'], true);
        $schema = $DB->get_record('local_wsmanager_schemas', ['id' => $result['id']]);
        $this->assertEquals(1, $schema->enabled);
    }
}
