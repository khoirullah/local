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

$records = $DB->get_records('payment_gateways');

$paygw = [];

foreach ($records as $record) {

    if (strpos($record->config, 'xnd_') !== false) {

        $paygw = [
            'id'        => $record->id,
            'accountid' => $record->accountid,
            'gateway'   => $record->gateway,
            'config'    => $record->config
        ];

        break;
    }
}

if (empty($paygw)) {
    throw new moodle_exception(
        'Xendit configuration not found'
    );
}

$config = json_decode(
    $paygw['config'],
    true
);

$secret = $config['secret'] ?? '';

if (empty($secret)) {
    throw new moodle_exception(
        'Xendit secret key not configured'
    );
}

// =========================
// CREATE INVOICE RECORD
// =========================

$invoice = new stdClass();

$invoice->companyid = $companyid;
$invoice->invoicecode = '';
$invoice->coins = $coins;
$invoice->amount = $amount;
$invoice->status = 'pending';
$invoice->paymentmethod = 'xendit';
$invoice->timecreated = time();
$invoice->timemodified = time();

$invoiceid = $DB->insert_record(
    'local_corpcredits_invoice',
    $invoice
);

// =========================
// CREATE XENDIT INVOICE
// =========================

$externalid =
    'TOPUP-' .
    $companyid .
    '-' .
    $invoiceid .
    '-' .
    time();

$description =
    'Top up Corporate Credits untuk perusahaan ' .
    $company->name .
    ' sebanyak ' .
    number_format($coins, 0, ',', '.') .
    ' credits dengan nilai pembayaran Rp ' .
    number_format($amount, 0, ',', '.');

$payload = [

    'external_id' => $externalid,

    'amount' => $amount,

    'payer_email' => $USER->email,

    'description' => $description,

    'currency' => 'IDR',

    'customer' => [
        'given_names' => fullname($USER),
        'email' => $USER->email
    ],

    'success_redirect_url' =>
        $CFG->wwwroot .
        '/local/corporatecredits/payment_success.php?id=' .
        $invoiceid,

    'failure_redirect_url' =>
        $CFG->wwwroot .
        '/local/corporatecredits/payment_failed.php?id=' .
        $invoiceid,

    'items' => [
        [
            'id' => 'CC-' . $companyid,
            'name' => number_format($coins, 0, ',', '.') . ' Corporate Credits',
            'quantity' => 1,
            'price' => $amount,
            'category' => 'Corporate Credits'
        ]
    ]
];

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => 'https://api.xendit.co/v2/invoices',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Basic ' .
        base64_encode($secret . ':')
    ]
]);

$response = curl_exec($curl);
$httpcode = curl_getinfo(
    $curl,
    CURLINFO_HTTP_CODE
);

curl_close($curl);

$result = json_decode(
    $response,
    true
);

// =========================
// FAILED CREATE INVOICE
// =========================

if (
    $httpcode < 200 ||
    $httpcode >= 300 ||
    empty($result['invoice_url'])
) {

    $DB->set_field(
        'local_corpcredits_invoice',
        'status',
        'failed',
        ['id' => $invoiceid]
    );

    redirect(
        new moodle_url(
            '/local/corporatecredits/payment_failed.php',
            [
                'id' => $invoiceid
            ]
        )
    );
}

// =========================
// UPDATE INVOICE
// =========================

$DB->update_record(
    'local_corpcredits_invoice',
    (object)[
        'id' => $invoiceid,
        'invoicecode' => $externalid,
        'status' => 'pending',
        'timemodified' => time()
    ]
);

// =========================
// REDIRECT TO XENDIT
// =========================

redirect(
    $result['invoice_url']
);