<?php
namespace local_user_management\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

class certificate_verify_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;

        //$mform->addElement('header', 'verify', get_string('menu_certificate_verify', 'local_user_management'));

        $mform->addElement(
            'text',
            'certificate_code',
            get_string('certificate_code', 'local_user_management')
        );
        $mform->setType('certificate_code', PARAM_ALPHANUMEXT);
        $mform->addRule('certificate_code', null, 'required');

        $this->add_action_buttons(false, get_string('verify', 'local_user_management'));
    }
}
