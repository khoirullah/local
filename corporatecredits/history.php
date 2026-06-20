<?php

require('../../../config.php');

global $DB;

$companyid =
    required_param(
        'companyid',
        PARAM_INT
    );

require_login();

require_capability(
    'local_company:manage',
    context_system::instance()
);

$sql = "
SELECT t.*
FROM {local_company_assignment_transfer} t
ORDER BY t.timecreated DESC
";

$records =
    $DB->get_records_sql(
        $sql
    );

echo $OUTPUT->header();

$table = new html_table();

$table->head = [
    'From',
    'To',
    'Date'
];

foreach ($records as $r) {

    $table->data[] = [
        $r->fromuserid,
        $r->touserid,
        userdate(
            $r->timecreated
        )
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();