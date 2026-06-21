<?php
// This file is part of Moodle - http://moodle.org/

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/user_management/classes/form/create_user_form.php');

require_login();

$context = context_system::instance();
require_capability('local/user_management:createuser', $context);
use local_company\company_manager;
$company = \local_company\company_manager::get_user_company($USER->id);
        
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/user_management/pages/create_user.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('menu_create_user', 'local_user_management'));
$PAGE->set_heading(get_string('menu_create_user', 'local_user_management'), $company->name);

$mform = new \local_user_management\form\create_user_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/user_management/index.php'));
}

if ($data = $mform->get_data()) {
    require_once($CFG->dirroot . '/local/user_management/classes/service/user_creator.php');

    $creator = new \local_user_management\service\user_creator();
    $creator->create($data);

    redirect(
        new moodle_url('/local/user_management/index.php'),
        get_string('user_created_success', 'local_user_management'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}
$PAGE->requires->js_init_code(<<<JS
require([], function() {
    const usernameInput = document.querySelector('input[name="username"]');
    if (!usernameInput) return;

    // Force lowercase on input
    usernameInput.addEventListener('input', function () {
        this.value = this.value.toLowerCase();
    });

    // Detect capslock
    usernameInput.addEventListener('keydown', function (e) {
        if (e.getModifierState && e.getModifierState('CapsLock')) {
            this.setCustomValidity('Username harus huruf kecil (lowercase)');
        } else {
            this.setCustomValidity('');
        }
    });
});
JS);


echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
