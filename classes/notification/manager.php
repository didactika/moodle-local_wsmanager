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

namespace local_wsmanager\notification;

/**
 * Notification manager for health alerts
 *
 * @package    local_wsmanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /**
     * Send daily report of health issues (and optionally healthy status)
     *
     * @param array $issues Array of issues with 'schema' and 'result'
     * @param array $healthyschemas Array of healthy schemas
     */
    public function send_daily_report(array $issues, array $healthyschemas = []): void {
        global $CFG, $OUTPUT;

        $recipients = $this->get_notification_recipients();
        if (empty($recipients)) {
            return;
        }

        $site = get_site();
        $hasissues = !empty($issues);

        // Prepare context for template.
        $context = [
            'site_name' => $site->fullname,
            'site_url' => $CFG->wwwroot,
            'report_date' => userdate(time()),
            'dashboard_url' => (new \moodle_url('/local/wsmanager/pages/dashboard.php'))->out(false),
            'has_issues' => $hasissues,
            'has_healthy' => !empty($healthyschemas),
        ];

        // Get Branding.
        try {
            $themename = $CFG->theme;
            
            // Try to get logo URL manually since theme_config::get_logo_url might not exist.
            $logourl = $this->get_theme_logo_url($themename);
            if ($logourl) {
                $context['logo_url'] = $logourl;
            }

            // Try to get brand color.
            $brandcolor = get_config('theme_' . $themename, 'brandcolor');
            if ($brandcolor) {
                $context['primary_color'] = $brandcolor;
            } else {
                $context['primary_color'] = '#0f6674'; // Fallback Moodle Teal.
            }
        } catch (\Exception $e) {
            // Fallback if branding fails.
            $context['primary_color'] = '#0f6674'; 
        }

        // Determine overall status.
        if ($hasissues) {
            $context['status_class'] = 'danger';
            $context['status_text'] = get_string('healthcheck_issues_found', 'local_wsmanager', count($issues));
            $context['summary_text'] = get_string('healthcheck_issues_summary', 'local_wsmanager');
        } else {
            $context['status_class'] = 'success';
            $context['status_text'] = get_string('healthcheck_all_healthy', 'local_wsmanager');
            $context['summary_text'] = get_string('healthcheck_healthy_summary', 'local_wsmanager');
        }

        // Process issues data.
        $issuesdata = [];
        foreach ($issues as $issue) {
            $schema = $issue['schema'];
            $result = $issue['result'];
            $msgs = explode('; ', $result['message']);
            
            $issuesdata[] = [
                'name' => $schema->name,
                'schema_id' => $schema->schema_id,
                'status' => $result['status'], // 'warning' or 'critical'
                'status_label' => strtoupper($result['status']),
                'messages' => $msgs,
                'view_url' => (new \moodle_url('/local/wsmanager/pages/view.php', ['id' => $schema->id]))->out(false),
            ];
        }
        $context['issues'] = $issuesdata;

        // Process healthy schemas.
        $healthydata = [];
        foreach ($healthyschemas as $schema) {
            $healthydata[] = [
                'name' => $schema->name,
                'schema_id' => $schema->schema_id,
            ];
        }
        $context['healthy_schemas'] = $healthydata;

        // Render email content.
        $htmlbody = $OUTPUT->render_from_template('local_wsmanager/email_health_report', $context);
        $textbody = strip_tags($htmlbody); // Fallback text.
        $subject = 'Campus ' . $site->shortname . ': ' . get_string('healthcheck_report_subject', 'local_wsmanager');

        $noreplyuser = \core_user::get_noreply_user();

        // Send to all recipients.
        foreach ($recipients as $recipient) {
            if ($recipient->id == -1) {
                // External email.
                email_to_user($recipient, $noreplyuser, $subject, $textbody, $htmlbody);
            } else {
                // Moodle user.
                email_to_user($recipient, $noreplyuser, $subject, $textbody, $htmlbody);
            }
        }
    }

    /**
     * Send critical alert for a specific schema
     *
     * @param \stdClass $schema Schema record
     * @param string $message Alert message
     */
    public function send_critical_alert(\stdClass $schema, string $message): void {
        global $CFG;
        
        $recipients = $this->get_notification_recipients();
        if (empty($recipients)) {
            return;
        }

        $subject = '[CRITICAL] Service Schema: ' . $schema->name;
        $body = "Critical issue detected with schema '{$schema->name}':\n\n{$message}";
        $htmlbody = nl2br(s($body)) . '<br><br><a href="'.$CFG->wwwroot.'/local/wsmanager/pages/view.php?id='.$schema->id.'">View Schema</a>';

        $noreplyuser = \core_user::get_noreply_user();

        foreach ($recipients as $recipient) {
             email_to_user($recipient, $noreplyuser, $subject, $body, $htmlbody);
        }
    }

    /**
     * Build report body from issues
     *
     * @deprecated Use template instead.
     * @param array $issues Array of issues
     * @return string
     */
    protected function build_report_body(array $issues): string {
        return '';
    }

    /**
     * Get recipients based on settings
     *
     * Uses notification_emails from settings and optionally site admins
     *
     * @return array User objects
     */
    protected function get_notification_recipients(): array {
        global $DB;

        $recipients = [];
        $addedemails = [];

        // Get configured emails.
        $emailsconfig = get_config('local_wsmanager', 'notification_emails');
        if (!empty($emailsconfig)) {
            $emails = array_map('trim', explode(',', $emailsconfig));
            foreach ($emails as $email) {
                if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    if (in_array($email, $addedemails)) {
                        continue;
                    }
                    
                    $user = $DB->get_record('user', ['email' => $email, 'deleted' => 0, 'suspended' => 0]);
                    if ($user) {
                        $recipients[$user->id] = $user;
                        $addedemails[] = $user->email;
                    } else {
                        // Create a dummy user for email-only notification.
                        $dummyuser = new \stdClass();
                        $dummyuser->id = -1; // Marker ID.
                        $dummyuser->email = $email;
                        $dummyuser->firstname = 'External';
                        $dummyuser->lastname = 'Recipient';
                        $dummyuser->mailformat = 1;
                        $dummyuser->mnethostid = $CFG->mnet_localhost_id ?? 1;
                        
                        // We use the email as key to avoid duplicates if mixed with real users.
                        $recipients['external_' . $email] = $dummyuser;
                        $addedemails[] = $email;
                    }
                }
            }
        }

        // Add site admins if configured.
        $notifyadmins = get_config('local_wsmanager', 'notify_admins');
        if ($notifyadmins === false || $notifyadmins) { // Default to true.
            $admins = get_admins();
            foreach ($admins as $admin) {
                if (!in_array($admin->email, $addedemails)) {
                    $recipients[$admin->id] = $admin;
                    $addedemails[] = $admin->email;
                }
            }
        }

        return $recipients;
    }

    /**
     * Send email directly to external address
     *
     * @deprecated Recipient handling is now unified in send_daily_report
     * @param \stdClass $recipient Recipient with email
     * @param string $subject Subject
     * @param string $body Body
     */
    protected function send_email_directly(\stdClass $recipient, string $subject, string $body): void {
        // Deprecated.
    }

    /**
     * Send a message to a user
     *
     * @deprecated Recipient handling is now unified in send_daily_report using email_to_user directly
     * @param \stdClass $user User object
     * @param string $subject Subject
     * @param string $body Message body
     */
    protected function send_message(\stdClass $user, string $subject, string $body): void {
        // Deprecated.
    }

    /**
     * Get theme logo URL safely
     *
     * @param string $themename Theme name (unused, kept for compatibility)
     * @return string|null URL or null
     */
    protected function get_theme_logo_url(string $themename): ?string {
        global $CFG;
        
        // Moodle stores logos in core_admin, not in the theme.
        $logo = get_config('core_admin', 'logo');
        if (empty($logo)) {
            return null;
        }
        
        // Build URL matching core's approach.
        $syscontext = \context_system::instance();
        $filepath = '200x200/'; // Standard size.
        
        $url = \moodle_url::make_pluginfile_url(
            $syscontext->id,
            'core_admin',
            'logo',
            $filepath,
            theme_get_revision(),
            $logo
        );
        
        return $url->out(false);
    }
}
