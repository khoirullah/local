<?php
/**
 * Approve invoice.
 *
 * @package local_corporatecredits
 */

require('../../../config.php');

use local_corporatecredits\invoice_manager;
use local_corporatecredits\wallet_manager;

require_login();
require_sesskey();

$context = context_system::instance();

require_capability(
    'moodle/site:config',
    $context
);

$id = required_param('id', PARAM_INT);

global $DB, $USER;

$invoice = invoice_manager::get_invoice($id);

if (!$invoice) {
    throw new moodle_exception(
        'invalidinvoice',
        'local_corporatecredits'
    );
}

if ($invoice->status !== 'pending') {
    redirect(
        new moodle_url(
            '/local/corporatecredits/invoice/index.php'
        ),
        get_string(
            'invoicealreadyprocessed',
            'local_corporatecredits'
        ),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

$transaction = $DB->start_delegated_transaction();

try {

    invoice_manager::mark_paid(
        $invoice->id, 'invoice'
    );

    $invoice = invoice_manager::get_invoice($invoice->id);
    
    $transaction->allow_commit();

    redirect(
        new moodle_url(
            '/local/corporatecredits/invoice/index.php'
        ),
        get_string(
            'invoiceapproved',
            'local_corporatecredits'
        ),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );

} catch (Throwable $e) {

    $transaction->rollback($e);

    redirect(
        new moodle_url(
            '/local/corporatecredits/invoice/index.php'
        ),
        $e->getMessage(),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}