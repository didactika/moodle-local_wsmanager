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
 * Settings for local_wsmanager
 *
 * @package    local_wsmanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create category for our plugin.
    $ADMIN->add('server', new admin_category(
        'local_wsmanager_category',
        get_string('pluginname', 'local_wsmanager')
    ));

    // Add dashboard link.
    $ADMIN->add('local_wsmanager_category', new admin_externalpage(
        'local_wsmanager_dashboard',
        get_string('dashboard', 'local_wsmanager'),
        new moodle_url('/local/wsmanager/pages/dashboard.php'),
        'local/wsmanager:view'
    ));

    // Add import page link.
    $ADMIN->add('local_wsmanager_category', new admin_externalpage(
        'local_wsmanager_import',
        get_string('import_schemas', 'local_wsmanager'),
        new moodle_url('/local/wsmanager/pages/import.php'),
        'local/wsmanager:manage'
    ));

    // Create settings page with tabs.
    $settings = new admin_settingpage(
        'local_wsmanager_settings',
        get_string('settings', 'local_wsmanager')
    );

    if ($ADMIN->fulltree) {
        // =====================================
        // TAB: Notifications
        // =====================================
        $settings->add(new admin_setting_heading(
            'local_wsmanager/notifications_heading',
            get_string('settings_notifications', 'local_wsmanager'),
            get_string('settings_notifications_desc', 'local_wsmanager')
        ));

        // Notification emails (comma-separated).
        $settings->add(new admin_setting_configtextarea(
            'local_wsmanager/notification_emails',
            get_string('notification_emails', 'local_wsmanager'),
            get_string('notification_emails_desc', 'local_wsmanager'),
            '', // Default empty = use site admins.
            PARAM_TEXT
        ));

        // Send to site admins as well.
        $settings->add(new admin_setting_configcheckbox(
            'local_wsmanager/notify_admins',
            get_string('notify_admins', 'local_wsmanager'),
            get_string('notify_admins_desc', 'local_wsmanager'),
            1
        ));

        // Notification level.
        $settings->add(new admin_setting_configselect(
            'local_wsmanager/notification_level',
            get_string('notification_level', 'local_wsmanager'),
            get_string('notification_level_desc', 'local_wsmanager'),
            'warning',
            [
                'critical' => get_string('status_critical', 'local_wsmanager'),
                'warning' => get_string('status_warning', 'local_wsmanager'),
                'all' => get_string('notification_level_all', 'local_wsmanager'),
            ]
        ));

        // =====================================
        // TAB: Health Check
        // =====================================
        $settings->add(new admin_setting_heading(
            'local_wsmanager/healthcheck_heading',
            get_string('settings_healthcheck', 'local_wsmanager'),
            get_string('settings_healthcheck_desc', 'local_wsmanager')
        ));

        // Health check enabled.
        $settings->add(new admin_setting_configcheckbox(
            'local_wsmanager/healthcheck_enabled',
            get_string('healthcheck_enabled', 'local_wsmanager'),
            get_string('healthcheck_enabled_desc', 'local_wsmanager'),
            1
        ));

        // =====================================
        // TAB: Log Cleanup
        // =====================================
        $settings->add(new admin_setting_heading(
            'local_wsmanager/cleanup_heading',
            get_string('settings_cleanup', 'local_wsmanager'),
            get_string('settings_cleanup_desc', 'local_wsmanager')
        ));

        // Log cleanup enabled.
        $settings->add(new admin_setting_configcheckbox(
            'local_wsmanager/cleanup_enabled',
            get_string('cleanup_enabled', 'local_wsmanager'),
            get_string('cleanup_enabled_desc', 'local_wsmanager'),
            1
        ));

        // Log retention days.
        $settings->add(new admin_setting_configtext(
            'local_wsmanager/cleanup_retention_days',
            get_string('cleanup_retention_days', 'local_wsmanager'),
            get_string('cleanup_retention_days_desc', 'local_wsmanager'),
            30,
            PARAM_INT
        ));

        // =====================================
        // TAB: Version Retention
        // =====================================
        $settings->add(new admin_setting_heading(
            'local_wsmanager/version_retention_heading',
            get_string('settings_version_retention', 'local_wsmanager'),
            get_string('settings_version_retention_desc', 'local_wsmanager')
        ));

        // Version retention enabled.
        $settings->add(new admin_setting_configcheckbox(
            'local_wsmanager/version_retention_enabled',
            get_string('version_retention_enabled', 'local_wsmanager'),
            get_string('version_retention_enabled_desc', 'local_wsmanager'),
            1
        ));

        // Max versions per schema.
        $settings->add(new admin_setting_configtext(
            'local_wsmanager/version_retention_max',
            get_string('version_retention_max', 'local_wsmanager'),
            get_string('version_retention_max_desc', 'local_wsmanager'),
            10,
            PARAM_INT
        ));
    }

    $ADMIN->add('local_wsmanager_category', $settings);
}
