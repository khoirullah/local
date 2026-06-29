<?php
/**
 * Provision queue.
 *
 * @package    local_learningproducts
 */

require('../../../config.php');

use local_learningproducts\provision_manager;

require_login();

$context = context_system::instance();

require_capability(
    'moodle/site:config',
    $context
);

$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');

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

$PAGE->set_heading(
    get_string(
        'provisionqueue',
        'local_learningproducts'
    )
);

global $DB;

$sql = "
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

$records = $DB->get_records_sql($sql);

$items = [];

foreach ($records as $record) {

    $items[] = [

        'id' => $record->id,

        'purchaseid' => $record->purchaseid,

        'companyname' => format_string(
            $record->companyname
        ),

        'productname' => format_string(
            $record->productname
        ),

        'templatecourse' => format_string(
            $record->templatecoursename
        ),

        'requestedby' => fullname($record),

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