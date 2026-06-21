<?php

require('../../../config.php');

use local_company\assignment_manager;

$entitlementid =
    required_param(
        'entitlementid',
        PARAM_INT
    );

require_login();

require_capability(
    'local_company:manage',
    context_system::instance()
);

$mform =
    new \local_company\form\assignment_form(
        null,
        [
            'entitlementid' => $entitlementid
        ]
    );

if ($mform->is_cancelled()) {

    redirect(
        new moodle_url(
            '/local/company/detail.php'
        )
    );
}

if ($data = $mform->get_data()) {

    assignment_manager::assign_user(
        $entitlementid,
        $data->userid,
        $USER->id
    );

    redirect(
        new moodle_url(
            '/local/company/detail.php'
        ),
        'Seat assigned'
    );
}

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();