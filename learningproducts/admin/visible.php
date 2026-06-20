<?php

require('../../../config.php');

$id = optional_param('id', 0, PARAM_INT);

require_login();

$context = context_system::instance();

require_capability(
    'local/learningproducts:manage',
    $context
);

$data = new stdClass();

$data->id = $id;
$data->visible = 1;

\local_learningproducts\product_manager::update_product($data);

redirect(new moodle_url('/local/learningproducts/admin/index.php'));
