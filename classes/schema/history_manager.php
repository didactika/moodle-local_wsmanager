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

namespace local_wsmanager\schema;

defined('MOODLE_INTERNAL') || die();

/**
 * History manager for schema versioning and rollback.
 *
 * @package    local_wsmanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class history_manager {

    /** @var string Table name for history */
    const TABLE = 'local_wsmanager_history';

    /**
     * Save a version snapshot of the schema.
     *
     * @param int $schemaid Schema ID.
     * @param string $version Version string.
     * @param string $yamlcontent YAML content.
     * @param string|null $reason Change reason.
     * @return int The history record ID.
     */
    public function save_version(int $schemaid, string $version, string $yamlcontent, ?string $reason = null): int {
        global $DB, $USER;

        $record = new \stdClass();
        $record->schemaid = $schemaid;
        $record->version = $version;
        $record->yaml_content = $yamlcontent;
        $record->yaml_hash = hash('sha256', $yamlcontent);
        $record->change_reason = $reason;
        $record->changedby = $USER->id;
        $record->timecreated = time();

        return $DB->insert_record(self::TABLE, $record);
    }

    /**
     * Check if a version already exists for this schema.
     *
     * @param int $schemaid Schema ID.
     * @param string $version Version string.
     * @return bool True if exists.
     */
    public function version_exists(int $schemaid, string $version): bool {
        global $DB;
        return $DB->record_exists(self::TABLE, ['schemaid' => $schemaid, 'version' => $version]);
    }

    /**
     * Get version history for a schema.
     *
     * @param int $schemaid Schema ID.
     * @param int $limit Maximum number of records.
     * @return array Array of history records.
     */
    public function get_history(int $schemaid, int $limit = 50): array {
        global $DB;

        $sql = "SELECT h.*, u.firstname, u.lastname, u.middlename, u.alternatename, u.firstnamephonetic, u.lastnamephonetic, u.picture, u.email, u.imagealt
                FROM {" . self::TABLE . "} h
                JOIN {user} u ON u.id = h.changedby
                WHERE h.schemaid = :schemaid
                ORDER BY h.timecreated DESC";

        return $DB->get_records_sql($sql, ['schemaid' => $schemaid], 0, $limit);
    }

    /**
     * Get a specific version record.
     *
     * @param int $historyid History record ID.
     * @return \stdClass|false The history record or false.
     */
    public function get_version(int $historyid) {
        global $DB;

        return $DB->get_record(self::TABLE, ['id' => $historyid]);
    }

    /**
     * Rollback schema to a previous version.
     *
     * @param int $schemaid Schema ID.
     * @param int $historyid History record ID to rollback to.
     * @return bool Success.
     * @throws \moodle_exception If history record not found.
     */
    public function rollback(int $schemaid, int $historyid): bool {
        global $DB;

        $history = $this->get_version($historyid);
        if (!$history || $history->schemaid != $schemaid) {
            throw new \moodle_exception('historynotfound', 'local_wsmanager');
        }

        $manager = new manager();

        // Save current state before rollback ONLY if it differs from the last history entry.
        // This prevents "Backup before rollback" if we are already sitting on a known history state.
        $current = $DB->get_record('local_wsmanager_schemas', ['id' => $schemaid]);
        if ($current) {
            $parser = new yaml_parser();
            $currenthash = $parser->get_hash($current->yaml_content);
            $latest = $this->get_history($schemaid, 1);
            $latesthash = (!empty($latest)) ? reset($latest)->yaml_hash : '';

            // If the current content is DIFFERENT from the latest history entry, save it.
            if ($currenthash !== $latesthash) {
                $this->save_version(
                    $schemaid,
                    $current->version,
                    $current->yaml_content,
                    get_string('rollback_backup', 'local_wsmanager')
                );
            }
        }

        // Update schema with historical content (skip version validation for rollback).
        $manager->update_schema($schemaid, $history->yaml_content, true);

        // DO NOT log a new history entry for the rollback result itself.
        // Ideally, we just moved the state to a previous point.
        // The "Backup" preserves the state we left.
        // The old history entry still exists.
        // Users prefer not to see "Restored to X" as a new top entry if X is already in history.
        // However, if we don't log it, the "Current" state on dashboard matches the OLD history entry, but
        // there is no record of "Action: Rollback performed at [time]".
        // But user requested "si restauro no se creen copias de una version".
        // Use a lightweight record? No, let's skip the new entry as per request.

        return true;
    }

    /**
     * Compare two versions and return diff.
     *
     * @param int $historyid1 First history ID.
     * @param int $historyid2 Second history ID.
     * @return array Array with 'lines1', 'lines2', and 'diff'.
     */
    public function compare_versions(int $historyid1, int $historyid2): array {
        $v1 = $this->get_version($historyid1);
        $v2 = $this->get_version($historyid2);

        if (!$v1 || !$v2) {
            return ['lines1' => [], 'lines2' => [], 'diff' => []];
        }

        $lines1 = explode("\n", $v1->yaml_content);
        $lines2 = explode("\n", $v2->yaml_content);

        // Simple line-by-line diff.
        $max = max(count($lines1), count($lines2));
        $diff = [];

        for ($i = 0; $i < $max; $i++) {
            $l1 = $lines1[$i] ?? null;
            $l2 = $lines2[$i] ?? null;

            if ($l1 === $l2) {
                // Unchanged.
                $diff[] = ['type' => 'unchanged', 'content' => $l1];
            } elseif ($l1 !== null && $l2 === null) {
                // Deleted.
                $diff[] = ['type' => 'deleted', 'content' => $l1];
            } elseif ($l1 === null && $l2 !== null) {
                // Added.
                $diff[] = ['type' => 'added', 'content' => $l2];
            } else {
                // Modified (show as deleted then added for simplicity in side-by-side).
                $diff[] = ['type' => 'modified_deleted', 'content' => $l1];
                $diff[] = ['type' => 'modified_added', 'content' => $l2];
            }
        }

        return ['lines1' => $lines1, 'lines2' => $lines2, 'diff' => $diff];
    }


    /**
     * Delete history for a schema.
     *
     * @param int $schemaid Schema ID.
     * @return bool Success.
     */
    public function delete_schema_history(int $schemaid): bool {
        global $DB;

        return $DB->delete_records(self::TABLE, ['schemaid' => $schemaid]);
    }

    /**
     * Get the count of versions for a schema.
     *
     * @param int $schemaid Schema ID.
     * @return int Count of versions.
     */
    public function get_version_count(int $schemaid): int {
        global $DB;

        return $DB->count_records(self::TABLE, ['schemaid' => $schemaid]);
    }

    /**
     * Get version history with pagination and optional filters.
     * Groups records by version, returning only the most recent record per version.
     *
     * @param int $schemaid Schema ID.
     * @param int $page Current page (0-indexed).
     * @param int $perpage Items per page.
     * @param array $filters Optional filters: 'version', 'datefrom', 'dateto'.
     * @return array Array of history records.
     */
    public function get_history_paginated(int $schemaid, int $page = 0, int $perpage = 10, array $filters = []): array {
        global $DB;

        $conditions = ['h.schemaid = :schemaid'];
        $params = ['schemaid' => $schemaid];

        if (!empty($filters['version'])) {
            $conditions[] = $DB->sql_like('h.version', ':version', false);
            $params['version'] = '%' . $DB->sql_like_escape($filters['version']) . '%';
        }

        if (!empty($filters['datefrom'])) {
            $conditions[] = 'h.timecreated >= :datefrom';
            $params['datefrom'] = $filters['datefrom'];
        }

        if (!empty($filters['dateto'])) {
            $conditions[] = 'h.timecreated <= :dateto';
            $params['dateto'] = $filters['dateto'] + 86400;
        }

        $where = implode(' AND ', $conditions);

        // Get the most recent record for each version by using a subquery to get max id per version.
        $sql = "SELECT h.*, u.firstname, u.lastname, u.middlename, u.alternatename, u.firstnamephonetic, u.lastnamephonetic, u.picture, u.email, u.imagealt
                FROM {" . self::TABLE . "} h
                JOIN {user} u ON u.id = h.changedby
                WHERE " . $where . "
                AND h.id IN (
                    SELECT MIN(h2.id)
                    FROM {" . self::TABLE . "} h2
                    WHERE h2.schemaid = :schemaid2
                    GROUP BY h2.version
                )
                ORDER BY h.timecreated DESC";
        
        $params['schemaid2'] = $schemaid;

        return $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);
    }

    /**
     * Count history records with filters.
     *
     * @param int $schemaid Schema ID.
     * @param array $filters Optional filters.
     * @return int Total count.
     */
    public function count_history(int $schemaid, array $filters = []): int {
        global $DB;

        $conditions = ['schemaid = :schemaid'];
        $params = ['schemaid' => $schemaid];

        if (!empty($filters['version'])) {
            $conditions[] = $DB->sql_like('version', ':version', false);
            $params['version'] = '%' . $DB->sql_like_escape($filters['version']) . '%';
        }

        if (!empty($filters['datefrom'])) {
            $conditions[] = 'timecreated >= :datefrom';
            $params['datefrom'] = $filters['datefrom'];
        }

        if (!empty($filters['dateto'])) {
            $conditions[] = 'timecreated <= :dateto';
            $params['dateto'] = $filters['dateto'] + 86400;
        }

        $where = implode(' AND ', $conditions);

        // Count distinct versions (to match grouped query behavior).
        return $DB->count_records_sql(
            "SELECT COUNT(DISTINCT version) FROM {" . self::TABLE . "} WHERE " . $where,
            $params
        );
    }
}
