<?php
namespace local_user_management\service;

defined('MOODLE_INTERNAL') || die();

class bulk_uploader {

    public function process_csv(string $content, int $companyid): string {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/user/selector/lib.php'); // ✅ INI YANG KURANG
        require_once($CFG->dirroot . '/enrol/manual/locallib.php');

        $lines = explode(PHP_EOL, trim($content));
        $header = str_getcsv(array_shift($lines));
       
        $created = 0;
        $enrolled = 0;

        $company = \local_company\company_manager::get($companyid);
        
        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $row = array_combine($header, str_getcsv($line));

            if (empty($row['fullname']) || empty($row['email'])) {
                continue;
            }

            $fullname = $row['fullname'];

            $parts = explode(' ', trim($fullname), 2);
            $row['firstname'] = $parts[0];
            $row['lastname']  = $parts[1] ?? '';
            $row['cohort'] = $company->cohortid;
            $row['username'] = $row['email'];
            // Check user exists.
            $user = $DB->get_record('user', ['username' => $row['email']]);

            if (!$user) {
                $user = new \stdClass();
                $user->auth         = 'manual';
                $user->confirmed    = 1;
                $user->username     = $row['username'];
                $user->password     = hash_internal_user_password($row['password']);
                $user->firstname    = $row['firstname'];
                $user->lastname     = $row['lastname'];
                $user->email        = $row['email'];
                $user->department   = $row['department'];
                $user->institution  = $company->name;
                $user->forcepasswordchange = 1;
                $user->mnethostid = 1;

                $user->mnethostid = $CFG->mnet_localhost_id;

                $userid = user_create_user($user, false);
                $created++;
            } else {
                $userid = $user->id;
            }

            if (!is_siteadmin($userid)) {
                \local_company\member_manager::add_member(
                    $company->id,
                    $userid,
                    $row['role']
                );
            }
        }

        return "{$created} users created, {$enrolled} users enrolled.";
    }
}
