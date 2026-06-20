<?php

require('../../config.php');

use local_corporatecredits\wallet_manager;
use local_corporatecredits\transaction_manager;

require_login();

$context =
    context_system::instance();

require_capability(
    'local/corporatecredits:view',
    $context
);

$companyid =
    required_param(
        'companyid',
        PARAM_INT
    );

$summary =
    wallet_manager::get_summary(
        $companyid
    );

$transactions =
    transaction_manager
        ::get_company_transactions(
            $companyid
        );

$templatecontext = [

    'balance' =>
        number_format(
            $summary['balance']
        ),

    'creditin' =>
        number_format(
            $summary['creditin']
        ),

    'creditout' =>
        number_format(
            $summary['creditout']
        ),

    'transactions' => []
];

foreach ($transactions as $transaction) {

    $templatecontext['transactions'][] = [

        'date' =>
            userdate(
                $transaction->timecreated
            ),

        'type' =>
            ucfirst(
                $transaction->type
            ),

        'amount' =>
            number_format(
                $transaction->amount
            ),

        'balanceafter' =>
            number_format(
                $transaction->balanceafter
            ),

        'description' =>
            $transaction->description
    ];
}

$PAGE->set_context($context);

$PAGE->set_url(
    '/local/corporatecredits/index.php',
    [
        'companyid' => $companyid
    ]
);

$PAGE->set_title(
    get_string(
        'pluginname',
        'local_corporatecredits'
    )
);

$PAGE->set_heading(
    get_string(
        'pluginname',
        'local_corporatecredits'
    )
);

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_corporatecredits/wallet',
    $templatecontext
);

echo $OUTPUT->footer();