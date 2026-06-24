<?php
namespace local_user_management\service;

defined('MOODLE_INTERNAL') || die();

class user_creator {

    public function create(\stdClass $data): int {
        global $CFG;

        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/lib/moodlelib.php');
        require_once($CFG->dirroot . '/enrol/manual/locallib.php');

        $fullname = $data->fullname;

        $company = \local_company\company_manager::get($data->companyid);
        
        $parts = explode(' ', trim($fullname), 2);
        $data->firstname = $parts[0];
        $data->lastname  = $parts[1] ?? '';
        $data->cohort = $company->cohortid;
        $data->username = $data->email;

        $user = new \stdClass();
        $user->auth       = 'manual';
        $user->confirmed  = 1;
        $user->username   = $data->username;
        $user->password   = hash_internal_user_password($data->password);
        $user->firstname  = $data->firstname;
        $user->lastname   = $data->lastname;
        $user->email      = $data->email;
        $user->department = $data->department || '';
        $user->institution = $company->name;

        if (!empty($data->forcepasswordchange)) {
            $user->forcepasswordchange = 1;
        }
        $user->mnethostid = $CFG->mnet_localhost_id;

        $userid = user_create_user($user, false);

        if (!is_siteadmin($userid)) {
            \local_company\member_manager::add_member(
                $company->id,
                $userid
            );
        }

        return $userid;
    }
}

