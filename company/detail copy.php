<?php
require('../../config.php');

use local_company\company_manager;
use local_company\subscription_manager;
use local_company\form\subscription_form;

$id = optional_param('id', 0, PARAM_INT);
$companyid = optional_param('companyid', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);
$status = optional_param('status', '', PARAM_TEXT);
$courseid = optional_param('course', 0, PARAM_INT);
$year     = optional_param('year', date('Y'), PARAM_INT);

if (!$id && $companyid) {
    $id = $companyid;
}

if (!$id) {
    throw new moodle_exception('missingparam', 'error');
}

require_login();

$context = context_system::instance();
require_capability('local/company:manage', $context);

$PAGE->set_url('/local/company/detail.php', ['id'=>$id]);
$PAGE->set_context($context);

$company = company_manager::get($id);

$PAGE->set_title($company->name);
$PAGE->set_heading($company->name);

$PAGE->navbar->add(
    get_string('companydir', 'local_company'),
    new moodle_url('/local/company/index.php')
);

$PAGE->navbar->add(
    get_string('companymanagement', 'local_company')
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
    $companyid,
    $courseid,
    $year
);

$templatecontext['chartlabels'] = json_encode($chart['labels']);
$templatecontext['chartdata']   = json_encode($chart['data']);

$templatecontext['years'] = subscription_manager::get_year_list();
/* =============================
   DATA
============================= */

$subscriptions = subscription_manager::get_by_company($id, $search, $status);

$subs = [];
$total = count($subscriptions);
$years = [];
$labels = [];
$data = [];
$completionlabels = [];
$completiondata = [];

//$chart = subscription_manager::get_enrollment_chart_data($id, optional_param('course', 0, PARAM_INT), optional_param('year', date('Y'), PARAM_INT));

$labels = $chart['labels'];
$data = $chart['data'];

$currentyear = date('Y');

for ($i = 0; $i < 5; $i++) {
    $years[] = [
        'year' => $currentyear - $i
    ];
}

if ($subscriptions) {

    foreach ($subscriptions as $sub) {

        $used = subscription_manager::get_used_count($id, $sub->courseid);

        $percent = $sub->quota > 0
            ? round(($used/$sub->quota)*100)
            : 0;

        $substatus = (time() > $sub->enddate)
            ? 'Expired'
            : 'Active';

        $deleteurl = new moodle_url('/local/company/detail.php', [
            'id' => $id,
            'deletesub' => $sub->id,
            'sesskey' => sesskey()
        ]);

        $subs[] = [
            'courseid' => $sub->courseid,
            'courseurl' => $CFG->wwwroot.'/course/view.php?id='.$sub->courseid,
            'course' => $sub->fullname,
            'category' => $sub->category,
            'quota' => $sub->quota,
            'used' => $used,
            'total' => $total,
            'left' => $sub->quota - $used,
            'startdate' => userdate($sub->startdate, '%d %b %Y'),
            'enddate' => userdate($sub->enddate, '%d %b %Y'),
            'utilization' => $percent.'%',
            'completion' => round($sub->completionrate) . '%',
            'progress' => round($sub->completionrate),
            'status' => $substatus,
            'deleteurl' => $deleteurl->out(),
        ];
    }
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

foreach ($subs as $s) {

    $completionlabels[] = $s['course'];
    $completiondata[] = (int) str_replace('%','',$s['completion']);
}

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

//var_dump($files);
//echo 'files: '.$files;

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
$company->logo = $logo;

$templatecontext = [
    'company' => $company,
    'companyname' => $company->name,
    'description'  => $company->description,
    'subscriptions' => $subs,
    'hassubscriptions' => !empty($subs),
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
    
    'form' => $mform->render()
];

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_company/company_detail',
    $templatecontext
);
//echo $templatecontext['logo'];
//var_dump($templatecontext['logo']);
echo $OUTPUT->footer();