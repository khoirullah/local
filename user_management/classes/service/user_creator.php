<?php
namespace local_user_management\service;

defined('MOODLE_INTERNAL') || die();

class user_creator {

    public function create(\stdClass $data): int {
        global $CFG;

        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/lib/moodlelib.php');
        require_once($CFG->dirroot . '/enrol/manual/locallib.php');

        $user = new \stdClass();
        $user->auth       = 'manual';
        $user->confirmed  = 1;
        $user->username   = $data->username;
        $user->password   = hash_internal_user_password($data->password);
        $user->firstname  = $data->firstname;
        $user->lastname   = $data->lastname;
        $user->email      = $data->email;
        $user->institution = $data->institution;
        if (!empty($data->forcepasswordchange)) {
            $user->forcepasswordchange = 1;
        }
        $user->mnethostid = $CFG->mnet_localhost_id;

        // 🚀 THIS was crashing before
        $userid = user_create_user($user, false);
        // Assign ke cohort.
            if (!empty($data->cohort)) {
                cohort_add_member($data->cohort, $userid);
            }

        return $userid;
    }

    /* private function safe_enrol_user(int $userid, int $courseid): void {
        global $DB;

        if (empty($courseid) || $courseid == SITEID) {
            return;
        }

        $context = \context_course::instance($courseid);
        if (is_enrolled($context, $userid)) {
            return;
        }

        $enrol = $DB->get_record('enrol', [
            'courseid' => $courseid,
            'enrol'    => 'manual',
            'status'   => ENROL_INSTANCE_ENABLED
        ]);

        if (!$enrol) {
            return;
        }

        $enrolplugin = enrol_get_plugin('manual');
        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);

        $enrolplugin->enrol_user($enrol, $userid, $studentroleid);
    } */
}

