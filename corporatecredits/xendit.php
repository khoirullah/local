<?php

require('../../config.php');

require_login();

global $CFG, $DB, $USER;

$companyid = required_param('companyid', PARAM_INT);
$coins     = required_param('coins', PARAM_INT);
$amount    = required_param('amount', PARAM_INT);

$company = $DB->get_record(
    'local_company',
    ['id' => $companyid],
    '*',
    MUST_EXIST
);

// =========================
// VALIDATION
// =========================

if ($coins <= 0) {
    throw new moodle_exception(
        'invalidcoins',
        'local_corporatecredits'
    );
}

if ($amount <= 0) {
    throw new moodle_exception(
        'invalidamount',
        'local_corporatecredits'
    );
}

// =========================
// GET XENDIT CONFIG
// =========================

$paygw = $DB->get_record(
    'payment_gateways',
    ['gateway' => 'xendit'],
    '*',
    MUST_EXIST
);

$config = json_decode(
    $paygw->config,
    true
);

$secretkey = $config['secret'] ?? '';

if (empty($secretkey)) {
    throw new moodle_exception(
        'Xendit secret key not configured'
    );
}

// =========================
// CREATE TRANSACTION
// =========================

$transaction = new stdClass();

$transaction->companyid = $companyid;
$transaction->userid = $USER->id;
$transaction->coins = $coins;
$transaction->amount = $amount;
$transaction->status = 'pending';
$transaction->timecreated = time();
$transaction->timemodified = time();

$transactionid = $DB->insert_record(
    'local_corpcredits_transaction',
    $transaction
);

// =========================
// CREATE XENDIT INVOICE
// =========================

$externalid =
    'TOPUP-' .
    $companyid .
    '-' .
    $transactionid .
    '-' .
    time();

$payload = [
    'external_id' => $externalid,
    'amount' => $amount,
    'payer_email' => $USER->email,
    'description' =>
        'Topup ' .
        number_format($coins, 0, ',', '.') .
        ' Corporate Credits',
    'currency' => 'IDR',

    'customer' => [
        'given_names' => fullname($USER),
        'email' => $USER->email
    ],

    'success_redirect_url' =>
        $CFG->wwwroot .
        '/local/corporatecredits/success.php?id=' .
        $transactionid,

    'failure_redirect_url' =>
        $CFG->wwwroot .
        '/local/corporatecredits/failed.php?id=' .
        $transactionid,
];