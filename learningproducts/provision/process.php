<?php
/**
 * Process provisioning request.
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

$newcourseid = required_param(
    'copy',
    PARAM_INT
);

$id = required_param('id', PARAM_INT);

global $DB, $USER;

$returnurl = 
    new \moodle_url(
        '/local/learningproducts/provision/index.php'
    );
    
try {

    provision_manager::process($id, $newcourseid);

    redirect(
        $returnurl,
        'Provision completed successfully.',
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );

} catch (\Throwable $e) {

    redirect(
        $returnurl,
        $e->getMessage(),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}