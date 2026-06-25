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
 * Web service function and service definitions for local_servicemanager
 *
 * @package    local_servicemanager
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_servicemanager_get_schemas' => [
        'classname' => 'local_servicemanager_external',
        'methodname' => 'get_schemas',
        'classpath' => 'local/servicemanager/externallib.php',
        'description' => 'Get all service schemas.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/servicemanager:view',
    ],
    'local_servicemanager_get_schema' => [
        'classname' => 'local_servicemanager_external',
        'methodname' => 'get_schema',
        'classpath' => 'local/servicemanager/externallib.php',
        'description' => 'Get a single service schema by ID.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/servicemanager:view',
    ],
    'local_servicemanager_create_schema' => [
        'classname' => 'local_servicemanager_external',
        'methodname' => 'create_schema',
        'classpath' => 'local/servicemanager/externallib.php',
        'description' => 'Create a new service schema.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/servicemanager:manage',
    ],
    'local_servicemanager_update_schema' => [
        'classname' => 'local_servicemanager_external',
        'methodname' => 'update_schema',
        'classpath' => 'local/servicemanager/externallib.php',
        'description' => 'Update an existing service schema.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/servicemanager:manage',
    ],
    'local_servicemanager_delete_schema' => [
        'classname' => 'local_servicemanager_external',
        'methodname' => 'delete_schema',
        'classpath' => 'local/servicemanager/externallib.php',
        'description' => 'Delete a service schema.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/servicemanager:manage',
    ],
];

$services = [
    'Web Service - Web Service Manager' => [
        'functions' => [
            'local_servicemanager_get_schemas',
            'local_servicemanager_get_schema',
            'local_servicemanager_create_schema',
            'local_servicemanager_update_schema',
            'local_servicemanager_delete_schema',
        ],
        'restrictedusers' => 1,
        'enabled' => 1,
        'shortname' => 'ws_servicemanager',
    ],
];
