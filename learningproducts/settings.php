<?php
/**
 * Plugin settings.
 *
 * File ini membuat halaman setting plugin
 * di Site administration.
 *
 * @package    local_learningproducts
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $settings = new admin_settingpage(
        'local_learningproducts',
        get_string('pluginname', 'local_learningproducts')
    );

    $settings->add(new admin_setting_configtext(
        'local_learningproducts/productsperpage',
        get_string('productsperpage', 'local_learningproducts'),
        get_string('productsperpage_desc', 'local_learningproducts'),
        12,
        PARAM_INT
    ));

    $ADMIN->add('localplugins', $settings);
}