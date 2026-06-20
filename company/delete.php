<?php
require('../../config.php');

use local_company\company_manager;

$id = required_param('id', PARAM_INT);

require_login();
$context = context_system::instance();
require_capability('local/company:manage', $context);

$PAGE->set_url('/local/company/delete.php', ['id' => $id]);
$PAGE->set_context($context);

$company = company_manager::get($id);

$confirm = optional_param('confirm', 0, PARAM_BOOL);

if ($confirm) {
    company_manager::delete($id);
    redirect(new moodle_url('/local/company/index.php'));
}

echo $OUTPUT->header();

echo $OUTPUT->confirm(
    get_string('confirmdelete', 'local_company', $company->name),
    new moodle_url('/local/company/delete.php', ['id' => $id, 'confirm' => 1]),
    new moodle_url('/local/company/index.php')
);

echo $OUTPUT->footer();