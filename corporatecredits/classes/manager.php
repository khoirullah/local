<?php

namespace local_credits;

defined('MOODLE_INTERNAL') || die();

class manager {

    public static function create_wallet(int $userid): int {

        global $DB;

        if ($wallet = $DB->get_record('local_credits_wallet', [
            'userid' => $userid
        ])) {

            return $wallet->id;
        }

        $record = new \stdClass();
        $record->userid = $userid;
        $record->balance = 0;
        $record->timecreated = time();
        $record->timemodified = time();

        return $DB->insert_record('local_credits_wallet', $record);
    }
}