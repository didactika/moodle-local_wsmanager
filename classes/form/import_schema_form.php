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
 * Form for importing schemas.
 *
 * @package    local_wsmanager
 * @copyright  2026 Your Organization
 * @license    http://www.opensource.org/licenses/MIT MIT License
 */
class import_schema_form extends \moodleform {

    /**
     * Define form elements.
     */
    protected function definition() {
        $mform = $this->_form;

        // File upload.
        $mform->addElement(
            'filepicker',
            'importfile',
            get_string('import_file', 'local_wsmanager'),
            null,
            [
                'maxfiles' => 1,
                'accepted_types' => ['.yaml', '.yml', '.zip'],
            ]
        );
        $mform->addRule('importfile', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('importfile', 'import_file', 'local_wsmanager');

        // Conflict handling.
        $mform->addElement('header', 'conflicthandling', get_string('conflict_handling', 'local_wsmanager'));

        $conflictoptions = [
            'skip' => get_string('conflict_skip', 'local_wsmanager'),
            'overwrite' => get_string('conflict_overwrite', 'local_wsmanager'),
            'rename' => get_string('conflict_rename', 'local_wsmanager'),
        ];
        $mform->addElement('select', 'conflict_action', get_string('conflict_action', 'local_wsmanager'), $conflictoptions);
        $mform->setDefault('conflict_action', 'skip');
        $mform->addHelpButton('conflict_action', 'conflict_action', 'local_wsmanager');

        // Submit buttons.
        $this->add_action_buttons(true, get_string('import', 'local_wsmanager'));
    }

    /**
     * Validate the uploaded file.
     *
     * @param array $data Form data.
     * @param array $files Files.
     * @return array Errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $draftitemid = $data['importfile'];
        $fs = get_file_storage();
        $context = \context_user::instance($GLOBALS['USER']->id);
        $files = $fs->get_area_files($context->id, 'user', 'draft', $draftitemid, '', false);

        if (empty($files)) {
            $errors['importfile'] = get_string('required');
        }

        return $errors;
    }
}
