<?php
namespace local_auth\output;

use plugin_renderer_base;

defined('MOODLE_INTERNAL') || die();

class renderer extends plugin_renderer_base {

    public function render_login(login $login) {
        return $this->render_from_template('local_auth/login', $login->export_for_template($this));
    }

    public function render_signup(signup $signup) {
        return $this->render_from_template('local_auth/signup', $signup->export_for_template($this));
    }
    
}
