<?php

require('../../config.php');

require_login();

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/corporatecredits/transactions.php'));
$PAGE->set_title(get_string('transactions', 'local_corporatecredits'));
$PAGE->set_heading(get_string('transactions', 'local_corporatecredits'));

$transactionmanager = new \local_corporatecredits\transaction_manager();

$transactions = $transactionmanager->get_user_transactions($USER->id);

echo $OUTPUT->header();

$table = new html_table();

$table->head = [
    get_string('type', 'local_corporatecredits'),
    get_string('amount', 'local_corporatecredits'),
    get_string('description', 'local_corporatecredits'),
    get_string('timecreated', 'local_corporatecredits')
];

foreach ($transactions as $txn) {

    $table->data[] = [
        s($txn->type),
        s($txn->amount),
        s($txn->description),
        userdate($txn->timecreated)
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();