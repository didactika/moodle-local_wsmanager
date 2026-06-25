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
 * View page for historical schema details
 *
 * @package    local_wsmanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/wsmanager:view', $context);

$historyid = required_param('historyid', PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/wsmanager/pages/view_history.php', ['historyid' => $historyid]));
$PAGE->set_title(get_string('view_schema', 'local_wsmanager'));
$PAGE->set_heading(get_string('view_schema', 'local_wsmanager'));
$PAGE->set_pagelayout('admin');

$historymanager = new \local_wsmanager\schema\history_manager();
$history = $historymanager->get_version($historyid);

if (!$history) {
    throw new moodle_exception('historynotfound', 'local_wsmanager');
}

// Parse historical YAML for display.
$parser = new \local_wsmanager\schema\yaml_parser();
$yamldata = $parser->parse($history->yaml_content);
$meta = $parser->extract_meta($yamldata);
$functions = $parser->extract_functions($yamldata);
$extracaps = $parser->extract_extra_capabilities($yamldata);

// Get calculated capabilities (based on current system state, which is fine for "what would imply").
$capcalc = new \local_wsmanager\automation\capability_calculator();

// Build functions data.
$functionsdata = [];
foreach ($functions as $func) {
    $exists = $capcalc->function_exists($func['name']);
    $functionsdata[] = [
        'name' => $func['name'],
        'critical' => $func['critical'],
        'critical_label' => $func['critical'] ? get_string('function_critical', 'local_wsmanager') : '',
        'exists' => $exists,
        'status_class' => $exists ? 'text-success' : 'text-danger',
        'status_icon' => $exists ? 'fa-check' : 'fa-times',
        'status_label' => $exists ? get_string('function_exists', 'local_wsmanager')
            : get_string('function_missing', 'local_wsmanager'),
    ];
}

// Prepare data for template.
// We reuse the schema_detail template but with reduced features (no edit/delete buttons).
$templatedata = [
    'schema_id' => $meta['id'],
    'name' => $meta['name'],
    'version' => $meta['version'],
    'description' => $meta['description'],
    'maintainer' => $meta['maintainer'],
    'status' => 'history', // Just a placeholder.
    'status_label' => get_string('version', 'local_wsmanager') . ' ' . $history->version,
    'status_class' => 'badge-secondary',
    'status_icon' => 'fa-clock-o',
    'enabled' => false, // Irrelevant for history.
    'timecreated' => userdate($history->timecreated),
    'timemodified' => userdate($history->timecreated), // History is static.
    'has_token' => false,
    'new_token' => null,
    'show_new_token' => false,
    'functions' => $functionsdata,
    'has_functions' => !empty($functionsdata),
    'extra_capabilities' => $extracaps,
    'has_extra_capabilities' => !empty($extracaps),
    'health_logs' => [], // No logs for history.
    'has_health_logs' => false,
    'can_manage' => false, // Hide management buttons.
    'is_history_view' => true, // Flag for potential template usage.
    'edit_url' => '',
    'delete_url' => '',
    'regenerate_url' => '',
    'dashboard_url' => '',
    'back_url' => (new moodle_url('/local/wsmanager/pages/history.php', ['id' => $history->schemaid]))->out(false),
];

// Start output.
echo $OUTPUT->header();

echo $OUTPUT->render_from_template('local_wsmanager/schema_detail', $templatedata);
echo $OUTPUT->footer();
