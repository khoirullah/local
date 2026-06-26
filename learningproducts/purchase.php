<?php
require('../../config.php');

use local_corporatecredits\wallet_manager;
use local_company\transaction_manager;
use local_company\company_manager;
use local_learningproducts\product_manager;
use local_learningproducts\purchase_manager;
use local_learningproducts\enrolment_manager;

require_login();
require_sesskey();

$id = required_param('id', PARAM_INT);

$product = product_manager::get_product($id);

if (!$product) {
    redirect(
        new moodle_url('/local/learningproducts/index.php'),
        get_string('productnotfound', 'local_learningproducts'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

$company = company_manager::get_user_company($USER->id);

if (!$company) {
    redirect(
        new moodle_url(
            '/local/learningproducts/topup.php'
        )
    );
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

    // Potong saldo dan catat transaksi wallet.
    wallet_manager::deduct_credit(
        $company->id,
        $price,
        'learningproducts',
        $product->id,
        'Purchase ' . $product->name
    );

    purchase_manager::create_purchase(
        $company->id,
        $product->id,
        $price,
        1,
        $USER->id
    );

    // Assign cohort/course sesuai tipe produk.
    enrolment_manager::enrol_product(
        $product->id,
        $USER->id
    );

    $transaction->allow_commit();

    redirect(
        new moodle_url('/local/learningproducts/view.php', ['id' => $product->id]),
        get_string('purchasesuccess', 'local_learningproducts'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );

} catch (Exception $e) {

    $transaction->rollback($e);

    redirect(
        new moodle_url('/local/learningproducts/view.php', [
            'id' => $product->id
        ]),
        $e->getMessage(),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}
