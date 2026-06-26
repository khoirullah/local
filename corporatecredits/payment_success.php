<?php

require('../../config.php');

use local_corporatecredits\invoice_manager;

$invoiceid =
    required_param(
        'id',
        PARAM_INT
    );

invoice_manager::mark_paid(
    $invoiceid, 'xendit'
);

$invoice = invoice_manager::get_invoice($invoiceid);

redirect(
    new moodle_url('/local/company/detail.php', [
        'id' => $invoice->companyid,
    ]),
    get_string('paymentsuccess', 'local_corporatecredits'),
    \core\output\notification::NOTIFY_SUCCESS
);