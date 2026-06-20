<?php

defined('MOODLE_INTERNAL') || die();

$functions = [

    'local_corporatecredits_get_balance' => [
        'classname'   => 'local_corporatecredits\external\get_balance',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get wallet balance',
        'type'        => 'read',
        'ajax'        => true,
    ],

];