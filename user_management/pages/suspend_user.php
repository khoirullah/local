<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/user_management/classes/form/suspend_user_form.php');

require_login();

$context = context_system::instance();
require_capability('local/user_management:suspenduser', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/user_management/pages/suspend_user.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('menu_suspend_user', 'local_user_management'));
$PAGE->set_heading(get_string('menu_suspend_user', 'local_user_management'));

$form = new \local_user_management\form\suspend_user_form();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/user_management/index.php'));
}

if ($data = $form->get_data()) {
    require_once($CFG->dirroot . '/local/user_management/classes/service/user_suspender.php');

    $suspender = new \local_user_management\service\user_suspender();
    $count = $suspender->suspend_users($data->userids);

    redirect(
        new moodle_url('/local/user_management/index.php'),
        get_string('suspend_success', 'local_user_management', $count),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$PAGE->requires->js_init_code(<<<JS
document.addEventListener('DOMContentLoaded', function() {
    const confirmCheckbox = document.querySelector('#id_confirm');
    const submitButton = document.querySelector('#id_submitbutton');

    if (!confirmCheckbox || !submitButton) {
        return;
    }

    // default: disable
    submitButton.disabled = true;
    submitButton.classList.add('disabled');

    confirmCheckbox.addEventListener('change', function() {
        submitButton.disabled = !this.checked;
        submitButton.classList.toggle('disabled', !this.checked);
    });
});
JS);

$PAGE->requires->css(new moodle_url('data:text/css,' . urlencode('
    button[disabled] {
        opacity: 0.5;
        cursor: not-allowed;
    }
')));


echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
