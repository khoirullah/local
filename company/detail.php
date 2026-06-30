<?php

require('../../config.php');

use local_company\company_manager;
use local_company\subscription_manager;
use local_company\form\subscription_form;
use local_company\member_manager;
use local_company\assignment_manager;
use local_company\entitlement_manager;
use local_corporatecredits\wallet_manager;
use local_corporatecredits\transaction_manager;

$id = optional_param('id', 0, PARAM_INT);
$companyid = optional_param('companyid', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);
$status = optional_param('status', '', PARAM_TEXT);
$courseid = optional_param('course', 0, PARAM_INT);
$year     = optional_param('year', date('Y'), PARAM_INT);
$tab = optional_param(
    'tab',
    'overview',
    PARAM_ALPHA
);

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    is_siteadmin() &&
    optional_param('action', '', PARAM_ALPHA) === 'topup'
) {
    require_sesskey();
    
    $companyid = required_param(
        'companyid',
        PARAM_INT
    );

    $amount = required_param(
        'amount',
        PARAM_FLOAT
    );

    \local_corporatecredits\wallet_manager::add_credit(
        $companyid,
        $amount,
        'admin_topup',
        0,
        'Manual topup by site administrator'
    );

    redirect(
        $PAGE->url,
        'Wallet berhasil ditopup'
    );
}

$context = context_system::instance();

require_capability('local/company:manage', $context);

$canmanageall = has_capability(
    'local/company:manageall',
    $context
);

$company =
    company_manager::get($id);

$PAGE->set_context($context);

$PAGE->set_url(
    '/local/company/detail.php',
    [
        'id' => $id,
        'tab' => $tab
    ]
);

$PAGE->set_title(
    $company->name
);

if($canmanageall){
    $PAGE->navbar->add(
        get_string('pluginname', 'local_company'),
        new moodle_url(
            '/local/company/'
        )
    );
}

$PAGE->navbar->add(
    'Detail'
);

/* =============================
   FORM
============================= */

$url = new moodle_url('/local/company/detail.php', ['id'=>$id]);

$mform = new subscription_form($url, ['companyid'=>$id]);
$mform->set_data(['companyid'=>$id]);

$deletesub = optional_param('deletesub', 0, PARAM_INT);

