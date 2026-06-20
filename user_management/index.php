<?php
// This file is part of Moodle - http://moodle.org/

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/user_management:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/user_management/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_user_management'));
$PAGE->set_heading(get_string('menu', 'local_user_management'));

/* ===== Breadcrumb ===== */
$PAGE->navbar->add(
    get_string('pluginname', 'local_user_management')
);
$renderer = $PAGE->get_renderer('local_user_management');

echo $OUTPUT->header();

// Render main menu cards.
echo $renderer->render_main_menu();

echo $OUTPUT->footer();
