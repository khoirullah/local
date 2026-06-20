<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/user_management/classes/form/certificate_verify_form.php');

require_login();

$context = context_system::instance();
require_capability('local/user_management:verifycertificate', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/user_management/pages/certificate_verify.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('menu_certificate_verify', 'local_user_management'));
$PAGE->set_heading(get_string('menu_certificate_verify', 'local_user_management'));

// Breadcrumb
$PAGE->navbar->add(
    get_string('pluginname', 'local_user_management'),
    new moodle_url('/local/user_management/index.php')
);

$PAGE->navbar->add(
    get_string('menu_certificate_verify', 'local_user_management')
);

$form = new \local_user_management\form\certificate_verify_form();

$result = null;

if ($data = $form->get_data()) {
    require_once($CFG->dirroot . '/local/user_management/classes/service/certificate_verifier.php');

    $verifier = new \local_user_management\service\certificate_verifier();
    $result = $verifier->verify($data->certificate_code);
}

echo $OUTPUT->header();
$form->display();

if ($result !== null) {
    if ($result['valid']) {
        echo $OUTPUT->notification(
            get_string('certificate_valid', 'local_user_management'),
            \core\output\notification::NOTIFY_SUCCESS
        );

        echo html_writer::start_div('card mt-3 p-3');
        echo html_writer::div(get_string('fullname') . ': ' . $result['fullname']);
        echo html_writer::div(get_string('course') . ': ' . $result['coursename']);
        echo html_writer::div(get_string('issuedate', 'local_user_management') . ': ' . $result['issuedate']);
        echo html_writer::end_div();
    } else {
        echo $OUTPUT->notification(
            get_string('certificate_invalid', 'local_user_management'),
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

echo $OUTPUT->footer();
