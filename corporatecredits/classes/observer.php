<?php

namespace local_credits;

defined('MOODLE_INTERNAL') || die();

class observer {

    public static function user_created(
        \core\event\user_created $event
    ) {

        manager::create_wallet($event->objectid);
    }
}