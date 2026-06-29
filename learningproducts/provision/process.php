<?php
/**
 * Process provisioning request.
 *
 * @package local_learningproducts
 */

require('../../../config.php');

use local_learningproducts\provision_manager;
use local_learningproducts\purchase_manager;
use local_learningproducts\course_manager;
use local_learningproducts\enrolment_manager;
use local_company\subscription_manager;
use local_company\company_manager;

require_login();
require_sesskey();

$context = context_system::instance();

require_capability(
    'moodle/site:config',
    $context
);

$id = required_param('id', PARAM_INT);

global $DB, $USER;

$record = provision_manager::get($id);

if (!$record) {
    throw new moodle_exception('invalidrecord');
}

$transaction = $DB->start_delegated_transaction();

try {

    if ($record->status !== 'pending') {
        throw new moodle_exception(
            'Provision already processed.'
        );
    }

    //--------------------------------------------------
    // Processing
    //--------------------------------------------------

    $record->status = 'processing';
    $record->processedby = $USER->id;
    $record->timemodified = time();

    $DB->update_record(
        'local_company_provision',
        $record
    );

    //--------------------------------------------------
    // Duplicate course
    //--------------------------------------------------

    $newcourseid = course_manager::duplicate_course(
        $record->templatecourseid
    );

    //--------------------------------------------------
    // Rename
    //--------------------------------------------------

    course_manager::rename_for_company(
        $newcourseid,
        $record->companyid
    );

    //--------------------------------------------------
    // Move category
    //--------------------------------------------------

    course_manager::move_to_company_category(
        $newcourseid,
        $record->companyid
    );

    //--------------------------------------------------
    // Self enrol
    //--------------------------------------------------

    $enrolkey = enrolment_manager::generate_enrol_key();

    enrolment_manager::create_self_enrol(
        $newcourseid,
        $enrolkey
    );

    //--------------------------------------------------
    // Subscription
    //--------------------------------------------------

    $subscriptionid = subscription_manager::create([
        'companyid' => $record->companyid,
        'courseid' => $newcourseid,
        'quota' => $record->quota,
        'startdate' => $record->startdate,
        'enddate' => $record->enddate,
    ]);

    //--------------------------------------------------
    // Company PIC
    //--------------------------------------------------

    $admins = company_manager::get_company_admins(
        $record->companyid
    );

    foreach ($admins as $admin) {

        enrolment_manager::enrol_manual(
            $admin->userid,
            $newcourseid
        );

        // TODO:
        // notification_manager::send_ready_email(
        //     $admin->userid,
        //     $newcourseid,
        //     $enrolkey
        // );
    }

    //--------------------------------------------------
    // Completed
    //--------------------------------------------------

    $record->courseid = $newcourseid;
    $record->subscriptionid = $subscriptionid;
    $record->status = 'completed';
    $record->notes = 'Provision completed';
    $record->timemodified = time();

    $DB->update_record(
        'local_company_provision',
        $record
    );

    $transaction->allow_commit();

    redirect(
        new moodle_url(
            '/local/learningproducts/provision/index.php'
        ),
        get_string('changessaved'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );

} catch (Throwable $e) {

    $transaction->rollback($e);

    $record->status = 'failed';
    $record->processedby = $USER->id;
    $record->notes = substr(
        $e->getMessage(),
        0,
        1000
    );
    $record->timemodified = time();

    $DB->update_record(
        'local_company_provision',
        $record
    );

    redirect(
        new moodle_url(
            '/local/learningproducts/provision/index.php'
        ),
        $e->getMessage(),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}