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

/**
 * Library functions for local_wsmanager
 *
 * @package    local_wsmanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extend navigation for the plugin.
 *
 * @param global_navigation $navigation
 */
function local_wsmanager_extend_navigation(global_navigation $navigation) {
    // Navigation is handled via settings.php for admin pages.
}

/**
 * Get the domain from Moodle's wwwroot.
 *
 * @return string The domain name
 */
function local_wsmanager_get_domain(): string {
    global $CFG;
    $parsed = parse_url($CFG->wwwroot);
    return $parsed['host'] ?? 'localhost';
}

/**
 * Convert schema ID to shortname format (dots to underscores).
 *
 * @param string $schemaid The schema ID
 * @return string The shortname format
 */
function local_wsmanager_id_to_shortname(string $schemaid): string {
    return str_replace('.', '_', $schemaid);
}

/**
 * Validate that a schema ID only contains allowed characters.
 *
 * @param string $schemaid The schema ID to validate
 * @return bool True if valid
 */
function local_wsmanager_validate_schema_id(string $schemaid): bool {
    return preg_match('/^[a-zA-Z0-9.]+$/', $schemaid) === 1;
}
