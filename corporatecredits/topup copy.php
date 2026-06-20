<?php

require('../../config.php');

use local_corporatecredits\wallet_manager;

$companyid =
    optional_param(
        'companyid',
        0,
        PARAM_INT
    );

require_login();

require_capability(
    'local_company:manage',
    context_system::instance()
);

$PAGE->set_context(
    context_system::instance()
);

$PAGE->set_url(
    '/local/company/topup.php',
    [
        'companyid' => $companyid
    ]
);

$mform =
    new \local_company\form\topup_form(
        null,
        [
            'companyid' => $companyid
        ]
    );

if ($mform->is_cancelled()) {
    
    redirect(
        new moodle_url(
            '/local/company/detail.php',
            [
                'id' => $companyid,
                'tab' => 'wallet'
            ]
        )
    );
}

if ($data = $mform->get_data()) {

    wallet_manager::add_credit(
        $companyid,
        $data->amount,
        'manual_topup',
        0,
        'Manual topup'
    );

    redirect(
        new moodle_url(
            '/local/company/detail.php',
            [
                'id' => $companyid,
                'tab' => 'wallet'
            ]
        ),
        'Wallet topped up'
    );
}

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();