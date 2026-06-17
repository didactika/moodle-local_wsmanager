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
 * Data generator for local_wsmanager.
 *
 * @package    local_wsmanager
 * @category   test
 * @copyright  2026 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Data generator class for local_wsmanager.
 */
class local_wsmanager_generator extends testing_module_generator {

    /** @var int Counter for unique schema IDs */
    protected $schemacount = 0;

    /**
     * Reset the generator state.
     */
    public function reset() {
        $this->schemacount = 0;
        parent::reset();
    }

    /**
     * Create a test schema.
     *
     * @param array $record Data for the schema.
     * @return stdClass The created schema record.
     */
    public function create_schema($record = null) {
        global $DB;

        $this->schemacount++;

        $defaults = [
            'schema_id' => 'test.service.' . $this->schemacount,
            'name' => 'Test Service ' . $this->schemacount,
            'version' => '1.0.0',
            'enabled' => 1,
            'status' => 'healthy',
        ];

        $record = (object) array_merge($defaults, (array) $record);

        // Generate YAML content.
        $yamlcontent = <<<YAML
meta:
  id: "{$record->schema_id}"
  name: "{$record->name}"
  version: "{$record->version}"
definition:
  functions:
    - core_webservice_get_site_info
YAML;

        $record->yaml_content = $yamlcontent;
        $record->timecreated = time();
        $record->timemodified = time();

        $record->id = $DB->insert_record('local_wsmanager', $record);

        return $record;
    }

    /**
     * Create a schema from YAML content.
     *
     * @param string $yaml YAML content.
     * @param bool $generatetoken Whether to generate a token.
     * @return array Result with id and optional token.
     */
    public function create_schema_from_yaml($yaml, $generatetoken = false) {
        $manager = new \local_wsmanager\schema\manager();
        return $manager->create_from_yaml($yaml, $generatetoken);
    }
}
