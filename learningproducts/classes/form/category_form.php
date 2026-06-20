<?php
namespace local_learningproducts\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Category form.
 *
 * Handle category create/edit form.
 *
 * @package local_learningproducts
 */
class category_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {

        $mform = $this->_form;

        /**
         * Category name.
         */
        $mform->addElement(
            'text',
            'name',
            get_string('categoryname', 'core')
        );

        $mform->setType(
            'name',
            PARAM_TEXT
        );

        $mform->addRule(
            'name',
            null,
            'required'
        );

        /**
         * Description.
         */
        $mform->addElement(
            'editor',
            'description',
            get_string('description')
        );

        $mform->setType(
            'description',
            PARAM_RAW
        );

        /**
         * Sort order.
         */
        $mform->addElement(
            'text',
            'sortorder',
            get_string('sortorder', 'local_learningproducts')
        );

        $mform->setType(
            'sortorder',
            PARAM_INT
        );

        $mform->setDefault(
            'sortorder',
            0
        );

        /**
         * Visibility.
         */
        $mform->addElement(
            'advcheckbox',
            'visible',
            get_string('visible')
        );

        $mform->setDefault(
            'visible',
            1
        );

        /**
         * Submit buttons.
         */
        $this->add_action_buttons();
    }
}