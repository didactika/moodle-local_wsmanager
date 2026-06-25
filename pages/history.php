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
 * Schema version history page.
 *
 * @package    local_servicemanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$historyid = optional_param('historyid', 0, PARAM_INT);

// Pagination parameters.
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 10, PARAM_INT);

// Filter parameters.
$filterversion = optional_param('version', '', PARAM_TEXT);
$datefrom = optional_param('datefrom', 0, PARAM_INT);
$dateto = optional_param('dateto', 0, PARAM_INT);

// Build filters array.
$filters = [];
if (!empty($filterversion)) {
    $filters['version'] = $filterversion;
}
if ($datefrom > 0) {
    $filters['datefrom'] = $datefrom;
}
if ($dateto > 0) {
    $filters['dateto'] = $dateto;
}

$hasfilters = !empty($filters);

require_login();
require_capability('local/servicemanager:manage', context_system::instance());

$manager = new \local_servicemanager\schema\manager();
$historymanager = new \local_servicemanager\schema\history_manager();

$schema = $manager->get_schema($id);
if (!$schema) {
    redirect(
        new moodle_url('/local/servicemanager/pages/dashboard.php'),
        get_string('schema_not_found', 'local_servicemanager'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

$urlparams = [
    'id' => $id,
    'perpage' => $perpage,
    'version' => $filterversion,
    'datefrom' => $datefrom,
    'dateto' => $dateto,
];
$PAGE->set_url(new moodle_url('/local/servicemanager/pages/history.php', $urlparams));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_servicemanager') . ' - ' . get_string('version_history', 'local_servicemanager'));
$PAGE->set_heading(get_string('version_history', 'local_servicemanager') . ': ' . $schema->name);
$PAGE->set_pagelayout('admin');

// Navigation.
$PAGE->navbar->add(get_string('pluginname', 'local_servicemanager'), new moodle_url('/local/servicemanager/pages/dashboard.php'));
$PAGE->navbar->add($schema->name, new moodle_url('/local/servicemanager/pages/view.php', ['id' => $id]));
$PAGE->navbar->add(get_string('version_history', 'local_servicemanager'));

// Handle rollback action.
$confirm = optional_param('confirm', 0, PARAM_INT);

if ($action === 'rollback' && $historyid && confirm_sesskey()) {
    if ($confirm) {
        // Confirmed - perform rollback.
        try {
            $historymanager->rollback($id, $historyid);
            redirect(
                new moodle_url('/local/servicemanager/pages/view.php', ['id' => $id]),
                get_string('rollback_success', 'local_servicemanager'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        } catch (Exception $e) {
            redirect(
                $PAGE->url,
                get_string('rollback_error', 'local_servicemanager') . ': ' . $e->getMessage(),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }
    } else {
        // Show confirmation page.
        echo $OUTPUT->header();

        $confirmurl = new moodle_url('/local/servicemanager/pages/history.php', [
            'id' => $id,
            'action' => 'rollback',
            'historyid' => $historyid,
            'confirm' => 1,
            'sesskey' => sesskey(),
        ]);
        $cancelurl = new moodle_url('/local/servicemanager/pages/history.php', ['id' => $id]);

        echo $OUTPUT->confirm(
            get_string('rollback_confirm', 'local_servicemanager'),
            $confirmurl,
            $cancelurl
        );

        echo $OUTPUT->footer();
        exit;
    }
}

echo $OUTPUT->header();

// Get paginated history.
$totalcount = $historymanager->count_history($id, $filters);
$history = $historymanager->get_history_paginated($id, $page, $perpage, $filters);

// Prepare context for template.
$context = [
    'id' => $id,
    'backurl' => (new moodle_url('/local/servicemanager/pages/view.php', ['id' => $id]))->out(false),
    'hasfilters' => $hasfilters,
    'filtercount' => count($filters),
    'filterversion' => $filterversion,
    'datefromval' => $datefrom > 0 ? date('Y-m-d', $datefrom) : '',
    'datetoval' => $dateto > 0 ? date('Y-m-d', $dateto) : '',
    'clearurl' => (new moodle_url($PAGE->url, [
        'id' => $id, 'page' => 0, 'perpage' => 10, 'version' => '', 'datefrom' => 0, 'dateto' => 0,
    ]))->out(false),
    'compareaction' => (new moodle_url('/local/servicemanager/pages/compare.php'))->out(false),
    'totalcount' => $totalcount,
    'nohistory' => empty($history),
];

// Per page options.
$context['perpageoptions'] = [];
foreach ([10, 25, 50, 100] as $opt) {
    $context['perpageoptions'][] = [
        'value' => $opt,
        'selected' => ($perpage == $opt),
    ];
}

// Current version info.
if (!$hasfilters && $page === 0) {
    $context['currentversion'] = [
        'version' => $schema->version,
        'timemodified' => userdate($schema->timemodified),
        'editurl' => (new moodle_url('/local/servicemanager/pages/edit.php', ['id' => $id]))->out(false),
    ];
}

// History table.
if (!empty($history)) {
    $historyrows = [];
    foreach ($history as $record) {
        $user = new stdClass();
        $user->id = $record->changedby;
        $user->firstname = $record->firstname;
        $user->lastname = $record->lastname;
        $user->middlename = $record->middlename;
        $user->alternatename = $record->alternatename;
        $user->firstnamephonetic = $record->firstnamephonetic;
        $user->lastnamephonetic = $record->lastnamephonetic;
        $user->picture = $record->picture;
        $user->email = $record->email;
        $user->imagealt = $record->imagealt;

        $userpic = $OUTPUT->user_picture($user, ['size' => 30, 'link' => false]);
        $username = fullname($user);

        $row = [
            'id' => $record->id,
            'version' => $record->version,
            'user_profile' => $userpic . ' ' . $username,
            'date' => userdate($record->timecreated, get_string('strftimedatetimeshort')),
            'change_reason' => format_text($record->change_reason, FORMAT_MOODLE),
            'yaml_content' => $record->yaml_content,
            'view_url' => (new moodle_url(
                '/local/servicemanager/pages/view_history.php',
                ['historyid' => $record->id]
            ))->out(false),
        ];

        // Only show rollback if version is different from current.
        if ($record->version !== $schema->version) {
            $row['rollback_url'] = (new moodle_url($PAGE->url, [
                'action' => 'rollback',
                'historyid' => $record->id,
                'sesskey' => sesskey(),
            ]))->out(false);
        }

        $historyrows[] = $row;
    }

    $context['history_rows'] = $historyrows;

    // Standard Moodle Pagination.
    $pagingbar = new \core\output\paging_bar($totalcount, $page, $perpage, $PAGE->url);
    $context['pagingbar'] = $OUTPUT->render($pagingbar);
}

// Load AMD module.
$PAGE->requires->js_call_amd('local_servicemanager/history', 'init');

echo $OUTPUT->render_from_template('local_servicemanager/history_page', $context);

echo $OUTPUT->footer();
