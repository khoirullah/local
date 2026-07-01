<?php
/**
 * Provision queue.
 *
 * @package    local_learningproducts
 */

require('../../../config.php');

use local_corporatecredits\invoice_manager;

require_login();

$context = context_system::instance();

require_capability(
    'moodle/site:config',
    $context
);

$PAGE->set_context($context);
//$PAGE->set_pagelayout('admin');

$PAGE->set_url(
    new moodle_url(
        '/local/corporatecredits/invoice/index.php'
    )
);

$PAGE->set_title( 
    get_string(
        'invoicequeue',
        'local_corporatecredits'
    )
);

$PAGE->navbar->add(
    get_string('home'),
    new moodle_url(
        '/my'
    )
);
$PAGE->navbar->add(
    get_string('pluginname', 'local_corporatecredits'),
    new moodle_url(
        '/local/corporatecredits/index.php'
    )
);
$PAGE->navbar->add(
    get_string('invoicequeue', 'local_corporatecredits')
);

$invoices = invoice_manager::get_all_invoice();

$items = [];

foreach ($invoices as $invoice) {

    $items[] = [

        'id' => $invoice->id,

        'companyname' => $invoice->companyname,

        'invoicecode' => $invoice->invoicecode,

        'coins' => number_format($invoice->coins, 0, '.', ','),

        'amount' => 'Rp '.number_format($invoice->amount, 0, '.', ','),

        'paymentmethod' => $invoice->paymentmethod,

        'status' => ucfirst(
            $invoice->status
        ),

        'statuspending' =>
            $invoice->status === 'pending',

        'statusprocessing' =>
            $invoice->status === 'processing',

        'statuscompleted' =>
            $invoice->status === 'completed' || $invoice->status === 'paid',

        'statusfailed' =>
            $invoice->status === 'failed',

        'processurl' => ( 
            new moodle_url(
                '/local/corporatecredits/invoice/process.php',
                [
                    'id' => $invoice->id,
                    'sesskey' => sesskey()
                ]
            )
        )->out(false)

    ];
}

$templatecontext = [

    'items' => $items,

    'hasrecords' => !empty($items)

];

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_corporatecredits/invoice_status',
    $templatecontext
);

echo $OUTPUT->footer();