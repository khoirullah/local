<?php
/**
 * Delete provisioning request.
 *
 * @package local_learningproducts
 */

require('../../../config.php');

use local_learningproducts\provision_manager;

require_login();
require_sesskey();

$context = context_system::instance();

require_capability(
    'moodle/site:config',
    $context
);

$id = required_param('id', PARAM_INT);

global $DB, $USER;

$returnurl = 
    new \moodle_url(
        '/local/learningproducts/provision/index.php'
    );

provision_manager::set_status($id, 'failed', $USER->id);

redirect(
    $returnurl,
    'Has been deleted',
    null,
    \core\output\notification::NOTIFY_SUCCESS
);