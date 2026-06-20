<?php
namespace local_user_management\service;

defined('MOODLE_INTERNAL') || die();

class user_suspender {

    public function suspend_users(array $userids): int {
        global $DB;

        require_once($GLOBALS['CFG']->dirroot . '/user/profile/lib.php');

        $count = 0;

        foreach ($userids as $userid) {
            $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', IGNORE_MISSING);
            if (!$user) {
                continue;
            }

            // Skip if already permanently suspended
            if ($this->is_permanently_suspended($userid)) {
                continue;
            }

            // Suspend user
            $DB->set_field('user', 'suspended', 1, ['id' => $userid]);

            // Set custom profile field
            profile_save_data((object)[
                'id' => $userid,
                'profile_field_permanent_suspend' => 1
            ]);

            // Kill sessions
            \core\session\manager::kill_user_sessions($userid);

            $count++;
        }

        return $count;
    }

    private function is_permanently_suspended(int $userid): bool {
        global $DB;

        return $DB->record_exists_sql("
            SELECT 1
            FROM {user_info_data} d
            JOIN {user_info_field} f ON f.id = d.fieldid
            WHERE f.shortname = 'permanent_suspend'
              AND d.userid = :userid
              AND d.data = '1'
        ", ['userid' => $userid]);
    }
}
