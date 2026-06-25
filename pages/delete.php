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
 * Delete schema page
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
require_capability('local/wsmanager:manage', $context);

$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/wsmanager/pages/delete.php', ['id' => $id]));
$PAGE->set_title(get_string('action_delete', 'local_wsmanager'));
$PAGE->set_heading(get_string('action_delete', 'local_wsmanager'));
$PAGE->set_pagelayout('admin');

$manager = new \local_wsmanager\schema\manager();
$schema = $manager->get_schema($id);

if (!$schema) {
    redirect(
        new moodle_url('/local/wsmanager/pages/dashboard.php'),
        get_string('schema_not_found', 'local_wsmanager'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

$dashboardurl = new moodle_url('/local/wsmanager/pages/dashboard.php');

if ($confirm && confirm_sesskey()) {
    $schemaname = $schema->name;
    $manager->delete_schema($id);

    redirect(
        $dashboardurl,
        get_string('schema_deleted_success', 'local_wsmanager', $schemaname),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Show confirmation.
echo $OUTPUT->header();

$confirmurl = new moodle_url('/local/wsmanager/pages/delete.php', [
    'id' => $id,
    'confirm' => 1,
    'sesskey' => sesskey(),
]);

echo $OUTPUT->confirm(
    get_string('confirm_delete', 'local_wsmanager', $schema->name),
    $confirmurl,
    $dashboardurl
);

echo $OUTPUT->footer();
