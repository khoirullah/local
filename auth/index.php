<?php
require('../../config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/auth/index.php'));
$PAGE->set_title('Hello World');
$PAGE->set_heading('Hello World');

echo $OUTPUT->header();
echo $OUTPUT->heading('Hello, world from local_auth!');
echo $OUTPUT->footer();
