<?php

require('../../config.php');

use local_corporatecredits\wallet_manager;
use local_corporatecredits\transaction_manager;

require_login();

$context =
    context_system::instance();

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

    'sesskey' => sesskey(),

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
    '/local/corporatecredits/topup.php',
    [
        'companyid' => $companyid
    ]
);

$PAGE->set_title(
    get_string(
        'topupwallet',
        'local_corporatecredits'
    )
);

/* $PAGE->set_heading(
    get_string(
        'topupwallet',
        'local_corporatecredits'
    )
); */
use local_corporatecredits\invoice_manager;

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {

    require_sesskey();

    $coins =
        required_param(
            'coins',
            PARAM_INT
        );

    $amount =
        $coins * 1000;

    $invoiceid =
        invoice_manager
            ::create_invoice(
                $companyid,
                $coins,
                $amount
            );

    redirect(
        new moodle_url(
            '/local/corporatecredits/invoice.php',
            [
                'id' => $invoiceid
            ]
        )
    );
}
echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_corporatecredits/topup',
    $templatecontext
);

echo $OUTPUT->footer();