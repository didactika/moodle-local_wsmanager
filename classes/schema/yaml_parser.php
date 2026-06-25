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

/**
 * YAML parser for service schema definitions
 *
 * Uses PHP's native yaml_parse if available, otherwise falls back to
 * a simple custom parser for the supported YAML subset.
 *
 * @package    local_wsmanager
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class yaml_parser {
    /**
     * Parse YAML content string
     *
     * @param string $content YAML content
     * @return array|null Parsed data or null on failure
     * @throws \moodle_exception If YAML is invalid
     */
    public function parse(string $content): ?array {
        // Try native PHP yaml extension first.
        if (function_exists('yaml_parse')) {
            $data = @yaml_parse($content);
            if ($data === false) {
                throw new \moodle_exception('error_invalid_yaml', 'local_wsmanager', '', 'Unable to parse YAML');
            }
            return is_array($data) ? $data : null;
        }

        // Fallback to simple parser.
        try {
            $data = $this->simple_parse($content);
            return is_array($data) ? $data : null;
        } catch (\Exception $e) {
            throw new \moodle_exception('error_invalid_yaml', 'local_wsmanager', '', $e->getMessage());
        }
    }

    /**
     * Simple YAML parser for the subset we need
     *
     * Supports: key: value, nested objects, arrays with - prefix
     *
     * @param string $content YAML content
     * @return array Parsed data
     */
    protected function simple_parse(string $content): array {
        $lines = explode("\n", $content);
        $result = [];
        $stack = [&$result];
        // Metadata for every open container on $stack, kept in parallel.
        // type:        'map' | 'seq' | null   (null = not yet known)
        // childindent: indent at which this container's direct children sit (null until first child)
        // keyindent:   indent of the key that opened this container (-1 for the root).
        $frames = [['type' => 'map', 'childindent' => 0, 'keyindent' => -1]];

        foreach ($lines as $line) {
            // Skip empty lines and whole-line comments.
            $trimmed = trim($line);
            if ($trimmed === '' || strpos($trimmed, '#') === 0) {
                continue;
            }

            // Classify the line. A sequence item starts with "-"; a mapping key is
            // "<key>:" where the colon is followed by whitespace or end-of-line (so
            // values such as "moodle/role:assign" are NOT mistaken for keys).
            $isseq = preg_match('/^\s*-(\s|$)/', $line) === 1;
            $iskey = !$isseq && preg_match('/^\s*[^\s:#][^:]*:(\s|$)/', $line) === 1;
            if (!$isseq && !$iskey) {
                // Unsupported construct (block scalar, etc.); ignore.
                continue;
            }

            $indent = strlen($line) - strlen(ltrim($line));

            // Close any containers this line does not belong to. The rule mirrors
            // block YAML: a sequence may share its key's indent, a mapping key must
            // be deeper than its key, and the token type must match the container.
            while (count($frames) > 1) {
                $top = $frames[count($frames) - 1];
                if ($top['childindent'] === null) {
                    // Container opened but no child seen yet.
                    if ($isseq && $indent >= $top['keyindent']) {
                        break;
                    }
                    if ($iskey && $indent > $top['keyindent']) {
                        break;
                    }
                } else {
                    if ($indent > $top['childindent']) {
                        break;
                    }
                    if ($indent === $top['childindent']) {
                        if ($isseq && $top['type'] === 'seq') {
                            break;
                        }
                        if ($iskey && $top['type'] === 'map') {
                            break;
                        }
                    }
                }
                array_pop($stack);
                array_pop($frames);
            }

            $topidx = count($stack) - 1;
            $current = &$stack[$topidx];
            if (!is_array($current)) {
                $current = [];
            }

            // Resolve the container's type the first time a child is added to it.
            if ($frames[$topidx]['childindent'] === null) {
                $frames[$topidx]['childindent'] = $indent;
                $frames[$topidx]['type'] = $isseq ? 'seq' : 'map';
            }

            if ($isseq) {
                preg_match('/^(\s*-\s*)(.*)$/', $line, $sm);
                $value = rtrim($sm[2]);

                // Object in array (- key: value): open a map frame for further keys.
                if (preg_match('/^([^\s:#][^:]*):(?:\s+(.*))?$/', $value, $om)) {
                    $okey = rtrim($om[1]);
                    $oval = isset($om[2]) ? trim($om[2]) : '';
                    $innerindent = strlen($sm[1]);

                    $current[] = [$okey => $this->parse_value($oval)];
                    $last = array_key_last($current);
                    $stack[] = &$current[$last];
                    $frames[] = ['type' => 'map', 'childindent' => $innerindent, 'keyindent' => $innerindent];
                } else {
                    $current[] = $this->parse_value($value);
                }
                unset($current);
                continue;
            }

            // Mapping key.
            preg_match('/^\s*([^\s:#][^:]*):(?:\s+(.*))?$/', $line, $km);
            $key = rtrim($km[1]);
            $value = isset($km[2]) ? trim($km[2]) : '';

            if ($value === '[]') {
                $current[$key] = [];
            } else if ($value === '' || str_starts_with($value, '#')) {
                // Empty value (or a pure inline comment) means a nested map or sequence follows.
                $current[$key] = [];
                $stack[] = &$current[$key];
                $frames[] = ['type' => null, 'childindent' => null, 'keyindent' => $indent];
            } else {
                $current[$key] = $this->parse_value($value);
            }
            unset($current);
        }

        return $result;
    }

    /**
     * Parse a YAML value (string, number, boolean, null)
     *
     * @param string $value Raw value
     * @return mixed Parsed value
     */
    protected function parse_value(string $value) {
        $value = trim($value);

        // Handle quoted strings. Use strrpos so a trailing inline comment after the
        // closing quote (e.g. "value" # comment) doesn't break the match.
        if (str_starts_with($value, '"')) {
            $endquote = strrpos($value, '"');
            if ($endquote > 0) {
                return substr($value, 1, $endquote - 1);
            }
        }
        if (str_starts_with($value, "'")) {
            $endquote = strrpos($value, "'");
            if ($endquote > 0) {
                return substr($value, 1, $endquote - 1);
            }
        }

        // A value that is purely a comment (e.g. key: # note) means null.
        if (str_starts_with($value, '#')) {
            return null;
        }

        // Strip inline comments from unquoted values (e.g. "false  # comment" → "false").
        // Per YAML spec, a comment starts at ' #' (space followed by #).
        $commentpos = strpos($value, ' #');
        if ($commentpos !== false) {
            $value = trim(substr($value, 0, $commentpos));
        }

        // Handle booleans.
        $lower = strtolower($value);
        if ($lower === 'true' || $lower === 'yes') {
            return true;
        }
        if ($lower === 'false' || $lower === 'no') {
            return false;
        }

        // Handle null.
        if ($lower === 'null' || $lower === '~' || $value === '') {
            return null;
        }

        // Handle numbers.
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
        }

        return $value;
    }

    /**
     * Validate schema ID format (only letters, numbers, dots)
     *
     * @param string $id Schema ID
     * @return bool True if valid
     */
    public function validate_schema_id(string $id): bool {
        return preg_match('/^[a-zA-Z0-9.]+$/', $id) === 1;
    }

    /**
     * Convert schema_id to shortname format (dots to underscores)
     *
     * @param string $id Schema ID
     * @return string Shortname format
     */
    public function id_to_shortname(string $id): string {
        return str_replace('.', '_', $id);
    }

    /**
     * Validate the structure of parsed YAML data
     *
     * @param array $data Parsed YAML data
     * @return array Array of error strings (empty if valid)
     */
    public function validate_structure(array $data): array {
        $errors = [];

        // Check meta section.
        if (!isset($data['meta']) || !is_array($data['meta'])) {
            $errors[] = get_string('error_missing_meta', 'local_wsmanager');
            return $errors; // Can't continue without meta.
        }

        $meta = $data['meta'];

        // Check required meta fields.
        if (empty($meta['id'])) {
            $errors[] = get_string('error_missing_meta_id', 'local_wsmanager');
        } else if (!$this->validate_schema_id($meta['id'])) {
            $errors[] = get_string('error_invalid_schema_id', 'local_wsmanager', $meta['id']);
        } else if (\strlen($meta['id']) > 50) {
            $errors[] = get_string('error_schema_id_too_long', 'local_wsmanager', \strlen($meta['id']));
        }

        if (empty($meta['name'])) {
            $errors[] = get_string('error_missing_meta_name', 'local_wsmanager');
        }

        if (empty($meta['version'])) {
            $errors[] = get_string('error_missing_meta_version', 'local_wsmanager');
        }

        // Check definition section.
        if (!isset($data['definition']) || !is_array($data['definition'])) {
            $errors[] = get_string('error_missing_definition', 'local_wsmanager');
            return $errors;
        }

        $definition = $data['definition'];

        // Check functions array.
        if (!isset($definition['functions']) || !is_array($definition['functions']) || empty($definition['functions'])) {
            $errors[] = get_string('error_missing_functions', 'local_wsmanager');
        }

        return $errors;
    }

    /**
     * Get SHA256 hash of content for change detection
     *
     * @param string $content YAML content
     * @return string Hash
     */
    public function get_hash(string $content): string {
        return hash('sha256', $content);
    }

    /**
     * Extract meta information from parsed YAML
     *
     * @param array $data Parsed YAML data
     * @return array Meta information with defaults
     */
    public function extract_meta(array $data): array {
        $meta = $data['meta'] ?? [];
        return [
            'id' => $meta['id'] ?? '',
            'name' => $meta['name'] ?? '',
            'version' => $meta['version'] ?? '1.0.0',
            'maintainer' => $meta['maintainer'] ?? '',
            'description' => $meta['description'] ?? '',
        ];
    }

    /**
     * Extract functions from parsed YAML
     *
     * @param array $data Parsed YAML data
     * @return array Functions with name and critical flag
     */
    public function extract_functions(array $data): array {
        $functions = $data['definition']['functions'] ?? [];
        $result = [];

        foreach ($functions as $func) {
            if (is_string($func)) {
                $result[] = [
                    'name' => $func,
                    'critical' => true,
                ];
            } else if (is_array($func) && isset($func['name'])) {
                $result[] = [
                    'name' => $func['name'],
                    'critical' => $func['critical'] ?? true,
                ];
            }
        }

        return $result;
    }

    /**
     * Extract extra capabilities from parsed YAML
     *
     * @param array $data Parsed YAML data
     * @return array Extra capabilities
     */
    public function extract_extra_capabilities(array $data): array {
        return $data['definition']['extra_capabilities'] ?? [];
    }

    /**
     * Extract additional user emails from parsed YAML
     *
     * @param array $data Parsed YAML data
     * @return array Email addresses
     */
    public function extract_additional_users(array $data): array {
        return $data['definition']['additional_users'] ?? [];
    }

    /**
     * Extract required plugins from parsed YAML
     *
     * @param array $data Parsed YAML data
     * @return array Plugin names
     */
    public function extract_required_plugins(array $data): array {
        return $data['requirements']['plugins'] ?? [];
    }

    /**
     * Extract file transfer flags from the requirements section.
     * Defaults to false for any flag not explicitly set.
     *
     * @param array $data Parsed YAML data
     * @return array ['download_files' => bool, 'upload_files' => bool]
     */
    public function extract_service_settings(array $data): array {
        $requirements = $data['requirements'] ?? [];
        return [
            'download_files' => !empty($requirements['download_files']),
            'upload_files'   => !empty($requirements['upload_files']),
        ];
    }
}
