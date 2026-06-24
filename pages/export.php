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
 * Export schema(s) page.
 *
 * @package    local_wsmanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

$id = optional_param('id', 0, PARAM_INT);
$all = optional_param('all', 0, PARAM_BOOL);

require_login();
require_capability('local/wsmanager:view', context_system::instance());

$manager = new \local_wsmanager\schema\manager();

// Export single schema as YAML.
if ($id) {
    $schema = $manager->get_schema($id);
    if (!$schema) {
        throw new moodle_exception('schemanotfound', 'local_wsmanager');
    }

    $filename = $schema->schema_id . '.yaml';
    $content = $schema->yaml_content;

    header('Content-Type: application/x-yaml');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    echo $content;
    exit;
}

// Export all schemas as ZIP.
if ($all) {
    $schemas = $manager->get_all_schemas();

    if (empty($schemas)) {
        redirect(
            new moodle_url('/local/wsmanager/pages/dashboard.php'),
            get_string('no_schemas_to_export', 'local_wsmanager'),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    $tempdir = make_temp_directory('wsmanager_export');
    $zipfilepath = $tempdir . '/schemas_export_' . date('Ymd_His') . '.zip';

    $zip = new ZipArchive();
    if ($zip->open($zipfilepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new moodle_exception('zipcreationfailed', 'local_wsmanager');
    }

    foreach ($schemas as $schema) {
        $filename = $schema->schema_id . '.yaml';
        $zip->addFromString($filename, $schema->yaml_content);
    }

    // Add a manifest file.
    $manifest = [
        'exported_at' => date('c'),
        'moodle_version' => $CFG->version,
        'schema_count' => count($schemas),
        'schemas' => [],
    ];
    foreach ($schemas as $schema) {
        $manifest['schemas'][] = [
            'id' => $schema->schema_id,
            'name' => $schema->name,
            'version' => $schema->version,
        ];
    }
    $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

    $zip->close();

    // Send file.
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="schemas_export_' . date('Ymd_His') . '.zip"');
    header('Content-Length: ' . filesize($zipfilepath));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    readfile($zipfilepath);

    // Cleanup.
    unlink($zipfilepath);
    exit;
}

// No parameters - redirect to dashboard.
redirect(new moodle_url('/local/wsmanager/pages/dashboard.php'));
