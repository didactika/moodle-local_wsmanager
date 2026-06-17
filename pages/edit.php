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
 * Edit page for existing schemas
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

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/wsmanager/pages/edit.php', ['id' => $id]));
$PAGE->set_title(get_string('edit_schema', 'local_wsmanager'));
$PAGE->set_heading(get_string('edit_schema', 'local_wsmanager'));
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
$viewurl = new moodle_url('/local/wsmanager/pages/view.php', ['id' => $id]);

$form = new \local_wsmanager\form\edit_schema_form(null, ['schema' => $schema]);

// Set default values.
$form->set_data([
    'id' => $schema->id,
    'yaml_content' => $schema->yaml_content,
    'enabled' => (int)$schema->enabled,
]);

if ($form->is_cancelled()) {
    redirect($viewurl);
} elseif ($data = $form->get_data()) {
    try {
        $result = $manager->update_schema($data->id, $data->yaml_content);

        // Apply enabled state (independent of YAML content).
        $manager->set_enabled($data->id, (bool)$data->enabled);

        // Show warnings if any.
        if (!empty($result['warnings'])) {
            foreach ($result['warnings'] as $warning) {
                \core\notification::warning($warning);
            }
        }

        \core\notification::success(get_string('schema_updated_success', 'local_wsmanager', $schema->name));
        redirect($viewurl);

    } catch (\Exception $e) {
        \core\notification::error($e->getMessage());
    }
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
