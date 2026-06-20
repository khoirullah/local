<?php
// This file is part of Moodle - http://moodle.org/

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/user_management/classes/form/bulk_upload_form.php');

require_login();

$context = context_system::instance();
require_capability('local/user_management:bulkupload', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/user_management/pages/bulk_upload.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('menu_bulk_upload', 'local_user_management'));
$PAGE->set_heading(get_string('menu_bulk_upload', 'local_user_management'));

$mform = new \local_user_management\form\bulk_upload_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/user_management/index.php'));
}

if ($data = $mform->get_data()) {
    require_once($CFG->dirroot . '/local/user_management/classes/service/bulk_uploader.php');

    $uploader = new \local_user_management\service\bulk_uploader();
    $result = $uploader->process_csv($mform->get_file_content('userfile'), $data->courseid);

    redirect(
        new moodle_url('/local/user_management/index.php'),
        get_string('bulk_upload_success', 'local_user_management', $result),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
