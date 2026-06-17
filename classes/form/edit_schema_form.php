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

namespace local_wsmanager\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for editing an existing YAML schema
 *
 * @package    local_wsmanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_schema_form extends \moodleform {

    /**
     * Form definition
     */
    protected function definition() {
        $mform = $this->_form;

        // Documentation link banner.
        $docurl = new \moodle_url('/local/wsmanager/pages/documentation.php');
        $doclink = \html_writer::link(
            $docurl,
            \html_writer::tag('i', '', ['class' => 'fa fa-book mr-2']) . 
            get_string('view_documentation', 'local_wsmanager'),
            ['class' => 'text-primary font-weight-bold ml-2']
        );
        $dochtml = \html_writer::div(
            \html_writer::tag('i', '', ['class' => 'fa fa-info-circle mr-2']) .
            get_string('view_documentation_desc', 'local_wsmanager') . ' ' . $doclink,
            'alert alert-info d-flex align-items-center'
        );
        $mform->addElement('html', $dochtml);

        // Hidden ID.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Schema info header.
        $mform->addElement('header', 'schemainfo', get_string('schema_information', 'local_wsmanager'));

        // Read-only schema ID display.
        if (!empty($this->_customdata['schema'])) {
            $schema = $this->_customdata['schema'];
            $mform->addElement('static', 'schema_id_display',
                get_string('schema_id', 'local_wsmanager'),
                $schema->schema_id
            );
            $mform->addElement('static', 'schema_version_display',
                get_string('schema_version', 'local_wsmanager'),
                $schema->version
            );
            $mform->addElement('advcheckbox', 'enabled',
                get_string('schema_enabled', 'local_wsmanager'),
                '',
                null,
                [0, 1]
            );
        }

        // YAML content editor.
        $mform->addElement('header', 'yamlheader', get_string('yamlcontent', 'local_wsmanager'));

        $mform->addElement(
            'textarea',
            'yaml_content',
            get_string('yamlcontent', 'local_wsmanager'),
            ['rows' => 30, 'cols' => 100, 'style' => 'font-family: monospace; width: 100%;']
        );
        $mform->addRule('yaml_content', null, 'required');
        $mform->addHelpButton('yaml_content', 'yamlcontent', 'local_wsmanager');

        // Info about changes.
        $mform->addElement('static', 'changes_info', '',
            '<div class="alert alert-info">' .
            get_string('changes_will_apply', 'local_wsmanager') .
            '</div>'
        );

        // Action buttons.
        $this->add_action_buttons(true, get_string('savechanges'));
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

        if (!empty($data['yaml_content'])) {
            $validator = new \local_wsmanager\schema\validator();
            $result = $validator->validate_content($data['yaml_content'], $data['id'] ?? null);
            if (!empty($result['errors'])) {
                $errors['yaml_content'] = implode('<br>', $result['errors']);
            }
        }

        return $errors;
    }
}
