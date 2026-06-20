<?php

require('../../config.php');

use local_corporatecredits\invoice_manager;

$invoiceid =
    required_param(
        'invoiceid',
        PARAM_INT
    );

invoice_manager::mark_paid(
    $invoiceid
);

echo 'OK';