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
 * Upload page for new schemas
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

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/wsmanager/pages/upload.php'));
$PAGE->set_title(get_string('upload_schema', 'local_wsmanager'));
$PAGE->set_heading(get_string('upload_schema', 'local_wsmanager'));
$PAGE->set_pagelayout('admin');

$dashboardurl = new moodle_url('/local/wsmanager/pages/dashboard.php');

$form = new \local_wsmanager\form\upload_schema_form();

if ($form->is_cancelled()) {
    redirect($dashboardurl);
} else if ($data = $form->get_data()) {
    // Get file content.
    $content = $form->get_yaml_file_content('yamlfile');
    $generatetoken = !empty($data->generatetoken);

    try {
        $manager = new \local_wsmanager\schema\manager();
        $result = $manager->create_schema($content, $generatetoken);

        // Build success message.
        $parser = new \local_wsmanager\schema\yaml_parser();
        $yamldata = $parser->parse($content);
        $schemaname = $yamldata['meta']['name'] ?? 'Unknown';

        $message = get_string('schema_created_success', 'local_wsmanager', $schemaname);

        // If token was generated, show it.
        if (!empty($result['token'])) {
            $SESSION->wsmanager_new_token = $result['token'];
            $SESSION->wsmanager_schema_id = $result['id'];
        }

        // Show warnings if any.
        if (!empty($result['warnings'])) {
            foreach ($result['warnings'] as $warning) {
                \core\notification::warning($warning);
            }
        }

        \core\notification::success($message);

        // Redirect to view page to show token.
        redirect(new moodle_url('/local/wsmanager/pages/view.php', [
            'id' => $result['id'],
            'newtoken' => 1,
        ]));
    } catch (\Exception $e) {
        \core\notification::error($e->getMessage());
    }
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
