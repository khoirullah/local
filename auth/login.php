<?php
require_once('../../config.php');

// Kalau user udah login, langsung lempar ke front page
if (isloggedin() && !isguestuser()) {
    redirect($CFG->wwwroot); // atau $CFG->wwwroot . '/my/' untuk dashboard
}

$PAGE->set_url(new moodle_url('/local/auth/login.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Login');

$renderer = $PAGE->get_renderer('local_auth'); // <- harus bisa jalan sekarang
echo $OUTPUT->header();
echo $renderer->render_login(new \local_auth\output\login());
echo $OUTPUT->footer();
