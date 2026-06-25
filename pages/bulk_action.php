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
 * Bulk action handler for schemas.
 *
 * @package    local_servicemanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

require_login();
require_capability('local/servicemanager:manage', context_system::instance());
require_sesskey();

$action = required_param('action', PARAM_ALPHA);
$ids = required_param_array('ids', PARAM_INT);

if (empty($ids)) {
    redirect(
        new moodle_url('/local/servicemanager/pages/dashboard.php'),
        get_string('no_schemas_selected', 'local_servicemanager'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

$manager = new \local_servicemanager\schema\manager();

switch ($action) {
    case 'enable':
        $count = 0;
        foreach ($ids as $id) {
            $manager->set_enabled($id, true);
            $count++;
        }
        $message = get_string('bulk_enabled', 'local_servicemanager', $count);
        $notifytype = \core\output\notification::NOTIFY_SUCCESS;
        break;

    case 'disable':
        $count = 0;
        foreach ($ids as $id) {
            $manager->set_enabled($id, false);
            $count++;
        }
        $message = get_string('bulk_disabled', 'local_servicemanager', $count);
        $notifytype = \core\output\notification::NOTIFY_SUCCESS;
        break;

    case 'delete':
        $count = 0;
        $errors = 0;
        foreach ($ids as $id) {
            try {
                $manager->delete_schema($id);
                $count++;
            } catch (Exception $e) {
                $errors++;
            }
        }
        if ($errors > 0) {
            $message = get_string('bulk_deleted_with_errors', 'local_servicemanager', ['count' => $count, 'errors' => $errors]);
            $notifytype = \core\output\notification::NOTIFY_WARNING;
        } else {
            $message = get_string('bulk_deleted', 'local_servicemanager', $count);
            $notifytype = \core\output\notification::NOTIFY_SUCCESS;
        }
        break;

    case 'export':
        // Export selected as ZIP.
        global $DB, $CFG;

        $tempdir = make_temp_directory('servicemanager_export');
        $zipfilepath = $tempdir . '/schemas_selected_' . date('Ymd_His') . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($zipfilepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            redirect(
                new moodle_url('/local/servicemanager/pages/dashboard.php'),
                get_string('export_error', 'local_servicemanager'),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }

        $manifest = [
            'exported_at' => date('c'),
            'moodle_version' => $CFG->version,
            'schema_count' => 0,
            'schemas' => [],
        ];

        foreach ($ids as $id) {
            $schema = $manager->get_schema($id);
            if ($schema) {
                $filename = $schema->schema_id . '.yaml';
                $zip->addFromString($filename, $schema->yaml_content);
                $manifest['schema_count']++;
                $manifest['schemas'][] = [
                    'id' => $schema->schema_id,
                    'name' => $schema->name,
                    'version' => $schema->version,
                ];
            }
        }

        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
        $zip->close();

        // Send file.
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="schemas_selected_' . date('Ymd_His') . '.zip"');
        header('Content-Length: ' . filesize($zipfilepath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($zipfilepath);
        unlink($zipfilepath);
        exit;

    default:
        $message = get_string('invalid_action', 'local_servicemanager');
        $notifytype = \core\output\notification::NOTIFY_ERROR;
}

redirect(
    new moodle_url('/local/servicemanager/pages/dashboard.php'),
    $message,
    null,
    $notifytype
);
