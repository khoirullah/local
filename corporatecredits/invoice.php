<?php

require('../../config.php');

use local_corporatecredits\invoice_manager;

require_login();

$id =
    required_param(
        'id',
        PARAM_INT
    );

$invoice =
    invoice_manager
        ::get_invoice(
            $id
        );

$PAGE->set_context(
    context_system::instance()
);

$PAGE->set_url(
    '/local/corporatecredits/invoice.php',
    [
        'id' => $id
    ]
);

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_corporatecredits/invoice',
    [

        'invoicecode' =>
            $invoice->invoicecode,

        'coins' =>
            number_format(
                $invoice->coins
            ),

        'amount' =>
            number_format(
                $invoice->amount
            ),

        'status' =>
            ucfirst(
                $invoice->status
            ),

        'producturl' =>
            (new moodle_url(
                '/local/learningproducts/index.php'
            ))->out(false)

    ]
);

echo $OUTPUT->footer();