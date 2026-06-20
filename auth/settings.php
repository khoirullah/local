<?php

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    // Buat halaman setting utama
    $settings = new admin_settingpage('local_auth_settings', get_string('pluginname', 'local_auth'));

    // --- Section: Login Settings ---
    $settings->add(new admin_setting_heading(
        'local_auth/loginsection',
        get_string('loginsection', 'local_auth'),
        ''
    ));

    $forgoturl = new moodle_url('/login/forgot_password.php');
    $signupurl = new moodle_url('/login/signup.php');

    $settings->add(new admin_setting_configtext(
        'local_auth/forgotpasswordurl',
        get_string('forgotpasswordurl', 'local_auth'),
        get_string('forgotpasswordurl_desc', 'local_auth'),
        $forgoturl->out(false)
    ));

    $settings->add(new admin_setting_configtext(
        'local_auth/signupurl',
        get_string('signupurl', 'local_auth'),
        get_string('signupurl_desc', 'local_auth'),
        $signupurl->out(false)
    ));

    // --- Section: Login by (OAuth2) ---
    global $DB;

    $records = $DB->get_records('oauth2_issuer', ['enabled' => 1]);
    $options = [];
    $disabledoptions = [];

    foreach ($records as $issuer) {
        $id = $issuer->id;
        $name = $issuer->name;

        if ($issuer->systemaccount > 0) {
            $options[$id] = $name;
        } else {
            $options[$id] = $name . ' (' . get_string('noconnectedaccount', 'local_auth') . ')';
            $disabledoptions[] = $id;
        }
    }

    if (!empty($options)) {
        $settings->add(new admin_setting_configmulticheckbox(
            'local_auth/loginby',
            get_string('loginby', 'local_auth'),
            get_string('loginby_desc', 'local_auth'),
            array_keys($options),
            $options,
        ));
    } else {
        $settings->add(new admin_setting_heading(
            'local_auth/loginby_heading',
            get_string('loginby', 'local_auth'),
            get_string('nologinmethod', 'local_auth')
        ));
    }
   
    // --- Section: Signup Settings (masih kosong) ---
    $settings->add(new admin_setting_heading(
        'local_auth/signupsection',
        get_string('signupsection', 'local_auth'),
        ''
    ));

    // Register ke halaman admin plugin
    $ADMIN->add('localplugins', $settings);
}
