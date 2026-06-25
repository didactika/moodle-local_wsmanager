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
 * Settings for local_servicemanager
 *
 * @package    local_servicemanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create category for our plugin.
    $ADMIN->add('server', new admin_category(
        'local_servicemanager_category',
        get_string('pluginname', 'local_servicemanager')
    ));

    // Add dashboard link.
    $ADMIN->add('local_servicemanager_category', new admin_externalpage(
        'local_servicemanager_dashboard',
        get_string('dashboard', 'local_servicemanager'),
        new moodle_url('/local/servicemanager/pages/dashboard.php'),
        'local/servicemanager:view'
    ));

    // Add import page link.
    $ADMIN->add('local_servicemanager_category', new admin_externalpage(
        'local_servicemanager_import',
        get_string('import_schemas', 'local_servicemanager'),
        new moodle_url('/local/servicemanager/pages/import.php'),
        'local/servicemanager:manage'
    ));

    // Create settings page with tabs.
    $settings = new admin_settingpage(
        'local_servicemanager_settings',
        get_string('settings', 'local_servicemanager')
    );

    if ($ADMIN->fulltree) {
        // TAB: Notifications.
        $settings->add(new admin_setting_heading(
            'local_servicemanager/notifications_heading',
            get_string('settings_notifications', 'local_servicemanager'),
            get_string('settings_notifications_desc', 'local_servicemanager')
        ));

        // Notification emails (comma-separated).
        $settings->add(new admin_setting_configtextarea(
            'local_servicemanager/notification_emails',
            get_string('notification_emails', 'local_servicemanager'),
            get_string('notification_emails_desc', 'local_servicemanager'),
            '', // Default empty = use site admins.
            PARAM_TEXT
        ));

        // Send to site admins as well.
        $settings->add(new admin_setting_configcheckbox(
            'local_servicemanager/notify_admins',
            get_string('notify_admins', 'local_servicemanager'),
            get_string('notify_admins_desc', 'local_servicemanager'),
            1
        ));

        // Notification level.
        $settings->add(new admin_setting_configselect(
            'local_servicemanager/notification_level',
            get_string('notification_level', 'local_servicemanager'),
            get_string('notification_level_desc', 'local_servicemanager'),
            'warning',
            [
                'critical' => get_string('status_critical', 'local_servicemanager'),
                'warning' => get_string('status_warning', 'local_servicemanager'),
                'all' => get_string('notification_level_all', 'local_servicemanager'),
            ]
        ));

        // TAB: Health Check.
        $settings->add(new admin_setting_heading(
            'local_servicemanager/healthcheck_heading',
            get_string('settings_healthcheck', 'local_servicemanager'),
            get_string('settings_healthcheck_desc', 'local_servicemanager')
        ));

        // Health check enabled.
        $settings->add(new admin_setting_configcheckbox(
            'local_servicemanager/healthcheck_enabled',
            get_string('healthcheck_enabled', 'local_servicemanager'),
            get_string('healthcheck_enabled_desc', 'local_servicemanager'),
            1
        ));

        // TAB: Log Cleanup.
        $settings->add(new admin_setting_heading(
            'local_servicemanager/cleanup_heading',
            get_string('settings_cleanup', 'local_servicemanager'),
            get_string('settings_cleanup_desc', 'local_servicemanager')
        ));

        // Log cleanup enabled.
        $settings->add(new admin_setting_configcheckbox(
            'local_servicemanager/cleanup_enabled',
            get_string('cleanup_enabled', 'local_servicemanager'),
            get_string('cleanup_enabled_desc', 'local_servicemanager'),
            1
        ));

        // Log retention days.
        $settings->add(new admin_setting_configtext(
            'local_servicemanager/cleanup_retention_days',
            get_string('cleanup_retention_days', 'local_servicemanager'),
            get_string('cleanup_retention_days_desc', 'local_servicemanager'),
            30,
            PARAM_INT
        ));

        // TAB: Version Retention.
        $settings->add(new admin_setting_heading(
            'local_servicemanager/version_retention_heading',
            get_string('settings_version_retention', 'local_servicemanager'),
            get_string('settings_version_retention_desc', 'local_servicemanager')
        ));

        // Version retention enabled.
        $settings->add(new admin_setting_configcheckbox(
            'local_servicemanager/version_retention_enabled',
            get_string('version_retention_enabled', 'local_servicemanager'),
            get_string('version_retention_enabled_desc', 'local_servicemanager'),
            1
        ));

        // Max versions per schema.
        $settings->add(new admin_setting_configtext(
            'local_servicemanager/version_retention_max',
            get_string('version_retention_max', 'local_servicemanager'),
            get_string('version_retention_max_desc', 'local_servicemanager'),
            10,
            PARAM_INT
        ));
    }

    $ADMIN->add('local_servicemanager_category', $settings);
}
