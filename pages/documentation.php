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
 * Schema documentation page
 *
 * @package    local_servicemanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

require_login();
require_capability('local/servicemanager:view', context_system::instance());

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

$PAGE->set_url(new moodle_url('/local/servicemanager/pages/documentation.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_servicemanager') . ' - ' . get_string('documentation', 'local_servicemanager'));
$PAGE->set_heading(get_string('documentation', 'local_servicemanager'));
$PAGE->set_pagelayout('admin');

// Navigation.
$PAGE->navbar->add(get_string('pluginname', 'local_servicemanager'), new moodle_url('/local/servicemanager/pages/dashboard.php'));
$PAGE->navbar->add(get_string('documentation', 'local_servicemanager'));

echo $OUTPUT->header();

// Back button URL.
$backurl = !empty($returnurl) ? $returnurl : new moodle_url('/local/servicemanager/pages/dashboard.php');
$downloadurl = new moodle_url('/local/servicemanager/pages/download_example.php');

// Define parameters for template.
$structurecode = <<<'YAML'
meta:
  id: "example.service"           # Required: Unique identifier
  name: "Example Service"         # Required: Display name
  version: "1.0.0"               # Required: Version number
  maintainer: "Your Name"         # Optional: Maintainer
  description: "Description"      # Optional: Description

requirements:                     # Optional
  plugins:
    - mod_forum
  download_files: false           # Allow file downloads (default: false)
  upload_files: false             # Allow file uploads (default: false)

definition:
  functions:                      # Required: Web service functions
    - core_user_get_users
    - name: core_course_get_courses
      critical: true
  
  extra_capabilities:             # Optional: Additional capabilities
    - moodle/user:viewdetails
  
  additional_users:               # Optional: Users to authorize
    - admin@example.com
YAML;

$funccode = <<<'YAML'
# Simple format (critical: true by default)
functions:
  - core_user_get_users
  - core_course_get_courses

# Extended format
functions:
  - name: core_user_get_users
    critical: true    # Blocks creation if missing
  - name: mod_forum_get_forums
    critical: false   # Warning only if missing
YAML;

$context = [
    'backurl' => $backurl->out(false),
    'downloadurl' => $downloadurl->out(false),
    'structure_code' => $structurecode,
    'function_code' => $funccode,
];

echo $OUTPUT->render_from_template('local_servicemanager/documentation_page', $context);

echo $OUTPUT->footer();
