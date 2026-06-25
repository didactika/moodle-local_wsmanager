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
 * Regenerate token page
 *
 * @package    local_servicemanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/servicemanager:manage', $context);

$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/servicemanager/pages/regenerate_token.php', ['id' => $id]));
$PAGE->set_title(get_string('action_regenerate_token', 'local_servicemanager'));
$PAGE->set_heading(get_string('action_regenerate_token', 'local_servicemanager'));
$PAGE->set_pagelayout('admin');

$manager = new \local_servicemanager\schema\manager();
$schema = $manager->get_schema($id);

if (!$schema) {
    redirect(
        new moodle_url('/local/servicemanager/pages/dashboard.php'),
        get_string('schema_not_found', 'local_servicemanager'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

$viewurl = new moodle_url('/local/servicemanager/pages/view.php', ['id' => $id]);

if ($confirm && confirm_sesskey()) {
    // Regenerate the token.
    $tokenmanager = new \local_servicemanager\automation\token_manager();
    $result = $tokenmanager->regenerate_token($id);

    // Store token in session for display.
    $SESSION->servicemanager_new_token = $result['token'];
    $SESSION->servicemanager_schema_id = $id;

    \core\notification::success(get_string('token_regenerated', 'local_servicemanager'));
    redirect(new moodle_url('/local/servicemanager/pages/view.php', ['id' => $id, 'newtoken' => 1]));
}

// Show confirmation.
echo $OUTPUT->header();

$confirmurl = new moodle_url('/local/servicemanager/pages/regenerate_token.php', [
    'id' => $id,
    'confirm' => 1,
    'sesskey' => sesskey(),
]);

echo $OUTPUT->confirm(
    get_string('confirm_regenerate_token', 'local_servicemanager'),
    $confirmurl,
    $viewurl
);

echo $OUTPUT->footer();
