<?php
namespace local_user_management\service;

defined('MOODLE_INTERNAL') || die();

class bulk_uploader {

    public function process_csv(string $content, int $courseid): string {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/user/selector/lib.php'); // ✅ INI YANG KURANG
        require_once($CFG->dirroot . '/enrol/manual/locallib.php');

        $lines = explode(PHP_EOL, trim($content));
        $header = str_getcsv(array_shift($lines));

        $created = 0;
        $enrolled = 0;

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $row = array_combine($header, str_getcsv($line));

            if (empty($row['username']) || empty($row['email'])) {
                continue;
            }

            // Check user exists.
            $user = $DB->get_record('user', ['username' => $row['username']]);

            if (!$user) {
                $user = new \stdClass();
                $user->auth       = 'manual';
                $user->confirmed  = 1;
                $user->username   = $row['username'];
                $user->password   = !empty($row['password']) ? $row['password'] : generate_password();
                $user->firstname  = $row['firstname'] ?? '';
                $user->lastname   = $row['lastname'] ?? '';
                $user->email      = $row['email'];
                $user->mnethostid = 1;

                $userid = user_create_user($user, false);
                $created++;
            } else {
                $userid = $user->id;
            }

            // Enrol user.
            if (!$this->is_enrolled($userid, $courseid)) {
                $this->enrol_user($userid, $courseid);
                $enrolled++;
            }
        }

        return "{$created} users created, {$enrolled} users enrolled.";
    }
 
    private function enrol_user(int $userid, int $courseid): void {
        global $DB;

        $enrol = $DB->get_record('enrol', [
            'courseid' => $courseid,
            'enrol'    => 'manual',
            'status'   => ENROL_INSTANCE_ENABLED,
        ], '*', MUST_EXIST);

        $plugin = enrol_get_plugin('manual');
        $plugin->enrol_user($enrol, $userid, 5);
    }

    private function is_enrolled(int $userid, int $courseid): bool {
        global $DB;

        return $DB->record_exists_sql(
            "SELECT 1
             FROM {user_enrolments} ue
             JOIN {enrol} e ON e.id = ue.enrolid
             WHERE ue.userid = :userid AND e.courseid = :courseid",
            [
                'userid'   => $userid,
                'courseid' => $courseid,
            ]
        );
    }
}
