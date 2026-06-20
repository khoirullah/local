<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/moodlelib.php');

global $DB;

// Jika user sudah login, redirect ke home/dashboard.
if (isloggedin() && !isguestuser()) {
    redirect(new moodle_url('/'));
}

$PAGE->set_url(new moodle_url('/local/auth/signup.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('signup', 'local_auth'));
$PAGE->set_heading(get_string('signup', 'local_auth'));

$errors = [];
$data = (object) [
    'fullname' => optional_param('fullname', '', PARAM_TEXT),
    'email' => optional_param('email', '', PARAM_RAW),
    'email2' => optional_param('email2', '', PARAM_RAW),
    'password' => optional_param('password', '', PARAM_RAW),
    'signup_token' => optional_param('signup_token', '', PARAM_TEXT),
    'profile_field_company' => optional_param('profile_field_company', '', PARAM_TEXT),
    '_qf__login_signup_form' => optional_param('_qf__login_signup_form', '', PARAM_RAW),
    'mform_isexpanded_id_category_2' => optional_param('mform_isexpanded_id_category_2', '', PARAM_RAW),
    'firstname' => optional_param('firstname', '', PARAM_TEXT),
    'lastname' => optional_param('lastname', '', PARAM_TEXT),
    'username' => optional_param('username', '', PARAM_RAW),
    '_qf__auth_enrolkey_form_enrolkey_signup_form' => optional_param('mform_isexpanded_id_category_2', '', PARAM_RAW),
    'sesskey' => optional_param('sesskey', '', PARAM_RAW),
];
 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {

    // Validasi fullname
    if (strlen(trim($data->fullname)) < 3) {
        $errors['fullname'] = get_string('val_fullname', 'local_auth');
    }

    // Validasi email
    if (!validate_email($data->email)) {
        $errors['email'] = get_string('val_email', 'local_auth');
    } else if ($DB->record_exists('user', ['email' => $data->email])) {
        $errors['email'] = get_string('val_email_reg', 'local_auth');
    }

    // Validasi email konfirmasi
    if ($data->email !== $data->email2) {
        $errors['email2'] = get_string('val_email2', 'local_auth');
    }

    // Validasi password
    if (strlen($data->password) < 6) {
        $errors['password'] = get_string('val_password', 'local_auth');
    }

    // Validasi enrolment key jika diisi
    if (!empty($data->signup_token)) {
        $token = trim($data->signup_token);
        $exists = $DB->record_exists('enrol', [
            'enrol' => 'manual',
            'status' => 0,
            'password' => $token,
        ]);

        if (!$exists) {
            $errors['signup_token'] = get_string('val_key', 'local_auth');
        }
    }

    // Username akan diambil dari email, jadi kita juga validasi sekarang
    $username = strtolower(trim($data->email));
    if ($DB->record_exists('user', ['username' => $username])) {
        $errors['email'] = format_text(get_string('emailalreadytaken', 'local_auth'), FORMAT_HTML);
    }


    // Kalau gak ada error
    if (empty($errors)) {
        echo $OUTPUT->header();
        echo html_writer::start_tag('form', [
            'id' => 'postredirectform',
            'method' => 'post',
            'action' => new moodle_url('/login/signup.php') // atau signup_finish.php
        ]);

        foreach ($data as $key => $value) {
            echo html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => $key,
                'value' => s($value)
            ]);
        }

        echo html_writer::end_tag('form');
        echo "<script>document.getElementById('postredirectform').submit();</script>";
        echo $OUTPUT->footer();
        exit;
        /* redirect(new moodle_url('/login/signup.php', ['success' => 1]));
        exit; */
    }
}

echo $OUTPUT->header();
echo $PAGE->get_renderer('local_auth')->render_signup(new \local_auth\output\signup($data, $errors));
echo $OUTPUT->footer();