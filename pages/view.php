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
 * View page for schema details
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
require_capability('local/servicemanager:view', $context);

$id = required_param('id', PARAM_INT);
$newtoken = optional_param('newtoken', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/servicemanager/pages/view.php', ['id' => $id]));
$PAGE->set_title(get_string('view_schema', 'local_servicemanager'));
$PAGE->set_heading(get_string('view_schema', 'local_servicemanager'));
$PAGE->set_pagelayout('admin');

// Load AMD module for token management and log filtering.
$PAGE->requires->js_call_amd('local_servicemanager/token_manager', 'init');
$PAGE->requires->js_call_amd('local_servicemanager/health_log_filter', 'init');

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

// Parse YAML for display.
$parser = new \local_servicemanager\schema\yaml_parser();
$yamldata = $parser->parse($schema->yaml_content);
$functions = $parser->extract_functions($yamldata);
$extracaps = $parser->extract_extra_capabilities($yamldata);
$requiredplugins = $parser->extract_required_plugins($yamldata);

// Get calculated capabilities.
$capcalc = new \local_servicemanager\automation\capability_calculator();

// Build functions data.
$functionsdata = [];
foreach ($functions as $func) {
    $exists = $capcalc->function_exists($func['name']);
    $functionsdata[] = [
        'name' => $func['name'],
        'critical' => $func['critical'],
        'critical_label' => $func['critical'] ? get_string('function_critical', 'local_servicemanager') : '',
        'exists' => $exists,
        'status_class' => $exists ? 'text-success' : 'text-danger',
        'status_icon' => $exists ? 'fa-check' : 'fa-times',
        'status_label' => $exists ? get_string('function_exists', 'local_servicemanager')
            : get_string('function_missing', 'local_servicemanager'),
    ];
}

// Check for new token in session.
$tokenvalue = null;
if ($newtoken && isset($SESSION->servicemanager_new_token) && $SESSION->servicemanager_schema_id == $id) {
    $tokenvalue = $SESSION->servicemanager_new_token;
    unset($SESSION->servicemanager_new_token);
    unset($SESSION->servicemanager_schema_id);
}

// Build status info.
$statusclass = 'badge-success';
$statusicon = 'fa-check-circle';
if ($schema->status === 'warning') {
    $statusclass = 'badge-warning';
    $statusicon = 'fa-exclamation-triangle';
} else if ($schema->status === 'critical') {
    $statusclass = 'badge-danger';
    $statusicon = 'fa-times-circle';
}

// Load provisioned resources linked to this schema.
global $DB;
$resourceuser = null;
$resourcerole = null;
$resourceservice = null;

if ($schema->userid) {
    $u = $DB->get_record(
        'user',
        ['id' => $schema->userid],
        'id, username, firstname, lastname, firstnamephonetic, lastnamephonetic, middlename, alternatename'
    );
    if ($u) {
        $resourceuser = [
            'name'     => fullname($u),
            'username' => $u->username,
            'url'      => (new moodle_url('/user/profile.php', ['id' => $u->id]))->out(false),
        ];
    }
}
if ($schema->roleid) {
    $r = $DB->get_record('role', ['id' => $schema->roleid], 'id, name, shortname');
    if ($r) {
        $resourcerole = [
            'name'      => !empty($r->name) ? $r->name : $r->shortname,
            'shortname' => $r->shortname,
            'url'       => (new moodle_url('/admin/roles/define.php', ['action' => 'view', 'roleid' => $r->id]))->out(false),
        ];
    }
}
$servicedownloadfiles = false;
$serviceuploadfiles = false;
if ($schema->serviceid) {
    $s = $DB->get_record('external_services', ['id' => $schema->serviceid], 'id, name, shortname, downloadfiles, uploadfiles');
    if ($s) {
        $resourceservice = [
            'name'      => $s->name,
            'shortname' => $s->shortname,
            'url'       => (new moodle_url('/admin/webservice/service.php', ['id' => $s->id]))->out(false),
        ];
        $servicedownloadfiles = (bool) $s->downloadfiles;
        $serviceuploadfiles   = (bool) $s->uploadfiles;
    }
}

// Build requirements data.
$pluginmanager = core_plugin_manager::instance();
$pluginsdata = [];
foreach ($requiredplugins as $pluginname) {
    $info = $pluginmanager->get_plugin_info($pluginname);
    $installed = $info !== null;
    $pluginsdata[] = [
        'name'         => $pluginname,
        'installed'    => $installed,
        'status_class' => $installed ? 'text-success' : 'text-warning',
        'status_icon'  => $installed ? 'fa-check' : 'fa-exclamation-triangle',
        'status_label' => $installed ? get_string('function_exists', 'local_servicemanager')
                                     : get_string('function_missing', 'local_servicemanager'),
    ];
}

// Get health logs.
$healthlogs = $DB->get_records(
    'local_servicemanager_logs',
    ['schemaid' => $id],
    'timecreated DESC',
    '*',
    0,
    100
);

$logsdata = [];
foreach ($healthlogs as $log) {
    $logstatusclass = 'badge-success';
    if ($log->status === 'warning') {
        $logstatusclass = 'badge-warning';
    } else if ($log->status === 'critical') {
        $logstatusclass = 'badge-danger';
    }

    $logsdata[] = [
        'time' => userdate($log->timecreated),
        'status' => $log->status,
        'status_class' => $logstatusclass,
        'message' => $log->message,
    ];
}

$canmanage = has_capability('local/servicemanager:manage', $context);

$templatedata = [
    'schema_id' => $schema->schema_id,
    'name' => $schema->name,
    'version' => $schema->version,
    'description' => $schema->description,
    'maintainer' => $schema->maintainer,
    'status' => $schema->status,
    'status_label' => get_string('status_' . $schema->status, 'local_servicemanager'),
    'status_class' => $statusclass,
    'status_icon' => $statusicon,
    'enabled' => (bool) $schema->enabled,
    'timecreated' => userdate($schema->timecreated),
    'timemodified' => userdate($schema->timemodified),
    'has_token' => !empty($schema->tokenid),
    'new_token' => $tokenvalue,
    'show_new_token' => !empty($tokenvalue),
    'required_plugins' => $pluginsdata,
    'has_required_plugins' => !empty($pluginsdata),
    'service_download_files' => $servicedownloadfiles,
    'service_upload_files' => $serviceuploadfiles,
    'functions' => $functionsdata,
    'has_functions' => !empty($functionsdata),
    'has_resources' => ($resourceuser || $resourcerole || $resourceservice),
    'has_resource_user' => !empty($resourceuser),
    'resource_user_name' => $resourceuser['name'] ?? '',
    'resource_user_username' => $resourceuser['username'] ?? '',
    'resource_user_url' => $resourceuser['url'] ?? '',
    'has_resource_role' => !empty($resourcerole),
    'resource_role_name' => $resourcerole['name'] ?? '',
    'resource_role_shortname' => $resourcerole['shortname'] ?? '',
    'resource_role_url' => $resourcerole['url'] ?? '',
    'has_resource_service' => !empty($resourceservice),
    'resource_service_name' => $resourceservice['name'] ?? '',
    'resource_service_shortname' => $resourceservice['shortname'] ?? '',
    'resource_service_url' => $resourceservice['url'] ?? '',
    'extra_capabilities' => $extracaps,
    'has_extra_capabilities' => !empty($extracaps),
    'health_logs' => $logsdata,
    'has_health_logs' => !empty($logsdata),
    'can_manage' => $canmanage,
    'edit_url' => (new moodle_url('/local/servicemanager/pages/edit.php', ['id' => $id]))->out(false),
    'delete_url' => (new moodle_url('/local/servicemanager/pages/delete.php', ['id' => $id]))->out(false),
    'history_url' => (new moodle_url('/local/servicemanager/pages/history.php', ['id' => $id]))->out(false),
    'regenerate_url' => (new moodle_url('/local/servicemanager/pages/regenerate_token.php', ['id' => $id]))->out(false),
    'dashboard_url' => (new moodle_url('/local/servicemanager/pages/dashboard.php'))->out(false),
    'sesskey' => sesskey(),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_servicemanager/schema_detail', $templatedata);
echo $OUTPUT->footer();
