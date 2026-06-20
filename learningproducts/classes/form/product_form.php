<?php
namespace local_learningproducts\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Product form.
 *
 * Handle create/edit product form.
 *
 * @package local_learningproducts
 */
class product_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {

        $mform = $this->_form;

        $mform->addElement(
            'text',
            'name',
            get_string('productname', 'local_learningproducts')
        );

        $mform->setType('name', PARAM_TEXT);

        $mform->addRule(
            'name',
            null,
            'required'
        );

        $mform->addElement(
            'text',
            'shortname',
            get_string('shortname', 'local_learningproducts')
        );

        $mform->setType('shortname', PARAM_TEXT);

        $mform->addElement(
            'select',
            'categoryid',
            get_string('category', 'core'),
            \local_learningproducts\category_manager
                ::get_category_options()
        );

        $mform->addElement(
            'select',
            'type',
            get_string('type', 'local_learningproducts'),
            [
                'single' => 'Single Product',
                'bundle' => 'Bundle Product'
            ]
        );

        $mform->addElement(
            'filemanager',
            'productimage',
            get_string(
                'productimage',
                'local_learningproducts'
            ),
            null,
            [
                'subdirs' => 0,
                'maxfiles' => 1,
                'accepted_types' => ['image'],
                'maxbytes' => 0
            ]
        );

        $mform->addElement(
            'editor',
            'description',
            get_string('description')
        );

        $mform->setType('description', PARAM_RAW);

        $mform->addElement(
            'text',
            'price',
            get_string('price', 'local_learningproducts')
        );

        $mform->setType('price', PARAM_FLOAT);

        $mform->addElement(
            'advcheckbox',
            'visible',
            get_string('visible')
        );

        /**
         * Hidden ID for edit mode.
         */
        $mform->addElement('hidden', 'id');

        $mform->setType('id', PARAM_INT);

        $mform->setDefault('visible', 1);

        $this->add_action_buttons();
    }
}