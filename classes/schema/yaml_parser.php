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
        $indentStack = [-1];

        foreach ($lines as $linenum => $line) {
            // Skip empty lines and comments.
            $trimmed = trim($line);
            if ($trimmed === '' || strpos($trimmed, '#') === 0) {
                continue;
            }

            // Calculate indent.
            $indent = strlen($line) - strlen(ltrim($line));

            // Pop stack for dedents.
            while (count($indentStack) > 1 && $indent <= end($indentStack)) {
                array_pop($stack);
                array_pop($indentStack);
            }

            // Handle array item.
            if (preg_match('/^(\s*)-\s*(.*)$/', $line, $matches)) {
                $value = trim($matches[2]);
                $current = &$stack[count($stack) - 1];

                if (!is_array($current)) {
                    $current = [];
                }

                // Check if it's an object in array (- name: value).
                if (preg_match('/^(\w+):\s*(.*)$/', $value, $kvmatch)) {
                    $obj = [$kvmatch[1] => $this->parse_value($kvmatch[2])];
                    $current[] = $obj;
                    $stack[] = &$current[count($current) - 1];
                    $indentStack[] = $indent;
                } else {
                    $current[] = $this->parse_value($value);
                }
                continue;
            }

            // Handle key: value.
            if (preg_match('/^(\s*)(\w+):\s*(.*)$/', $line, $matches)) {
                $key = $matches[2];
                $value = trim($matches[3]);
                $current = &$stack[count($stack) - 1];

                if (!is_array($current)) {
                    $current = [];
                }

                if ($value === '' || $value === '[]' || str_starts_with($value, '#')) {
                    // Empty value (or a pure inline comment) means nested object or array.
                    $current[$key] = ($value === '[]') ? [] : [];
                    $stack[] = &$current[$key];
                    $indentStack[] = $indent;
                } else {
                    $current[$key] = $this->parse_value($value);
                }
            }
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
        } elseif (!$this->validate_schema_id($meta['id'])) {
            $errors[] = get_string('error_invalid_schema_id', 'local_wsmanager', $meta['id']);
        } elseif (\strlen($meta['id']) > 50) {
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
            } elseif (is_array($func) && isset($func['name'])) {
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
