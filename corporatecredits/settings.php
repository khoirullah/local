<?php

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $settings = new admin_settingpage(
        'local_corporatecredits',
        get_string('pluginname', 'local_corporatecredits')
    );

    $ADMIN->add('localplugins', $settings);
}