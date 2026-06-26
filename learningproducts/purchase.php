<?php
require('../../config.php');

use local_corporatecredits\wallet_manager;
use local_company\transaction_manager;
use local_company\company_manager;
use local_learningproducts\product_manager;

require_login();
require_sesskey();

$id = required_param('id', PARAM_INT);

$product = product_manager::get_product($id);

if (!$product) {
    throw new moodle_exception('invalidproduct');
}

$company = company_manager::get_user_company($USER->id);

if (!$company) {
    throw new moodle_exception('companynotfound');
}

$wallet = wallet_manager::get_summary($company->id);

$balance = (int)$wallet['balance_raw']; // jangan pakai balance yang sudah diformat
$price   = (int)$product->price;

if ($balance < $price) {

    redirect(
        new moodle_url('/local/learningproducts/view.php', ['id' => $id]),
        get_string('insufficientcredits', 'local_learningproducts'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

$transaction = $DB->start_delegated_transaction();

try {

    // Kurangi saldo wallet.
    wallet_manager::debit(
        $company->id,
        $price
    );

    // Catat transaksi wallet.
    transaction_manager::create([
        'companyid'   => $company->id,
        'amount'      => -$price,
        'type'        => 'purchase',
        'source'      => 'learningproducts',
        'referenceid' => $product->id,
        'description' => 'Purchase ' . $product->name,
    ]);

    // Simpan histori pembelian.
    $purchase = new stdClass();
    $purchase->companyid = $company->id;
    $purchase->productid = $product->id;
    $purchase->price = $price;
    $purchase->status = 'paid';
    $purchase->userid = $USER->id;
    $purchase->timecreated = time();

    $DB->insert_record(
        'local_learningproducts_orders',
        $purchase
    );

    // Assign cohort/course sesuai tipe produk.
    product_manager::provision(
        $product,
        $company->id
    );

    $transaction->allow_commit();

} catch (Exception $e) {

    $transaction->rollback($e);
}

redirect(
    new moodle_url('/local/learningproducts/view.php', ['id' => $product->id]),
    get_string('purchasesuccess', 'local_learningproducts'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);