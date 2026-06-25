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

namespace local_servicemanager\automation;

/**
 * Manager for web service tokens
 *
 * @package    local_servicemanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class token_manager {
    /**
     * Generate a token for user and service
     *
     * name: Token - {meta.name}
     *
     * @param int $userid User ID
     * @param int $serviceid Service ID
     * @param string $metaname Display name from meta.name
     * @return array ['tokenid' => int, 'token' => string]
     */
    public function generate_token(int $userid, int $serviceid, string $metaname): array {
        global $DB, $USER;

        // Generate unique token.
        $token = md5(uniqid(rand(), true));

        $record = new \stdClass();
        $record->token = $token;
        $record->tokentype = EXTERNAL_TOKEN_PERMANENT;
        $record->userid = $userid;
        $record->externalserviceid = $serviceid;
        $record->contextid = \context_system::instance()->id;
        $record->creatorid = $USER->id ?? 0;
        $record->timecreated = time();
        $record->validuntil = 0; // No expiry.
        $record->iprestriction = null;
        $record->name = 'Token - ' . $metaname;
        $record->privatetoken = random_string(64);

        $tokenid = $DB->insert_record('external_tokens', $record);

        return [
            'tokenid' => $tokenid,
            'token' => $token,
        ];
    }

    /**
     * Regenerate token (delete old, create new)
     *
     * @param int $schemaid Schema ID
     * @return array ['tokenid' => int, 'token' => string]
     */
    public function regenerate_token(int $schemaid): array {
        global $DB;

        $schema = $DB->get_record('local_servicemanager_schemas', ['id' => $schemaid]);
        if (!$schema) {
            throw new \moodle_exception('Schema not found');
        }

        // Delete old token.
        if ($schema->tokenid) {
            $this->delete_token($schema->tokenid);
        }

        // Get meta name from YAML.
        $parser = new \local_servicemanager\schema\yaml_parser();
        $yamldata = $parser->parse($schema->yaml_content);
        $metaname = $yamldata['meta']['name'] ?? $schema->name;

        // Generate new token.
        $result = $this->generate_token($schema->userid, $schema->serviceid, $metaname);

        // Update schema with new token id.
        $DB->set_field('local_servicemanager_schemas', 'tokenid', $result['tokenid'], ['id' => $schemaid]);
        $DB->set_field('local_servicemanager_schemas', 'timemodified', time(), ['id' => $schemaid]);

        return $result;
    }

    /**
     * Delete a token
     *
     * @param int $tokenid Token ID
     * @return bool
     */
    public function delete_token(int $tokenid): bool {
        global $DB;
        return $DB->delete_records('external_tokens', ['id' => $tokenid]);
    }

    /**
     * Get token value (for display after generation)
     *
     * @param int $tokenid Token ID
     * @return string|null
     */
    public function get_token_value(int $tokenid): ?string {
        global $DB;
        return $DB->get_field('external_tokens', 'token', ['id' => $tokenid]) ?: null;
    }

    /**
     * Get token record
     *
     * @param int $tokenid Token ID
     * @return \stdClass|null
     */
    public function get_token(int $tokenid): ?\stdClass {
        global $DB;
        return $DB->get_record('external_tokens', ['id' => $tokenid]) ?: null;
    }

    /**
     * Check if token is valid
     *
     * @param int $tokenid Token ID
     * @return bool
     */
    public function is_token_valid(int $tokenid): bool {
        global $DB;

        $token = $DB->get_record('external_tokens', ['id' => $tokenid]);
        if (!$token) {
            return false;
        }

        // Check expiry.
        if ($token->validuntil && $token->validuntil < time()) {
            return false;
        }

        return true;
    }

    /**
     * Check if token exists
     *
     * @param int $tokenid Token ID
     * @return bool
     */
    public function token_exists(int $tokenid): bool {
        global $DB;
        return $DB->record_exists('external_tokens', ['id' => $tokenid]);
    }

    /**
     * Update a token to point to a different external service.
     *
     * Used when a service is recreated and the existing token should carry over.
     *
     * @param int $tokenid Token ID
     * @param int $serviceid New service ID
     * @return bool
     */
    public function reattach_token(int $tokenid, int $serviceid): bool {
        global $DB;
        return $DB->set_field('external_tokens', 'externalserviceid', $serviceid, ['id' => $tokenid]);
    }

    /**
     * Get last access time for token
     *
     * @param int $tokenid Token ID
     * @return int|null Timestamp or null if never accessed
     */
    public function get_last_access(int $tokenid): ?int {
        global $DB;
        $lastaccess = $DB->get_field('external_tokens', 'lastaccess', ['id' => $tokenid]);
        return $lastaccess ?: null;
    }
}
