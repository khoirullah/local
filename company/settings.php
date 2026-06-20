<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_company_manage',
        get_string('pluginname', 'local_company'),
        new moodle_url('/local/company/index.php'),
        'local/company:manage'
    ));

}