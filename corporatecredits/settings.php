<?php

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $settings = new admin_settingpage(
        'local_corporatecredits',
        get_string('pluginname', 'local_corporatecredits')
    );

    // Enable wallet.
    $settings->add(
        new admin_setting_configcheckbox(
            'local_corporatecredits/enablewallet',
            get_string('enablewallet', 'local_corporatecredits'),
            get_string('enablewallet_desc', 'local_corporatecredits'),
            1
        )
    );

    // Currency.
    $settings->add(
        new admin_setting_configtext(
            'local_corporatecredits/currency',
            get_string('currency', 'local_corporatecredits'),
            get_string('currency_desc', 'local_corporatecredits'),
            'IDR',
            PARAM_TEXT
        )
    );

    // Price per coin.
    $settings->add(
        new admin_setting_configtext(
            'local_corporatecredits/coinprice',
            get_string('coinprice', 'local_corporatecredits'),
            get_string('coinprice_desc', 'local_corporatecredits'),
            '1000',
            PARAM_FLOAT
        )
    );

    // Minimum topup.
    $settings->add(
        new admin_setting_configtext(
            'local_corporatecredits/minimumtopup',
            get_string('minimumtopup', 'local_corporatecredits'),
            get_string('minimumtopup_desc', 'local_corporatecredits'),
            '10',
            PARAM_INT
        )
    );

    // Maximum topup.
    $settings->add(
        new admin_setting_configtext(
            'local_corporatecredits/maximumtopup',
            get_string('maximumtopup', 'local_corporatecredits'),
            get_string('maximumtopup_desc', 'local_corporatecredits'),
            '100000',
            PARAM_INT
        )
    );

    // Invoice expiry (second).
    $settings->add(
        new admin_setting_configtext(
            'local_corporatecredits/invoiceexpiry',
            get_string('invoiceexpiry', 'local_corporatecredits'),
            get_string('invoiceexpiry_desc', 'local_corporatecredits'),
            '86400',
            PARAM_INT
        )
    );

    // Low balance threshold.
    $settings->add(
        new admin_setting_configtext(
            'local_corporatecredits/lowbalance',
            get_string('lowbalance', 'local_corporatecredits'),
            get_string('lowbalance_desc', 'local_corporatecredits'),
            '50',
            PARAM_INT
        )
    );

    // Welcome coins.
    $settings->add(
        new admin_setting_configtext(
            'local_corporatecredits/welcomecoins',
            get_string('welcomecoins', 'local_corporatecredits'),
            get_string('welcomecoins_desc', 'local_corporatecredits'),
            '0',
            PARAM_INT
        )
    );

    $ADMIN->add('localplugins', $settings);
}