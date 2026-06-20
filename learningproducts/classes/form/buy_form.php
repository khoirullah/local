<?php
namespace local_learningproducts\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * buy form.
 *
 * Handle buying form.
 *
 * @package local_learningproducts
 */
class buy_form extends \moodleform {

    public function definition() {

        $mform = $this->_form;

        $mform->addElement(
            'text',
            'companyid',
            'Company ID'
        );

        $mform->setType(
            'companyid',
            PARAM_INT
        );

        $mform->addElement(
            'text',
            'qty',
            'Quantity'
        );

        $mform->setDefault(
            'qty',
            1
        );

        $mform->setType(
            'qty',
            PARAM_INT
        );

        $this->add_action_buttons(
            true,
            'Purchase'
        );
    }
}