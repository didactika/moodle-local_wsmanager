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
 * Compare versions page for schemas.
 *
 * @package    local_wsmanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once(__DIR__ . '/../../../config.php');

require_login();
$id = required_param('id', PARAM_INT); // Schema ID.
$v1 = optional_param('v1', 0, PARAM_INT); // History ID 1.
$v2 = optional_param('v2', 0, PARAM_INT); // History ID 2.
$compare = optional_param_array('compare', [], PARAM_INT);

// Handle checkbox array selection.
if (empty($v1) && empty($v2) && !empty($compare) && count($compare) >= 2) {
    sort($compare); // Sort IDs ascending (older first).
    $v1 = $compare[0];
    $v2 = $compare[1];
}

// Ensure we have two versions to compare.
if (empty($v1) || empty($v2)) {
    throw new \moodle_exception('missingparam', 'error', '', 'v1, v2 or compare[]');
}

require_capability('local/wsmanager:view', context_system::instance());

$manager = new \local_wsmanager\schema\manager();
$historymanager = new \local_wsmanager\schema\history_manager();

$schema = $manager->get_schema($id);
if (!$schema) {
    throw new \moodle_exception('error_schema_not_found', 'local_wsmanager');
}

$urlparams = ['id' => $id, 'v1' => $v1, 'v2' => $v2];
$PAGE->set_url(new moodle_url('/local/wsmanager/pages/compare.php', $urlparams));
$PAGE->set_context(context_system::instance());
$PAGE->set_title($schema->name . ': ' . get_string('compare_versions', 'local_wsmanager'));
$PAGE->set_heading($schema->name);
$PAGE->set_pagelayout('admin');

// Navigation.
$PAGE->navbar->add(get_string('pluginname', 'local_wsmanager'), new moodle_url('/local/wsmanager/pages/dashboard.php'));
$PAGE->navbar->add($schema->name, new moodle_url('/local/wsmanager/pages/view.php', ['id' => $id]));
$PAGE->navbar->add(
    get_string('version_history', 'local_wsmanager'),
    new moodle_url('/local/wsmanager/pages/history.php', ['id' => $id])
);
$PAGE->navbar->add(get_string('compare_versions', 'local_wsmanager'));

echo $OUTPUT->header();

// Get version details.
$ver1 = $historymanager->get_version($v1);
$ver2 = $historymanager->get_version($v2);

if (!$ver1 || !$ver2) {
    throw new \moodle_exception('historynotfound', 'local_wsmanager');
}

// Calculate comparison.
$comparison = $historymanager->compare_versions($v1, $v2);

// Prepare lines for template.
$lines = [];
$max = max(count($comparison['lines1']), count($comparison['lines2']));

for ($i = 0; $i < $max; $i++) {
    $l1 = $comparison['lines1'][$i] ?? null;
    $l2 = $comparison['lines2'][$i] ?? null;

    $class1 = '';
    $class2 = '';

    if ($l1 !== $l2) {
        if ($l1 !== null && $l2 === null) {
            // Deleted in v2 (present in v1).
            $class1 = 'bg-danger text-white';
            $class2 = 'bg-light'; // Empty placeholder.
        } else if ($l1 === null && $l2 !== null) {
            // Added in v2.
            $class1 = 'bg-light'; // Empty placeholder.
            $class2 = 'bg-success text-white';
        } else {
            // Modified.
            $class1 = 'bg-warning';
            $class2 = 'bg-warning';
        }
    }

    $lines[] = [
        'content1' => $l1 !== null ? $l1 : '',
        'class1' => $class1,
        'content2' => $l2 !== null ? $l2 : '',
        'class2' => $class2,
    ];
}

$context = [
    'backurl' => (new moodle_url('/local/wsmanager/pages/history.php', ['id' => $id]))->out(false),
    'pagetitle' => get_string('compare_versions', 'local_wsmanager'),
    'v1' => [
        'version' => $ver1->version,
        'date' => userdate($ver1->timecreated),
        'user' => fullname(\core_user::get_user($ver1->changedby)),
    ],
    'v2' => [
        'version' => $ver2->version,
        'date' => userdate($ver2->timecreated),
        'user' => fullname(\core_user::get_user($ver2->changedby)),
    ],
    'lines' => $lines,
];

echo $OUTPUT->render_from_template('local_wsmanager/compare_page', $context);

echo $OUTPUT->footer();
