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
 * Unit tests for capability calculator class.
 *
 * @package    local_wsmanager
 * @category   test
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_wsmanager\automation\capability_calculator
 */
final class capability_calculator_test extends \advanced_testcase {

    /**
     * Insert a fake external function fixture for testing.
     *
     * @param string $name Function name
     * @param string $capabilities Comma-separated capability list
     */
    protected function create_function_fixture(string $name, string $capabilities): void {
        global $DB;

        $DB->insert_record('external_functions', [
            'name' => $name,
            'classname' => 'fake_external_class',
            'methodname' => 'execute',
            'classpath' => '',
            'component' => 'local_wsmanager',
            'capabilities' => $capabilities,
            'services' => '',
        ]);
    }

    /**
     * Test getting capabilities for a single known function.
     */
    public function test_get_function_capabilities(): void {
        $this->resetAfterTest();
        $this->create_function_fixture('local_wsmanager_fake_function', 'moodle/fake:cap1,moodle/fake:cap2');

        $calculator = new \local_wsmanager\automation\capability_calculator();
        $caps = $calculator->get_function_capabilities('local_wsmanager_fake_function');

        $this->assertIsArray($caps);
        $this->assertEquals(['moodle/fake:cap1', 'moodle/fake:cap2'], $caps);
    }

    /**
     * Test calculating capabilities from multiple functions.
     */
    public function test_get_capabilities_for_functions(): void {
        $this->resetAfterTest();
        $this->create_function_fixture('local_wsmanager_fake_function', 'moodle/fake:cap1');

        $calculator = new \local_wsmanager\automation\capability_calculator();

        $functions = [
            ['name' => 'local_wsmanager_fake_function', 'critical' => true],
        ];

        $caps = $calculator->get_capabilities_for_functions($functions);

        $this->assertIsArray($caps);
        $this->assertContains('moodle/fake:cap1', $caps);
    }

    /**
     * Test that get_capabilities_for_functions accepts plain string function names too.
     */
    public function test_get_capabilities_for_functions_with_string_names(): void {
        $this->resetAfterTest();
        $this->create_function_fixture('local_wsmanager_fake_function', 'moodle/fake:cap1');

        $calculator = new \local_wsmanager\automation\capability_calculator();
        $caps = $calculator->get_capabilities_for_functions(['local_wsmanager_fake_function']);

        $this->assertContains('moodle/fake:cap1', $caps);
    }

    /**
     * Test handling an unknown function.
     */
    public function test_unknown_function(): void {
        $this->resetAfterTest();

        $calculator = new \local_wsmanager\automation\capability_calculator();

        $this->assertFalse($calculator->function_exists('nonexistent_function_xyz'));
        $this->assertEmpty($calculator->get_function_capabilities('nonexistent_function_xyz'));
        $this->assertEmpty($calculator->get_capabilities_for_functions(['nonexistent_function_xyz']));
    }

    /**
     * Test deduplication of capabilities across multiple functions sharing one.
     */
    public function test_deduplicate_capabilities(): void {
        $this->resetAfterTest();
        $this->create_function_fixture('local_wsmanager_fake_function_a', 'moodle/fake:shared,moodle/fake:onlya');
        $this->create_function_fixture('local_wsmanager_fake_function_b', 'moodle/fake:shared,moodle/fake:onlyb');

        $calculator = new \local_wsmanager\automation\capability_calculator();
        $caps = $calculator->get_capabilities_for_functions([
            'local_wsmanager_fake_function_a',
            'local_wsmanager_fake_function_b',
        ]);

        // moodle/fake:shared must appear only once despite being on both functions.
        $this->assertEquals(3, count($caps));
        $this->assertContains('moodle/fake:shared', $caps);
        $this->assertContains('moodle/fake:onlya', $caps);
        $this->assertContains('moodle/fake:onlyb', $caps);
    }
}
