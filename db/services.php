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

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_wsmanager_get_schemas' => [
        'classname' => 'local_wsmanager_external',
        'methodname' => 'get_schemas',
        'classpath' => 'local/wsmanager/externallib.php',
        'description' => 'Get all service schemas.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/wsmanager:view',
    ],
    'local_wsmanager_get_schema' => [
        'classname' => 'local_wsmanager_external',
        'methodname' => 'get_schema',
        'classpath' => 'local/wsmanager/externallib.php',
        'description' => 'Get a single service schema by ID.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/wsmanager:view',
    ],
    'local_wsmanager_create_schema' => [
        'classname' => 'local_wsmanager_external',
        'methodname' => 'create_schema',
        'classpath' => 'local/wsmanager/externallib.php',
        'description' => 'Create a new service schema.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/wsmanager:manage',
    ],
    'local_wsmanager_update_schema' => [
        'classname' => 'local_wsmanager_external',
        'methodname' => 'update_schema',
        'classpath' => 'local/wsmanager/externallib.php',
        'description' => 'Update an existing service schema.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/wsmanager:manage',
    ],
    'local_wsmanager_delete_schema' => [
        'classname' => 'local_wsmanager_external',
        'methodname' => 'delete_schema',
        'classpath' => 'local/wsmanager/externallib.php',
        'description' => 'Delete a service schema.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/wsmanager:manage',
    ],
];

$services = [
    'Web Service - Web Service Manager' => [
        'functions' => [
            'local_wsmanager_get_schemas',
            'local_wsmanager_get_schema',
            'local_wsmanager_create_schema',
            'local_wsmanager_update_schema',
            'local_wsmanager_delete_schema',
        ],
        'restrictedusers' => 1,
        'enabled' => 1,
        'shortname' => 'ws_wsmanager',
    ],
];
