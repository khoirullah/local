<?php
namespace local_user_management\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

class bulk_upload_form extends \moodleform {

    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // ===== CSV Info =====
        $sampleurl = $CFG->wwwroot . '/admin/tool/uploaduser/example.csv';
        $mform->addElement(
            'static',
            'csvsample',
            '',
            '<div class="alert alert-info">
                ' . get_string('bulk_csv_format', 'local_user_management') . '
                <a href="' . $sampleurl . '" target="_blank" class="btn btn-link p-0">
                    ' . get_string('example', 'local_user_management') . '
                </a>
            </div>'
        );

        $mform->addElement('header', 'csvheader', get_string('bulkupload', 'local_user_management'));
        // Download sample CSV

        $mform->addElement(
            'filepicker',
            'userfile',
            get_string('csvfile', 'local_user_management'),
            null,
            [
                'accepted_types' => ['.csv'],
                'maxbytes'       => 0,
            ]
        );
        $mform->addRule('userfile', null, 'required');

        // ===== Company selection =====
        if (is_siteadmin()) {
            $mform->addElement(
                'select',
                'companyid',
                get_string('institution'),
                $this->get_company_options()
            );

            $mform->setType('companyid', PARAM_INT);

            $mform->addRule(
                'companyid',
                get_string('required'),
                'required',
                null,
                'client'
            );
        }
        
        // ===== Buttons =====
        $this->add_action_buttons(true, get_string('uploadusers', 'local_user_management'));
    }


    private function get_company_options(): array {
        global $DB;

        $options = [
            '' => get_string('choose', 'moodle')
        ];

        $companies = $DB->get_records(
            'local_company',
            ['status'=> 1],
            'name ASC',
            'id, name'
        );

        foreach ($companies as $company) {
            $options[$company->id] = $company->name;
        }

        return $options;
    }
}
