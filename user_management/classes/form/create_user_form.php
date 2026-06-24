<?php
namespace local_user_management\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

class create_user_form extends \moodleform {

    public function definition() {
        global $USER;
        $mform = $this->_form;

        // ===== Basic user info =====
        $mform->addElement('header', 'userinfo', get_string('general'));

        $mform->addElement('text', 'fullname', get_string('fullname'));
        $mform->setType('fullname', PARAM_NOTAGS);
        $mform->addRule('fullname', null, 'required');

        $mform->addElement('text', 'email', get_string('email'));
        $mform->setType('email', PARAM_EMAIL);
        $mform->addRule('email', null, 'required');

        $mform->addElement('text', 'department', get_string('department'));
        
        // ===== Role selection =====
        $roleoptions = [
            'employee' => get_string('employee', 'local_user_management'),
            'hod'      => get_string('hod', 'local_user_management'),
        ];

        // PIC hanya bisa dipilih oleh siteadmin
        if (is_siteadmin()) {
            $roleoptions['pic'] = get_string('pic', 'local_user_management');
        }

        $mform->addElement(
            'select',
            'role',
            get_string('role'),
            $roleoptions
        );

        $mform->setDefault('role', 'employee');
        $mform->addRule('role', null, 'required', null, 'client');

        // ===== Institution (hidden from PIC) =====
        $mform->addElement('hidden', 'institution', $USER->institution);
        $mform->setType('institution', PARAM_TEXT);

        // ===== Password =====
        $mform->addElement('passwordunmask', 'password', get_string('password'));
        $mform->addRule('password', null, 'required');

        // ===== Force password change =====
        $mform->addElement(
            'advcheckbox',
            'forcepasswordchange',
            '',
            get_string('forcepasswordchange')
        );
        $mform->setDefault('forcepasswordchange', 1);

        // ===== Company selection =====
        if (is_siteadmin()) {
            $mform->addElement(
                'select',
                'companyid',
                get_string('institution'),
                $this->get_company_options()
            );

            $mform->addRule(
                'companyid',
                get_string('required'),
                'required',
                null,
                'client'
            );
        }

        // ===== Buttons =====
        $this->add_action_buttons(true, get_string('createuser'));
    }

    public function validation($data, $files) {
        global $DB,$CFG;

        $errors = parent::validation($data, $files);

        // ===== Email validation =====
        if (!empty($data['email'])) {

            // Format email.
            if (!validate_email($data['email'])) {
                $errors['email'] = get_string('invalidemail');
            }

            // Email sudah digunakan.
            else if ($DB->record_exists('user', [
                'email' => $data['email'],
                'mnethostid' => $CFG->mnet_localhost_id
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

        return $errors;
    }

    private function get_company_options(): array {
        global $DB;

        $options = [
            '' => get_string('choose', 'moodle')
        ];

        $companies = $DB->get_records(
            'local_company',
            ['status'=> 1],
            'name ASC',
            'id, name'
        );

        foreach ($companies as $company) {
            $options[$company->id] = $company->name;
        }

        return $options;
    }
}
