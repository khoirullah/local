<?php
namespace local_company\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir.'/formslib.php');

class company_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', 'Company Name');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');

        $mform->addElement('text', 'shortname', 'Shortname');
        $mform->setType('shortname', PARAM_ALPHANUMEXT);
        $mform->addRule('shortname', null, 'required');
 
        // DESCRIPTION
        $mform->addElement(
            'editor',
            'description',
            get_string('description')
        );

        $mform->setType('description', PARAM_RAW);

        // LOGO UPLOAD
        $mform->addElement(
            'filepicker',
            'logo',
            'Company Logo',
            null,
            [
                'accepted_types' => ['.png','.jpg','.jpeg','.svg'],
                'maxbytes' => 1024 * 1024 * 2
            ]
        );
        
        $this->add_action_buttons(); 
    }
}