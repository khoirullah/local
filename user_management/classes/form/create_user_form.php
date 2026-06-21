<?php
namespace local_user_management\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

class create_user_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;

        // ===== Basic user info =====
        $mform->addElement('header', 'userinfo', get_string('general'));

        $mform->addElement('text', 'firstname', get_string('firstname'));
        $mform->setType('firstname', PARAM_NOTAGS);
        $mform->addRule('firstname', null, 'required');

        $mform->addElement('text', 'lastname', get_string('lastname'));
        $mform->setType('lastname', PARAM_NOTAGS);
        $mform->addRule('lastname', null, 'required');

        $mform->addElement('text', 'username', get_string('username'));
        $mform->setType('username', PARAM_USERNAME);
        $mform->addRule('username', null, 'required');

        $mform->addElement('text', 'email', get_string('email'));
        $mform->setType('email', PARAM_EMAIL);
        $mform->addRule('email', null, 'required');

        // ===== Password =====
        $mform->addElement('passwordunmask', 'password', get_string('password'));
        $mform->addRule('password', null, 'required');

        // ===== Buttons =====
        $this->add_action_buttons(true, get_string('createuser'));
    }

    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        // ===== Username validation =====
        if (!empty($data['username'])) {

            // duplicate check
            if ($DB->record_exists('user', [
                'username'   => $data['username'],
                'mnethostid' => 1
            ])) {
                $errors['username'] = get_string('usernameexists');
            }
        }


        // ===== Email validation =====
        if (!empty($data['email'])) {
            if ($DB->record_exists('user', [
                'email' => $data['email'],
                'mnethostid' => 1
            ])) {
                $errors['email'] = get_string('emailexists');
            }
        }

        // ===== Password policy (pakai core Moodle) =====
        if (!empty($data['password'])) {
            $errmsg = '';
            if (!check_password_policy($data['password'], $errmsg)) {
                $errors['password'] = $errmsg;
            }
        }

        // ===== Course selection =====
        /* if (empty($data['courseids'])) {
            $errors['courseids'] = get_string('required');
        } */

        return $errors;
    }

    private function get_course_options(): array {
        global $DB,$USER;

        $options = [];
        if (is_siteadmin()) {
            $courses = $DB->get_records_menu(
                'course',
                ['visible' => 1],
                'fullname ASC',
                'id, fullname'
            );
            unset($courses[SITEID]);
        } else {
            $sql = "
                SELECT c.id, c.fullname
                FROM {course} c
                JOIN {enrol} e ON e.courseid = c.id
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE c.id <> :siteid
                AND ue.userid = :userid
                ORDER BY c.fullname ASC
            ";

            $params = [
                'siteid' => SITEID,
                'userid' => $USER->id,
            ];

            $courses = $DB->get_records_sql_menu($sql, $params);
        }


        foreach ($courses as $id => $name) {
            if ($id == SITEID) {
                continue;
            }
            $options[$id] = $name;
        }

        return $options;
    }
}
