<?php
/**
 * Capabilities definition.
 *
 * @package    local_learningproducts
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    'local/learningproducts:manage' => [
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW
        ]
    ],

    'local/learningproducts:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'user' => CAP_ALLOW
        ]
    ]
];