if ($deletesub && confirm_sesskey()) {
    subscription_manager::delete($deletesub);

    redirect(
        new moodle_url('/local/company/detail.php', ['id'=>$id]),
        'Subscription deleted',
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

if ($data = $mform->get_data()) {
    subscription_manager::create((array)$data);
    redirect(new moodle_url('/local/company/detail.php', ['id'=>$id]));
}

$chart = subscription_manager::get_enrolment_chart_data(
    $id,
    $courseid,
    $year
);

$templatecontext['chartlabels'] = json_encode($chart['labels']);
$templatecontext['chartdata']   = json_encode($chart['data']);

$templatecontext['years'] = subscription_manager::get_year_list();

$subscriptions = subscription_manager::get_by_company(
    $id,
    $search,
    $status
);

$seatspurchased = 0;
$seatsused = 0;
$completionlabels = [];
$completiondata = [];

foreach ($subscriptions as $sub) {
    
    $course = get_course($sub->courseid);

    $sub->course = $course->fullname;

    $sub->selected = ($courseid == $sub->courseid);

    $sub->used = subscription_manager::get_used_count(
        $id,
        $sub->courseid
    );

    $sub->remaining = max(
        0,
        $sub->quota - $sub->used
    );
   
    $seatspurchased += $sub->quota;
    $seatsused += $sub->used;

    if (!empty($sub->completion)) {
        $completionlabels[] = $sub->course;
        $completiondata[] = (int) str_replace(
            '%',
            '',
            $sub->completion
        );
    }
}

$seatsremaining = $seatspurchased - $seatsused;
$totalcourses = count($subscriptions);

$total = count($subscriptions);
$years = [];
$labels = [];
$data = [];
$completionlabels = [];
$completiondata = [];

$labels = $chart['labels'];
$data = $chart['data'];

$currentyear = date('Y');

for ($i = 0; $i < 5; $i++) {
    $years[] = [
        'year' => $currentyear - $i
    ];
}

/* =============================
   TEMPLATE
============================= */
$company->timecreated =  userdate($company->timecreated, '%d %b %Y');

$status = $company->status;

$company->status = $status === '1' ? 'Active' : 'Suspend';

$company->statusclass = $status === '1'
    ? 'badge bg-success text-white p-2'
    : 'badge bg-secondary text-muted p-2';

$totalcourses = count($subscriptions);
$seatspurchased = 0;
$seatsused = 0;

foreach ($subscriptions as $sub) {
    $seatspurchased += $sub->quota;
    $seatsused += subscription_manager::get_used_count($id, $sub->courseid);

}

$seatsremaining = $seatspurchased - $seatsused;


$fs = get_file_storage();
$context = context_system::instance();

$files = $fs->get_area_files(
    $context->id,
    'local_company',
    'logo',
    $company->id,
    'itemid, filepath, filename',
    false
);

$logo = '';

if ($files) {

    $file = reset($files);

    $logo = moodle_url::make_pluginfile_url(
        $file->get_contextid(),
        $file->get_component(),
        $file->get_filearea(),
        $file->get_itemid(),
        $file->get_filepath(),
        $file->get_filename()
    );

    //echo $logo;
}

$overview = [
    'description' => $company->description,
    'subscriptions' => array_values($subscriptions),
    'hassubscriptions' => !empty($subscriptions),
    'search' => $search,
    'status' => $status,
    'stats' => [
        'status' => $company->status,
        'totalcourses' => $totalcourses,
        'seatspurchased' => $seatspurchased,
        'seatsused' => $seatsused,
        'seatsremaining' => $seatsremaining
    ],
    'years' => $years,
    'statusactive' => $status === '1',
    'statussuspend' => $status === '0',
    'enrollmentlabels' => json_encode($labels),
    'enrollmentdata' => json_encode($data),
    'completionlabels' => json_encode($completionlabels),
    'completiondata' => json_encode($completiondata),
]; 

$entitlements = entitlement_manager::get_company_entitlements($id);
//$entitlements = subscription_manager::get_by_company($id);


foreach ($entitlements as $entitlement) {

    $entitlement->remaining = max(
        0,
        $entitlement->quota - $entitlement->used
    );

    $entitlement->status = $entitlement->status
        ? get_string('active')
        : get_string('inactive');

    $entitlement->startdate = userdate(
        $entitlement->startdate
    );

    $entitlement->enddate = userdate(
        $entitlement->enddate
    );
}

$entitlements = [
    'entitlements' => $entitlements,
    'hasentitlements' => !empty($entitlements)
];

$templatecontext['overview'] = $overview;

$company->logo = $logo; 
$templatecontext = [
    'overview' => $overview,
    'entitlements' => $entitlements,
    'sesskey' => sesskey(),
    'is_siteadmin' => is_siteadmin($USER->id),
    'wallet' => wallet_manager::get_summary($company->id),
    'topupicon' => 
        $OUTPUT->image_url(
            'topup',
            'local_company'
        )->out(false),
    'company' => $company,
    'companyid' => $company->id,
    'companyname' => $company->name,
    'description'  => $company->description,
    'subscriptions' => $subscriptions,
    'hassubscriptions' => !empty($subscriptions),
    'search' => $search,
    'status' => $status,
    'stats' => [
        'status' => $company->status,
        'totalcourses' => $totalcourses,
        'seatspurchased' => $seatspurchased,
        'seatsused' => $seatsused,
        'seatsremaining' => $seatsremaining
    ],
    'years' => $years,
    'statusactive' => $status === '1',
    'statussuspend' => $status === '0',

    // chart
    'enrollmentlabels' => json_encode($labels),
    'enrollmentdata' => json_encode($data),
    'completionlabels' => json_encode($completionlabels),
    'completiondata' => json_encode($completiondata),

    'assignurl' => (
        new moodle_url(
            '/local/company/assignment/assign.php',
            [
                'companyid' => $id
            ]
        )
    )->out(false),
    'topupurl' =>(
        new moodle_url(
            '/local/corporatecredits/topup.php',
            [
                'companyid' => $id
            ]
        )
    )->out(false),
    'overviewurl' =>(
        new moodle_url(
            '/local/company/detail.php',
            [
                'id' => $id,
                'tab' => 'overview'
            ]
        )
    )->out(false),
    'entitlementsurl' =>(
        new moodle_url(
            '/local/company/detail.php',
            [
                'id' => $id,
                'tab' => 'entitlements'
            ]
        )
    )->out(false),
    'membersurl' =>(
        new moodle_url(
            '/local/company/detail.php',
            [
                'id' => $id,
                'tab' => 'members'
            ]
        )
    )->out(false),
    'assignmentsurl' =>(
        new moodle_url(
            '/local/company/detail.php',
            [
                'id' => $id,
                'tab' => 'assignments'
            ]
        )
    )->out(false),
    'walleturl' =>(
        new moodle_url(
            '/local/company/detail.php',
            [
                'id' => $id,
                'tab' => 'wallet'
            ]
        )
    )->out(false),

    'overviewactive' =>
        $tab === 'overview',

    'entitlementsactive' =>
        $tab === 'entitlements',

    'membersactive' =>
        $tab === 'members',

    'assignmentsactive' =>
        $tab === 'assignments',

    'walletactive' =>
        $tab === 'wallet',

    'form' => $mform->render()
];

switch ($tab) {

    case 'entitlements':
        //echo '<pre>';
        //var_dump($templatecontext['entitlements']);
        //die;
        $tabcontent =
            $OUTPUT->render_from_template(
                'local_company/company/tab_entitlements',
                $templatecontext['entitlements']
            );

        break;

    case 'members':

        $templatecontext['members']
            = member_manager::get_company_members($id);
        //$templatecontext['members']['userurl'] = new moodle_url('user/profile.php', ['id'=>$id]);
        //$members = member_manager::get_company_members($id);
        //var_dump($members->url);
        //die;
        $tabcontent =
            $OUTPUT->render_from_template(
                'local_company/company/tab_members',
                $templatecontext
            );

        break;

    case 'assignments':

        $templatecontext['assignments']
            = assignment_manager::get_company_assignments($id);
        
        $tabcontent =
            $OUTPUT->render_from_template(
                'local_company/company/tab_assignments',
                $templatecontext
            );

        break;

    case 'wallet':

        $templatecontext['wallet']
            = wallet_manager::get_summary($id);

        $transactions = transaction_manager::get_company_transactions($id);

        foreach ($transactions as &$t) {

            $t->timecreated = userdate($t->timecreated);

            // format amount display
            if ($t->type === 'credit') {
                $t->amountclass = 'text-success font-weight-bold';
                $t->amount = '+ ' . number_format($t->amount, 2);
            } else {
                $t->amountclass = 'text-danger font-weight-bold';
                $t->amount = '- '.number_format($t->amount, 2);
            }
        }

        $templatecontext['recenttransactions'] = array_slice($transactions, 0, 5);
        $templatecontext['transactions'] = array_values($transactions);
        $templatecontext['hastransactions'] = !empty($transactions);
        $tabcontent =
            $OUTPUT->render_from_template(
                'local_company/company/tab_wallet',
                $templatecontext
            );

        break;
    case 'overview':
        $tabcontent = $OUTPUT->render_from_template(
            'local_company/company/tab_overview',
            $templatecontext
        );
        break;
    default:
        
        $tabcontent = $OUTPUT->render_from_template(
            'local_company/company/tab_overview',
            $templatecontext
        );
}

$templatecontext['tabcontent'] = $tabcontent;

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_company/company_detail',
    $templatecontext
);

echo $OUTPUT->footer();