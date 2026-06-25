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
 * Language strings for local_servicemanager
 *
 * @package    local_servicemanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['action_delete'] = 'Delete';
$string['action_disable'] = 'Disable';
$string['action_edit'] = 'Edit';
$string['action_enable'] = 'Enable';
$string['action_export'] = 'Export';
$string['action_generate_token'] = 'Generate Token';
$string['action_regenerate_token'] = 'Regenerate Token';
$string['action_view'] = 'View';
$string['actions'] = 'Actions';
$string['authorized_users'] = 'Authorized Users';
$string['back'] = 'Back';
$string['back_to_history'] = 'Back to History';
$string['bulk_delete'] = 'Delete';
$string['bulk_delete_confirm'] = 'Are you sure you want to delete the selected schemas? This action cannot be undone.';
$string['bulk_deleted'] = '{$a} schema(s) have been deleted.';
$string['bulk_deleted_with_errors'] = '{$a->count} schema(s) deleted, {$a->errors} error(s) occurred.';
$string['bulk_disable'] = 'Disable';
$string['bulk_disabled'] = '{$a} schema(s) have been disabled.';
$string['bulk_enable'] = 'Enable';
$string['bulk_enabled'] = '{$a} schema(s) have been enabled.';
$string['bulk_export'] = 'Export';
$string['calculated_capabilities'] = 'Calculated from Functions';
$string['capabilities'] = 'Capabilities';
$string['changes_will_apply'] = 'Changes will be applied to the user, role, and service when you save.';
$string['cleanup_enabled'] = 'Enable log cleanup';
$string['cleanup_enabled_desc'] = 'Automatically delete old health check logs.';
$string['cleanup_retention_days'] = 'Log retention (days)';
$string['cleanup_retention_days_desc'] = 'Number of days to keep health check logs. Logs older than this will be deleted.';
$string['cleanup_task'] = 'Service Schema Log Cleanup';
$string['compare_select_two'] = 'Please select exactly two versions to compare.';
$string['compare_versions'] = 'Compare Versions';
$string['confirm_delete'] = 'Are you sure you want to delete schema "{$a}"? This will also delete the associated user, role, and service.';
$string['confirm_regenerate_token'] = 'Are you sure you want to regenerate the token? The current token will be invalidated immediately.';
$string['conflict_action'] = 'When schema ID exists';
$string['conflict_action_help'] = 'Choose what to do when a schema with the same ID already exists.';
$string['conflict_handling'] = 'Conflict Handling';
$string['conflict_overwrite'] = 'Overwrite (replace existing)';
$string['conflict_rename'] = 'Rename (add .imported suffix)';
$string['conflict_skip'] = 'Skip (keep existing)';
$string['copied'] = 'Copied!';
$string['copy'] = 'Copy';
$string['current'] = 'Current';
$string['current_token'] = 'Current Token';
$string['dashboard'] = 'Dashboard';
$string['description'] = 'Description';
$string['disabled'] = 'Disabled';
$string['doc_default_capabilities'] = 'The following capabilities are automatically added to every schema regardless of the functions defined:';
$string['doc_definition'] = 'Definition Section';
$string['doc_definition_desc'] = 'The definition section specifies the web service functions and capabilities.';
$string['doc_example'] = 'Complete Example';
$string['doc_example_col'] = 'Example';
$string['doc_example_complete_desc'] = 'The example schema above shows a complete functional configuration.';
$string['doc_example_to_get_started'] = 'to get started quickly.';
$string['doc_functions_desc'] = 'Functions can be specified in simple or extended format:';
$string['doc_meta'] = 'Meta Section';
$string['doc_meta_description'] = 'Brief description of the service purpose.';
$string['doc_meta_id'] = 'Unique identifier. Only letters, numbers, and dots (.) allowed. Max 50 characters.';
$string['doc_meta_maintainer'] = 'Person or team responsible for the schema.';
$string['doc_meta_name'] = 'Human-readable name for the service. Must be unique across all schemas.';
$string['doc_meta_version'] = 'Version string (semantic versioning recommended). Must be incremented when the definition (functions or capabilities) changes; metadata-only edits (name, maintainer, description) do not require a version bump.';
$string['doc_naming'] = 'Naming Conventions';
$string['doc_requirements_download_files'] = 'Boolean. If true, consumers of this service may download files via the Moodle webservice file endpoint. Defaults to false.';
$string['doc_requirements_plugins'] = 'Array of Moodle plugin names that must be installed. A warning is shown if any are missing, but schema creation is not blocked.';
$string['doc_requirements_upload_files'] = 'Boolean. If true, consumers of this service may upload files via the Moodle webservice upload endpoint. Defaults to false.';
$string['doc_structure'] = 'Schema Structure';
$string['doc_structure_desc'] = 'A service schema YAML file must contain the following sections:';
$string['documentation'] = 'Schema Documentation';
$string['download_example'] = 'Download Example File';
$string['download_example_desc'] = 'Get a working sample YAML schema file';
$string['edit'] = 'Edit';
$string['edit_schema'] = 'Edit Schema';
$string['email_footer'] = 'You are receiving this email because you are listed as a notification recipient for Web Service Manager.';
$string['error_critical_function_missing'] = 'Critical function "{$a}" is missing. Schema cannot be created.';
$string['error_duplicate_function'] = 'Function "{$a}" is duplicated in the definition.';
$string['error_function_not_found'] = 'Function "{$a}" does not exist in this Moodle installation.';
$string['error_id_change_forbidden'] = 'Changing the Schema ID is not allowed. Please Create a new schema instead.';
$string['error_invalid_schema_id'] = 'Schema ID "{$a}" is invalid. Only letters, numbers, and dots (.) are allowed.';
$string['error_invalid_yaml'] = 'Invalid YAML format: {$a}';
$string['error_missing_definition'] = 'Missing required "definition" section.';
$string['error_missing_functions'] = 'Missing required "definition.functions" array.';
$string['error_missing_meta'] = 'Missing required "meta" section in YAML.';
$string['error_missing_meta_id'] = 'Missing required "meta.id" field.';
$string['error_missing_meta_name'] = 'Missing required "meta.name" field.';
$string['error_missing_meta_version'] = 'Missing required "meta.version" field.';
$string['error_plugin_not_installed'] = 'Required plugin "{$a}" is not installed.';
$string['error_schema_id_exists'] = 'A schema with ID "{$a}" already exists.';
$string['error_schema_id_too_long'] = 'Schema ID is too long ({$a} characters). Maximum allowed is 50 characters.';
$string['error_schema_name_exists'] = 'A schema with the name "{$a}" already exists. Schema names must be unique.';
$string['error_version_change_forbidden'] = 'The version can only be changed if the schema definition is modified. Metadata changes do not require a version update.';
$string['error_version_change_required'] = 'Content changes detected. You must update the version number in the YAML (e.g. increment the version) to save these changes.';
$string['error_version_must_increment'] = 'New version ({$a->new}) must be greater than current version ({$a->current}). Versions can only decrease via history rollback.';
$string['export_all'] = 'Export All';
$string['export_error'] = 'Error creating export file.';
$string['external_service'] = 'External Service';
$string['extra_capabilities'] = 'Extra Capabilities';
$string['field'] = 'Field';
$string['filter_apply'] = 'Apply';
$string['filter_clear'] = 'Clear';
$string['filter_date_from'] = 'Date from';
$string['filter_date_to'] = 'Date to';
$string['filter_name'] = 'Search name...';
$string['filter_per_page'] = 'Per page';
$string['filter_status'] = 'Status';
$string['filter_status_all'] = 'All';
$string['filter_version'] = 'Version';
$string['filters'] = 'Filters';
$string['filters_active'] = 'Active filters';
$string['filters_applied'] = 'Filters applied';
$string['function_critical'] = 'Critical';
$string['function_exists'] = 'Exists';
$string['function_missing'] = 'Missing';
$string['function_name'] = 'Function Name';
$string['function_status'] = 'Status';
$string['functions'] = 'Functions';
$string['generatetoken'] = 'Generate token automatically';
$string['generatetoken_desc'] = 'If checked, a token will be generated for the service user and displayed after upload.';
$string['healthcheck_all_healthy'] = 'All service schemas are healthy.';
$string['healthcheck_enabled'] = 'Enable health check';
$string['healthcheck_enabled_desc'] = 'Run automatic health checks on schemas.';
$string['healthcheck_healthy_summary'] = 'All service schemas are operating normally. No action is required.';
$string['healthcheck_issues_found'] = 'Issues detected in {$a} schema(s).';
$string['healthcheck_issues_summary'] = 'Some schemas require attention. Please review the details below.';
$string['healthcheck_report_subject'] = 'Informe de Salud de Esquemas de Servicios Web';
$string['healthcheck_task'] = 'Service Schema Health Check';
$string['history_count'] = 'Showing {$a} version(s).';
$string['historynotfound'] = 'History record not found.';
$string['import'] = 'Import';
$string['import_complete'] = 'Import complete: {$a->imported} imported, {$a->skipped} skipped, {$a->errors_count} with errors.';
$string['import_error_no_id'] = 'YAML does not contain a valid meta.id field.';
$string['import_file'] = 'Import File';
$string['import_file_help'] = 'Upload a YAML schema file (.yaml, .yml) or a ZIP archive containing multiple schemas.';
$string['import_info_text'] = 'You can import schemas from YAML files or ZIP archives:';
$string['import_info_title'] = 'Import Schemas';
$string['import_info_yaml'] = 'Single YAML file (.yaml or .yml)';
$string['import_info_zip'] = 'ZIP archive containing multiple YAML files';
$string['import_schemas'] = 'Import Schemas';
$string['invalid_action'] = 'Invalid action.';
$string['issues'] = 'Issues Detected';
$string['manage_schemas'] = 'Manage Schemas';
$string['modified'] = 'Modified';
$string['no_file_uploaded'] = 'No file was uploaded.';
$string['no_history'] = 'No version history available for this schema.';
$string['no_schemas'] = 'No schemas have been defined yet. Upload a YAML file to create your first schema.';
$string['no_schemas_filtered'] = 'No schemas found with the applied filters.';
$string['no_schemas_selected'] = 'No schemas were selected.';
$string['no_schemas_to_export'] = 'There are no schemas to export.';
$string['no_token'] = 'No token generated';
$string['notification_emails'] = 'Notification emails';
$string['notification_emails_desc'] = 'Comma-separated list of email addresses to receive health notifications. Leave empty to use site administrators only.';
$string['notification_level'] = 'Notification level';
$string['notification_level_all'] = 'All (including healthy)';
$string['notification_level_desc'] = 'Minimum status level to trigger notifications.';
$string['notify_admins'] = 'Also notify site administrators';
$string['notify_admins_desc'] = 'Send notifications to site administrators in addition to the emails above.';
$string['pagination_first'] = 'First';
$string['pagination_last'] = 'Last';
$string['pagination_next'] = 'Next';
$string['pagination_page_info'] = 'Page {$a->current} / {$a->total}';
$string['pagination_previous'] = 'Previous';
$string['pattern'] = 'Pattern';
$string['pluginname'] = 'Web Service Manager';
$string['privacy:metadata'] = 'The Web Service Manager plugin does not store any personal data.';
$string['provisioned_resources'] = 'Provisioned Resources';
$string['quick_links'] = 'Quick Links';
$string['req_download_files'] = 'Can download files';
$string['req_file_access'] = 'File Access';
$string['req_plugins'] = 'Required Plugins';
$string['req_upload_files'] = 'Can upload files';
$string['resource'] = 'Resource';
$string['rollback'] = 'Rollback';
$string['rollback_backup'] = 'Backup before rollback';
$string['rollback_confirm'] = 'Are you sure you want to rollback to this version? Current changes will be saved as a backup.';
$string['rollback_error'] = 'Error rolling back schema';
$string['rollback_success'] = 'Schema has been rolled back successfully.';
$string['rollback_to_version'] = 'Rolled back to version {$a}';
$string['schema_created'] = 'Created';
$string['schema_created_success'] = 'Schema "{$a}" was created successfully.';
$string['schema_deleted_success'] = 'Schema "{$a}" was deleted successfully.';
$string['schema_description'] = 'Description';
$string['schema_enabled'] = 'Enabled';
$string['schema_id'] = 'Schema ID';
$string['schema_information'] = 'Schema Information';
$string['schema_maintainer'] = 'Maintainer';
$string['schema_modified'] = 'Last Modified';
$string['schema_name'] = 'Name';
$string['schema_not_found'] = 'Schema not found. It may have been deleted.';
$string['schema_reference'] = 'YAML Schema Reference';
$string['schema_requirements'] = 'Requirements';
$string['schema_status'] = 'Status';
$string['schema_updated_success'] = 'Schema "{$a}" was updated successfully.';
$string['schema_version'] = 'Version';
$string['select_all'] = 'Select all';
$string['selected'] = 'Selected';
$string['service_role'] = 'Service Role';
$string['service_user'] = 'Service User';
$string['servicemanager:manage'] = 'Manage service schemas';
$string['servicemanager:view'] = 'View service schemas';
$string['settings'] = 'Settings';
$string['settings_cleanup'] = 'Log Cleanup';
$string['settings_cleanup_desc'] = 'Configure automatic log cleanup to prevent database growth.';
$string['settings_healthcheck'] = 'Health Check';
$string['settings_healthcheck_desc'] = 'Configure automatic health monitoring.';
$string['settings_notifications'] = 'Notifications';
$string['settings_notifications_desc'] = 'Configure who receives health check notifications.';
$string['settings_version_retention'] = 'Version Retention Policy';
$string['settings_version_retention_desc'] = 'Configure automatic cleanup of old schema versions.';
$string['status_critical'] = 'Critical';
$string['status_healthy'] = 'Healthy';
$string['status_warning'] = 'Warning';
$string['task_scheduled_validation'] = 'Scheduled schema validation';
$string['task_version_cleanup'] = 'Clean up old schema versions';
$string['token_copy_warning'] = 'Copy this token now. It will not be shown again for security reasons.';
$string['token_generated'] = 'Token Generated Successfully';
$string['token_name'] = 'Token Name';
$string['token_regenerated'] = 'Token Regenerated Successfully';
$string['upload'] = 'Upload Schema';
$string['upload_schema'] = 'Upload Schema';
$string['version'] = 'Version';
$string['version_history'] = 'Version History';
$string['version_retention_enabled'] = 'Enable Version Retention';
$string['version_retention_enabled_desc'] = 'If enabled, old versions of schemas will be automatically deleted, keeping only the most recent ones.';
$string['version_retention_max'] = 'Max Versions Per Schema';
$string['version_retention_max_desc'] = 'Maximum number of historical versions to keep for each schema. The oldest versions will be deleted first.';
$string['view_documentation'] = 'View Documentation';
$string['view_documentation_desc'] = 'See the full documentation for the YAML schema format.';
$string['view_schema'] = 'View Schema';
$string['view_yaml'] = 'View YAML';
$string['warning_extra_capabilities_empty'] = 'The schema declares "extra_capabilities" but none were parsed. Check the indentation: list items must be aligned with or indented under the key.';
$string['warning_function_missing'] = 'Non-critical function "{$a}" is missing.';
$string['warning_plugin_not_installed'] = 'Recommended plugin "{$a}" is not installed.';
$string['warning_user_email_not_found'] = 'User with email "{$a}" not found. Skipping authorization.';
$string['ws_disabled_label'] = 'Disabled';
$string['ws_enabled_label'] = 'Enabled';
$string['ws_health_label'] = 'Health Summary';
$string['ws_manage_protocols_link'] = 'Manage protocols';
$string['ws_overview_link'] = 'Web services overview';
$string['ws_protocols_label'] = 'Enabled Protocols';
$string['ws_services_label'] = 'Web Services';
$string['ws_status_disabled'] = 'Disabled';
$string['ws_status_operational'] = 'Operational';
$string['ws_status_panel'] = 'Web Services Status';
$string['ws_status_warning'] = 'No protocols enabled';
$string['yamlcontent'] = 'YAML Content';
$string['yamlcontent_help'] = 'Edit the YAML schema definition directly.<br><br><a href="/local/servicemanager/pages/documentation.php"><strong>📖 View Documentation</strong></a>';
$string['yamlfile'] = 'YAML Schema File';
$string['yamlfile_help'] = 'Upload a YAML file containing the service schema definition. Only .yaml and .yml files are accepted.<br><br><a href="/local/servicemanager/pages/documentation.php"><strong>📖 View Documentation</strong></a>';
