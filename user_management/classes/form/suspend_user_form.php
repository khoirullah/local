<?php
namespace local_user_management\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

class suspend_user_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;

        // BIG WARNING
        $mform->addElement(
            'static',
            'warning',
            '',
            '<div class="alert alert-danger">
                <strong>WARNING!</strong><br>
                Suspended users <u>cannot</u> be reactivated again.<br>
                This action is <strong>PERMANENT</strong>.
            </div>'
        );

        // User selector
        $mform->addElement(
            'autocomplete',
            'userids',
            get_string('select_users', 'local_user_management'),
            $this->get_users(),
            [
                'multiple' => true,
                //'noselectionstring' => get_string('nousersselected', 'local_user_management')
            ]
        );
        $mform->addRule('userids', null, 'required');

        // Confirmation
        $mform->addElement(
            'advcheckbox',
            'confirm',
            get_string('confirm_suspend', 'local_user_management'),
            get_string('confirm_suspend_desc', 'local_user_management')
        );
        $mform->addRule('confirm', null, 'required');

        $this->add_action_buttons(true, get_string('suspend_permanently', 'local_user_management'));
    }

    private function get_users(): array {
        global $DB, $USER;

        // =========================
        // SITE ADMIN → ALL COURSES
        // =========================
        if (is_siteadmin()) {
            $sql = "
                SELECT DISTINCT u.id,
                    CONCAT(u.firstname, ' ', u.lastname, ' (', u.username, ')') AS fullname
                FROM {user} u
                JOIN {user_enrolments} ue ON ue.userid = u.id
                JOIN {enrol} e ON e.id = ue.enrolid
                WHERE u.deleted = 0
                AND u.suspended = 0
                AND u.id <> :siteadmin
                ORDER BY u.firstname ASC
            ";

            return $DB->get_records_sql_menu($sql, [
                'siteadmin' => 1,
            ]) ?: [];
        }

        // ==================================
        // NON SITE ADMIN → OWN ENROL COURSES
        // ==================================
        $sql = "
            SELECT DISTINCT u.id,
                CONCAT(u.firstname, ' ', u.lastname, ' (', u.username, ')') AS fullname
            FROM {user} u
            JOIN {user_enrolments} ue ON ue.userid = u.id
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {course} c ON c.id = e.courseid
            JOIN {user_enrolments} myue ON myue.enrolid = e.id
            WHERE myue.userid = :userid
            AND u.deleted = 0
            AND u.suspended = 0
            AND u.id <> :siteadmin
            ORDER BY u.firstname ASC
        ";

        return $DB->get_records_sql_menu($sql, [
            'userid'    => $USER->id,
            'siteadmin' => 1,
        ]) ?: [];
    }

}

class suspend_userid_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;

        $userid = $this->_customdata['userid'];
        $user = \core_user::get_user($userid);

        $mform->addElement(
            'static',
            'targetuser',
            get_string('user'),
            fullname($user) . ' (' . $user->email . ')'
        );

        $mform->addElement(
            'static',
            'targetuserdepartment',
            get_string('department'),
            $user->department
        );

        $mform->addElement(
            'hidden',
            'userid',
            $userid
        );
        $mform->setType('userid', PARAM_INT);

        // BIG WARNING
        $mform->addElement(
            'static',
            'warning',
            '',
            '<div class="alert alert-danger">
                <strong>WARNING!</strong><br>
                Suspended users <u>cannot</u> be reactivated again.<br>
                This action is <strong>PERMANENT</strong>.
            </div>'
        );

        // Confirmation
        $mform->addElement(
            'advcheckbox',
            'confirm',
            get_string('confirm_suspend', 'local_user_management'),
            get_string('confirm_suspend_desc', 'local_user_management')
        );
        $mform->addRule('confirm', null, 'required');

        $this->add_action_buttons(true, get_string('suspend_permanently', 'local_user_management'));
    }

}
