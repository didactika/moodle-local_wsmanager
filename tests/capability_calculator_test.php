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
     * Test getting capabilities for a known function.
     */
    public function test_get_capabilities_for_function(): void {
        $this->resetAfterTest();

        $calculator = new \local_wsmanager\automation\capability_calculator();

        // core_webservice_get_site_info should always exist.
        $caps = $calculator->get_capabilities_for_function('core_webservice_get_site_info');

        $this->assertIsArray($caps);
        // This function typically requires moodle/site:config or no specific caps.
    }

    /**
     * Test calculating capabilities from multiple functions.
     */
    public function test_calculate_capabilities(): void {
        $this->resetAfterTest();

        $calculator = new \local_wsmanager\automation\capability_calculator();

        $functions = [
            ['name' => 'core_webservice_get_site_info', 'critical' => true],
        ];

        $caps = $calculator->calculate_capabilities($functions);

        $this->assertIsArray($caps);
    }

    /**
     * Test merging extra capabilities.
     */
    public function test_merge_extra_capabilities(): void {
        $this->resetAfterTest();

        $calculator = new \local_wsmanager\automation\capability_calculator();

        $functions = [
            ['name' => 'core_webservice_get_site_info', 'critical' => true],
        ];
        $extra = ['moodle/user:viewdetails', 'moodle/course:view'];

        $caps = $calculator->calculate_capabilities($functions, $extra);

        $this->assertIsArray($caps);
        $this->assertContains('moodle/user:viewdetails', $caps);
        $this->assertContains('moodle/course:view', $caps);
    }

    /**
     * Test handling unknown function.
     */
    public function test_unknown_function(): void {
        $this->resetAfterTest();

        $calculator = new \local_wsmanager\automation\capability_calculator();

        $caps = $calculator->get_capabilities_for_function('nonexistent_function_xyz');

        $this->assertIsArray($caps);
        $this->assertEmpty($caps);
    }

    /**
     * Test deduplication of capabilities.
     */
    public function test_deduplicate_capabilities(): void {
        $this->resetAfterTest();

        $calculator = new \local_wsmanager\automation\capability_calculator();

        // Same capability added multiple times.
        $extra = [
            'moodle/user:viewdetails',
            'moodle/user:viewdetails',
            'moodle/course:view',
        ];

        $caps = $calculator->calculate_capabilities([], $extra);

        // Should be deduplicated.
        $this->assertEquals(2, count($caps));
    }
}
