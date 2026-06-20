<?php
namespace local_auth\output;

use renderable;
use templatable;
use renderer_base;
use context_system;
use moodle_url;
use stored_file;

defined('MOODLE_INTERNAL') || die();

class login implements renderable, templatable {
    public function export_for_template(renderer_base $output) {
        global $CFG;
           
        return [
            'actionurl' => $CFG->wwwroot . '/login/index.php',
            'forgotpasswordurl' => get_config('local_auth', 'forgotpasswordurl'),
            'signupurl' => get_config('local_auth', 'signupurl'),
            'sesskey' => sesskey(),
            'logourl' => 'https://www.integrity-academy.com/team/pluginfile.php/1/theme_edma/main_logo/1723432589/Integrity-Academy-logo.png',
            'mainimageurl' => 'https://www.integrity-academy.com/team/pluginfile.php/2/course/section/6/Picture1.png',
            'logintext' => get_string('login'),
            'usernametext' => get_string('username'),
            'passwordtext' => get_string('password'),
            'forgotpasswordtext' => get_string('forgotten'),
            'noaccounttext' => get_string('dontaccount', 'local_auth'),
            'signuptext' => get_string('createnewaccount', 'local_auth'),
            'loginbuttons' => $this->get_enabled_oauth2_buttons(),
        ];
    }

    private function get_enabled_oauth2_buttons(): array {
        global $DB;
    
        $loginby = get_config('local_auth', 'loginby');
        if (empty($loginby)) {
            return [];
        }
    
        $loginby = is_array($loginby) ? $loginby : unserialize($loginby);
        $buttons = [];
    
        list($sql, $params) = $DB->get_in_or_equal($loginby, SQL_PARAMS_NAMED);
        $records = $DB->get_records_select('oauth2_issuer', "enabled = 1 AND id $sql", $params);
    
        foreach ($records as $issuer) {
            if (empty($issuer->systemaccount)) {
                continue;
            }
    
            $buttons[] = [
                'id' => $issuer->id,
                'name' => $issuer->name,
                'loginurl' => new \moodle_url('/admin/oauth2/login.php', ['id' => $issuer->id]),
                'iconurl' => $issuer->iconurl ?? '', // optional, bisa null
            ];
        }
    
        return $buttons;
    }
    
    
}
