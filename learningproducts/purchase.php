<?php

require('../../config.php');

require_login();

$productid = required_param(
    'productid',
    PARAM_INT
);

$companyid =
    \local_company\helper
        ::get_user_company(
            $USER->id
        );

try {

    \local_coins\purchase_manager
        ::purchase_product(
            $companyid,
            $USER->id,
            $productid
        );

    redirect(
        new moodle_url('/my'),
        get_string(
            'purchasesuccess',
            'local_learningproducts'
        )
    );

} catch (\Throwable $e) {

    redirect(
        new moodle_url(
            '/local/learningproducts/index.php'
        ),
        $e->getMessage(),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}