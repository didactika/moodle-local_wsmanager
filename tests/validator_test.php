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

namespace local_servicemanager;

/**
 * Unit tests for schema validator class.
 *
 * @package    local_servicemanager
 * @category   test
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_servicemanager\schema\validator
 */
final class validator_test extends \advanced_testcase {
    /**
     * Test validating complete valid content.
     */
    public function test_validate_valid_content(): void {
        $this->resetAfterTest();

        $yaml = <<<YAML
meta:
  id: "test.service"
  name: "Test Service"
  version: "1.0.0"
definition:
  functions:
    - core_user_get_users
YAML;

        $validator = new \local_servicemanager\schema\validator();
        $result = $validator->validate_content($yaml);

        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('warnings', $result);
        // May have warnings about missing functions depending on Moodle setup.
    }

    /**
     * Test validation fails for missing meta section.
     */
    public function test_validate_missing_meta(): void {
        $this->resetAfterTest();

        $yaml = <<<YAML
definition:
  functions:
    - core_user_get_users
YAML;

        $validator = new \local_servicemanager\schema\validator();
        $result = $validator->validate_content($yaml);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('meta', $result['errors'][0]);
    }

    /**
     * Test validation fails for missing meta.id.
     */
    public function test_validate_missing_meta_id(): void {
        $this->resetAfterTest();

        $yaml = <<<YAML
meta:
  name: "Test Service"
  version: "1.0.0"
definition:
  functions:
    - core_user_get_users
YAML;

        $validator = new \local_servicemanager\schema\validator();
        $result = $validator->validate_content($yaml);

        $this->assertNotEmpty($result['errors']);
    }

    /**
     * Test validation fails for invalid schema ID.
     */
    public function test_validate_invalid_schema_id(): void {
        $this->resetAfterTest();

        $yaml = <<<YAML
meta:
  id: "test_service"
  name: "Test Service"
  version: "1.0.0"
definition:
  functions:
    - core_user_get_users
YAML;

        $validator = new \local_servicemanager\schema\validator();
        $result = $validator->validate_content($yaml);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('invalid', strtolower($result['errors'][0]));
    }

    /**
     * Test validation fails for missing definition section.
     */
    public function test_validate_missing_definition(): void {
        $this->resetAfterTest();

        $yaml = <<<YAML
meta:
  id: "test.service"
  name: "Test Service"
  version: "1.0.0"
YAML;

        $validator = new \local_servicemanager\schema\validator();
        $result = $validator->validate_content($yaml);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('definition', $result['errors'][0]);
    }

    /**
     * Test validation fails for missing functions.
     */
    public function test_validate_missing_functions(): void {
        $this->resetAfterTest();

        $yaml = <<<YAML
meta:
  id: "test.service"
  name: "Test Service"
  version: "1.0.0"
definition:
  extra_capabilities:
    - moodle/user:viewdetails
YAML;

        $validator = new \local_servicemanager\schema\validator();
        $result = $validator->validate_content($yaml);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('functions', $result['errors'][0]);
    }

    /**
     * Test duplicate schema ID detection.
     */
    public function test_validate_duplicate_schema_id(): void {
        global $DB;
        $this->resetAfterTest();

        // Insert a schema record.
        $DB->insert_record('local_servicemanager_schemas', [
            'schema_id' => 'existing.service',
            'name' => 'Existing Service',
            'version' => '1.0.0',
            'yaml_content' => 'test',
            'yaml_hash' => sha1('test'),
            'status' => 'healthy',
            'enabled' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $yaml = <<<YAML
meta:
  id: "existing.service"
  name: "Test Service"
  version: "1.0.0"
definition:
  functions:
    - core_user_get_users
YAML;

        $validator = new \local_servicemanager\schema\validator();
        $result = $validator->validate_content($yaml);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('exists', strtolower($result['errors'][0]));
    }
}
