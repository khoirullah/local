<?php

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();

$context = context_system::instance();

require_capability(
    'local/learningproducts:manage',
    $context
);

\local_learningproducts\category_manager
    ::delete_category($id);

redirect(new moodle_url('/local/learningproducts/admin/index.php'));