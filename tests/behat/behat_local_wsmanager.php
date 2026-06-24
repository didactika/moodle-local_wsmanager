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
 * Behat context for local_wsmanager
 *
 * @package    local_wsmanager
 * @category   test
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode;

/**
 * Local wsmanager behat context class.
 */
class behat_local_wsmanager extends behat_base {

    /**
     * Create a schema for testing.
     *
     * @Given I have uploaded a schema with id :schemaid
     * @param string $schemaid The schema ID.
     */
    public function i_have_uploaded_a_schema_with_id($schemaid) {
        global $DB;

        $yaml = <<<YAML
meta:
  id: "{$schemaid}"
  name: "Test Service"
  version: "1.0.0"
definition:
  functions:
    - core_webservice_get_site_info
YAML;

        $manager = new \local_wsmanager\schema\manager();
        $manager->create_schema($yaml, false);
    }

    /**
     * Create multiple schemas for testing.
     *
     * @Given the following schemas exist:
     * @param TableNode $data
     */
    public function the_following_schemas_exist(TableNode $data) {
        $manager = new \local_wsmanager\schema\manager();

        foreach ($data->getHash() as $row) {
            $yaml = <<<YAML
meta:
  id: "{$row['id']}"
  name: "{$row['name']}"
  version: "{$row['version']}"
definition:
  functions:
    - core_webservice_get_site_info
YAML;
            $manager->create_schema($yaml, false);
        }
    }

    /**
     * Check schema exists with given ID.
     *
     * @Then a schema with id :schemaid should exist
     * @param string $schemaid
     */
    public function a_schema_with_id_should_exist($schemaid) {
        global $DB;

        $exists = $DB->record_exists('local_wsmanager_schemas', ['schema_id' => $schemaid]);
        if (!$exists) {
            throw new Exception("Schema with ID '{$schemaid}' does not exist");
        }
    }

    /**
     * Check schema does not exist with given ID.
     *
     * @Then a schema with id :schemaid should not exist
     * @param string $schemaid
     */
    public function a_schema_with_id_should_not_exist($schemaid) {
        global $DB;

        $exists = $DB->record_exists('local_wsmanager_schemas', ['schema_id' => $schemaid]);
        if ($exists) {
            throw new Exception("Schema with ID '{$schemaid}' still exists");
        }
    }
}
