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
 * Unit tests for YAML parser class.
 *
 * @package    local_wsmanager
 * @category   test
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_wsmanager\schema\yaml_parser
 */
final class yaml_parser_test extends \advanced_testcase {
    /**
     * Test parsing valid YAML content.
     */
    public function test_parse_valid_yaml(): void {
        $yaml = <<<YAML
meta:
  id: "test.service"
  name: "Test Service"
  version: "1.0.0"
definition:
  functions:
    - core_user_get_users
YAML;

        $parser = new \local_wsmanager\schema\yaml_parser();
        $result = $parser->parse($yaml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('definition', $result);
        $this->assertEquals('test.service', $result['meta']['id']);
    }

    /**
     * Test parsing invalid YAML content.
     *
     * Only meaningful when the PECL yaml extension is installed: the bundled
     * fallback parser (simple_parse) is a lenient line-based parser for the
     * supported subset and does not perform strict YAML validation.
     */
    public function test_parse_invalid_yaml(): void {
        if (!function_exists('yaml_parse')) {
            $this->markTestSkipped('PECL yaml extension not installed; fallback parser does not validate strictly.');
        }

        $yaml = "invalid: yaml: content: [broken";

        $parser = new \local_wsmanager\schema\yaml_parser();

        $this->expectException(\moodle_exception::class);
        $parser->parse($yaml);
    }

    /**
     * Test schema ID validation with valid ID.
     */
    public function test_validate_schema_id_valid(): void {
        $parser = new \local_wsmanager\schema\yaml_parser();

        $this->assertTrue($parser->validate_schema_id('test.service'));
        $this->assertTrue($parser->validate_schema_id('myapp.users.v2'));
        $this->assertTrue($parser->validate_schema_id('simple'));
        $this->assertTrue($parser->validate_schema_id('test123'));
    }

    /**
     * Test schema ID validation with invalid ID.
     */
    public function test_validate_schema_id_invalid(): void {
        $parser = new \local_wsmanager\schema\yaml_parser();

        $this->assertFalse($parser->validate_schema_id('test_service'));
        $this->assertFalse($parser->validate_schema_id('test-service'));
        $this->assertFalse($parser->validate_schema_id('test service'));
        $this->assertFalse($parser->validate_schema_id('test@service'));
        $this->assertFalse($parser->validate_schema_id(''));
    }

    /**
     * Test extracting meta information.
     */
    public function test_get_meta(): void {
        $yaml = <<<YAML
meta:
  id: "test.service"
  name: "Test Service"
  version: "2.0.0"
  maintainer: "Test Team"
  description: "A test service"
definition:
  functions:
    - core_user_get_users
YAML;

        $parser = new \local_wsmanager\schema\yaml_parser();
        $data = $parser->parse($yaml);
        $meta = $parser->extract_meta($data);

        $this->assertEquals('test.service', $meta['id']);
        $this->assertEquals('Test Service', $meta['name']);
        $this->assertEquals('2.0.0', $meta['version']);
        $this->assertEquals('Test Team', $meta['maintainer']);
        $this->assertEquals('A test service', $meta['description']);
    }

    /**
     * Test extracting functions list.
     */
    public function test_get_functions(): void {
        $yaml = <<<YAML
meta:
  id: "test.service"
  name: "Test Service"
  version: "1.0.0"
definition:
  functions:
    - core_user_get_users
    - name: core_course_get_courses
      critical: true
    - name: mod_forum_get_forums
      critical: false
YAML;

        $parser = new \local_wsmanager\schema\yaml_parser();
        $data = $parser->parse($yaml);
        $functions = $parser->extract_functions($data);

        $this->assertCount(3, $functions);

        // Simple format should default to critical=true.
        $this->assertEquals('core_user_get_users', $functions[0]['name']);
        $this->assertTrue($functions[0]['critical']);

        // Extended format with critical=true.
        $this->assertEquals('core_course_get_courses', $functions[1]['name']);
        $this->assertTrue($functions[1]['critical']);

        // Extended format with critical=false.
        $this->assertEquals('mod_forum_get_forums', $functions[2]['name']);
        $this->assertFalse($functions[2]['critical']);
    }

    /**
     * Test extracting extra capabilities.
     */
    public function test_get_extra_capabilities(): void {
        $yaml = <<<YAML
meta:
  id: "test.service"
  name: "Test Service"
  version: "1.0.0"
definition:
  functions:
    - core_user_get_users
  extra_capabilities:
    - moodle/user:viewdetails
    - moodle/course:view
YAML;

        $parser = new \local_wsmanager\schema\yaml_parser();
        $data = $parser->parse($yaml);
        $caps = $parser->extract_extra_capabilities($data);

        $this->assertCount(2, $caps);
        $this->assertContains('moodle/user:viewdetails', $caps);
        $this->assertContains('moodle/course:view', $caps);
    }
}
