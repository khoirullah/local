<?php

require('../../../config.php');

use local_company\assignment_manager;

$id = required_param(
    'id',
    PARAM_INT
);

require_login();

require_capability(
    'local_company:manage',
    context_system::instance()
);

assignment_manager::unassign_user(
    $id
);

redirect(
    new moodle_url(
        $_SERVER['HTTP_REFERER']
    ),
    'Assignment removed'
);