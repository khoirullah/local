<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    'local/corporatecredits:manage' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        ]
    ],

    'local/corporatecredits:view' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'manager' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
        ]
    ],
];