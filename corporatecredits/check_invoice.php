<?php

require('../../config.php');

require_login();

use local_corporatecredits\invoice_manager;

$id = required_param('id', PARAM_INT);

$invoice = invoice_manager::get_invoice($id);

header('Content-Type: application/json');

echo json_encode([
    'status' => $invoice->status
]);