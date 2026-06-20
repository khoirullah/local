<?php
require('../../config.php');

use local_company\company_manager;

require_login();

$context = context_system::instance();

$canmanageall = has_capability(
    'local/company:manageall',
    $context
);

$canmanage = has_capability(
    'local/company:manage',
    $context
);

if (!$canmanageall && !$canmanage) {
    throw new required_capability_exception(
        $context,
        'local/company:manage',
        'nopermissions',
        ''
    );
}

$search  = optional_param('search', '', PARAM_TEXT);
$page    = optional_param('page', 0, PARAM_INT);
$status = optional_param('status', '', PARAM_TEXT);
$util   = optional_param('util', '', PARAM_TEXT);
$perpage = 9;

$PAGE->set_url('/local/company/index.php', [
    'search' => $search,
    'status' => $status,
    'util' => $util,
    'page'   => $page
]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('companymanagement', 'local_company'));
$PAGE->set_heading(get_string('companymanagement', 'local_company'));

echo $OUTPUT->header();

global $USER;

if (!$canmanageall) {

    $company = company_manager::get_pic_company($USER->id);

    if ($company) {
        redirect(
            new moodle_url(
                '/local/company/detail.php',
                ['id' => $company->id]
            )
        );
    }
}

list($companies, $total) = company_manager::get_all($search, $status, $util, $page, $perpage);

$templatecontext = [
    'search' => $search,
    'addurl' => (new moodle_url('/local/company/edit.php'))->out(),
    'companies' => []
];

foreach ($companies as $company) {

    $templatecontext['companies'][] = [
        'name'          => format_string($company->name),
        'clientstatus'  => $company->clientstatus,
        'statusclass'   => $company->statusclass,
        'activecourses' => $company->activecourses,
        'quota'         => $company->quota,
        'used'          => $company->used,
        'left'          => $company->left,
        'utiltext'      => $company->utiltext,
        'rowclass'      => $company->rowclass,
        'search'        => $search,
        'status'        => $status,
        'util'          => $util,
        'detailurl'     => (new moodle_url('/local/company/detail.php', ['id'=>$company->id]))->out(),
        'editurl'       => (new moodle_url('/local/company/edit.php', ['id'=>$company->id]))->out(),
        'deleteurl'     => (new moodle_url('/local/company/delete.php', ['id'=>$company->id]))->out(),
    ];
    $templatecontext['statusactive'] = ($status === 'Active');
    $templatecontext['statusexpiring'] = ($status === 'Expiring');
    $templatecontext['statussuspend'] = ($status === 'Suspended');

    $templatecontext['utillow'] = ($util === 'Low');
    $templatecontext['utilmedium'] = ($util === 'Medium');
    $templatecontext['utilhigh'] = ($util === 'High');
}

echo $OUTPUT->render_from_template(
    'local_company/company_table',
    $templatecontext
);

echo $OUTPUT->paging_bar(
    $total,
    $page,
    $perpage,
    new moodle_url('/local/company/index.php', ['search' => $search])
);

echo $OUTPUT->footer();