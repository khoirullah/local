<?php
/**
 * Provision queue.
 *
 * @package    local_learningproducts
 */

require('../../../config.php');

use local_learningproducts\provision_manager;
use local_learningproducts\purchase_manager;
use local_company\company_manager;
use local_learningproducts\product_manager;

require_login();

$context = context_system::instance();

require_capability(
    'moodle/site:config',
    $context
);

$PAGE->set_context($context);

$PAGE->set_url(
    new moodle_url(
        '/local/learningproducts/provision/index.php'
    )
);

$PAGE->set_title( 
    get_string(
        'provisionqueue',
        'local_learningproducts'
    )
);

$PAGE->navbar->add(
    get_string('home'),
    new moodle_url(
        '/my'
    )
);
$PAGE->navbar->add(
    get_string('pluginname', 'local_learningproducts'),
    new moodle_url(
        '/local/learningproducts/admin/index.php'
    )
);
$PAGE->navbar->add(
    get_string('provisionqueue', 'local_learningproducts')
);

global $DB;

/* $sql = "
SELECT
    p.*,
    c.name AS companyname,
    lp.name AS productname,
    cc.fullname AS templatecoursename,
    u.firstname,
    u.lastname

FROM {local_company_provision} p

JOIN {local_company} c
    ON c.id = p.companyid

JOIN {local_lp_products} lp
    ON lp.id = p.productid

JOIN {course} cc
    ON cc.id = p.templatecourseid

JOIN {user} u
    ON u.id = p.requestedby

WHERE p.status IN ('pending', 'processing')

ORDER BY p.timecreated ASC
";

$records = $DB->get_records_sql($sql); */
$records = provision_manager::get_pending();


$items = [];

foreach ($records as $record) {
    $course = get_course($record->templatecourseid);
    $company = company_manager::get($record->companyid);
    $product = product_manager::get_product($record->productid);
    $purchase = purchase_manager::get_purchase($record->purchaseid);
    $user = $DB->get_record('user', ['id'=> $record->requestedby]); 

    $items[] = [

        'id' => $record->id,

        'purchaseid' => $record->purchaseid,

        'companyname' => format_string(
            $company->name
        ),

        'productname' => format_string(
            $product->name
        ),

        'templatecourse' => format_string(
            $course->fullname
        ),

        'requestedby' => fullname($user),

        'quota' => $record->quota,

        'startdate' => userdate(
            $record->startdate
        ),

        'enddate' => userdate(
            $record->enddate
        ),

        'status' => ucfirst(
            $record->status
        ),

        'statuspending' =>
            $record->status === 'pending',

        'statusprocessing' =>
            $record->status === 'processing',

        'statuscompleted' =>
            $record->status === 'completed',

        'statusfailed' =>
            $record->status === 'failed',

        'processurl' => (
            new moodle_url(
                '/local/learningproducts/provision/process.php',
                [
                    'id' => $record->id,
                    'sesskey' => sesskey()
                ]
            )
        )->out(false),
        'deleteurl' => (
            new moodle_url(
                '/local/learningproducts/provision/delete.php',
                [
                    'id' => $record->id,
                    'sesskey' => sesskey()
                ]
            )
        )->out(false),
        'duplicateurl' => (
            new moodle_url(
                '/backup/copy.php',
                [
                    'id' => $course->id,
                    'sesskey' => sesskey()
                ]
            )
        )->out(false)

    ];
}

$templatecontext = [

    'items' => $items,

    'hasrecords' => !empty($items)

];

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_learningproducts/provision',
    $templatecontext
);

echo $OUTPUT->footer();