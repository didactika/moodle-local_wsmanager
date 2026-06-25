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

namespace local_servicemanager\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for uploading a new YAML schema
 *
 * @package    local_servicemanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_schema_form extends \moodleform {
    /**
     * Form definition
     */
    protected function definition() {
        $mform = $this->_form;

        // Documentation link banner.
        $docurl = new \moodle_url('/local/servicemanager/pages/documentation.php');
        $doclink = \html_writer::link(
            $docurl,
            \html_writer::tag('i', '', ['class' => 'fa fa-book mr-2']) .
            get_string('view_documentation', 'local_servicemanager'),
            ['class' => 'text-primary font-weight-bold']
        );
        $dochtml = \html_writer::div(
            \html_writer::tag('i', '', ['class' => 'fa fa-info-circle mr-2']) .
            get_string('view_documentation_desc', 'local_servicemanager') . ' ' . $doclink,
            'alert alert-info d-flex align-items-center'
        );
        $mform->addElement('html', $dochtml);

        // File picker for YAML.
        $mform->addElement(
            'filepicker',
            'yamlfile',
            get_string('yamlfile', 'local_servicemanager'),
            null,
            ['accepted_types' => ['.yaml', '.yml']]
        );
        $mform->addRule('yamlfile', null, 'required');
        $mform->addHelpButton('yamlfile', 'yamlfile', 'local_servicemanager');

        // Checkbox for generating token automatically.
        $mform->addElement(
            'advcheckbox',
            'generatetoken',
            get_string('generatetoken', 'local_servicemanager'),
            get_string('generatetoken_desc', 'local_servicemanager')
        );
        $mform->setDefault('generatetoken', 1);

        // Action buttons.
        $this->add_action_buttons(true, get_string('upload', 'local_servicemanager'));
    }

    /**
     * Validation
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Get the file content and validate YAML.
        $content = $this->get_yaml_file_content('yamlfile');
        if ($content) {
            $validator = new \local_servicemanager\schema\validator();
            $result = $validator->validate_content($content);
            if (!empty($result['errors'])) {
                $errors['yamlfile'] = implode('<br>', $result['errors']);
            }
        }

        return $errors;
    }

    /**
     * Get uploaded file content
     *
     * @param string $element Element name
     * @return string|null
     */
    public function get_yaml_file_content(string $element): ?string {
        $files = $this->get_yaml_draft_files($element);
        if (empty($files)) {
            return null;
        }

        $file = reset($files);
        return $file->get_content();
    }

    /**
     * Get draft files from file picker
     *
     * @param string $element Element name
     * @return array
     */
    protected function get_yaml_draft_files(string $element): array {
        global $USER;

        $draftitemid = $this->_form->getSubmitValue($element);
        if (!$draftitemid) {
            return [];
        }

        $fs = get_file_storage();
        $context = \context_user::instance($USER->id);

        $files = $fs->get_area_files(
            $context->id,
            'user',
            'draft',
            $draftitemid,
            'id DESC',
            false
        );

        return $files;
    }
}
