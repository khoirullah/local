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

$cancelurl = get_local_referer();

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

use local_corporatecredits\invoice_manager;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    require_sesskey();

    $coins = required_param(
        'coins',
        PARAM_INT
    );

    $coinprice = (float)get_config(
        'local_corporatecredits',
        'coinprice'
    );

    $minimumtopup = (int)get_config(
        'local_corporatecredits',
        'minimumtopup'
    );

    $maximumtopup = (int)get_config(
        'local_corporatecredits',
        'maximumtopup'
    );

    if ($coins < $minimumtopup) {
        throw new moodle_exception(
            'Minimum topup is ' . $minimumtopup . ' coins.'
        );
    }

    if ($coins > $maximumtopup) {
        throw new moodle_exception(
            'Maximum topup is ' . $maximumtopup . ' coins.'
        );
    }

    $amount = $coins * $coinprice;

    $invoiceid =
        invoice_manager::create_invoice(
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

$coinprice = get_config(
    'local_corporatecredits',
    'coinprice'
);

$coinprice = is_numeric($coinprice)
    ? (float)$coinprice
    : 1000;

$templatecontext['coinprice'] = $coinprice;

$templatecontext['cancelurl'] = (
        new moodle_url(
            '/local/company/detail.php',
            [
                'id' => $companyid
            ]
        )
    )->out(false);
    
$templatecontext['minimumtopup'] =
    (int)get_config(
        'local_corporatecredits',
        'minimumtopup'
    ) ?: 10;

$templatecontext['maximumtopup'] =
    (int)get_config(
        'local_corporatecredits',
        'maximumtopup'
    ) ?: 100000;

$templatecontext['currency'] = 
    get_config(
        'local_corporatecredits',
        'currency'
    ) ?: 'IDR';

$templatecontext['coinpriceformatted'] =
    number_format($coinprice, 0);

$templatecontext['is_admin'] =
    is_siteadmin($USER->id);

echo $OUTPUT->render_from_template(
    'local_corporatecredits/topup',
    $templatecontext
);

echo $OUTPUT->footer();