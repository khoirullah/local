<?php

require('../../config.php');

require_login();

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/credits/wallet.php'));
$PAGE->set_title(get_string('wallet', 'local_credits'));
$PAGE->set_heading(get_string('wallet', 'local_credits'));

$walletmanager = new \local_credits\wallet_manager();

$balance = $walletmanager->get_balance($USER->id);

echo $OUTPUT->header();

$templatecontext = [
    'balance' => $balance
];

echo $OUTPUT->render_from_template(
    'local_credits/wallet',
    $templatecontext
);

echo $OUTPUT->footer();