<?php

require('../../config.php');

use local_corporatecredits\invoice_manager;

$invoiceid =
    required_param(
        'id',
        PARAM_INT
    );

$invoice = invoice_manager::get_invoice(
    $invoiceid
);

invoice_manager::mark_failed(
    $invoiceid
);

$invoice = invoice_manager::get_invoice($invoiceid);

redirect(
    new moodle_url('/local/company/detail.php', [
        'id' => $invoice->companyid,
    ]),
    get_string('paymentfailed', 'local_corporatecredits'),
    0,
    \core\output\notification::NOTIFY_ERROR
);