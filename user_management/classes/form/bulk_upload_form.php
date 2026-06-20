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

        $mform->addElement('header', 'enrolinfo', get_string('indicator:nostudent'));

        // ===== Course =====
        $courses = $this->get_course_options();
        $mform->addElement('select', 'courseid', get_string('course'), $courses);
        $mform->setType('courseid', PARAM_INT);
        $mform->addRule('courseid', null, 'required');

        
        // ===== Buttons =====
        $this->add_action_buttons(true, get_string('uploadusers', 'local_user_management'));
    }


    private function get_course_options(): array {
        global $DB,$USER;

        $options = [];
        if (is_siteadmin()) {
            $courses = $DB->get_records_menu(
                'course',
                ['visible' => 1],
                'fullname ASC',
                'id, fullname'
            );
            unset($courses[SITEID]);
        } else {
            $sql = "
                SELECT c.id, c.fullname
                FROM {course} c
                JOIN {enrol} e ON e.courseid = c.id
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE c.id <> :siteid
                AND ue.userid = :userid
                ORDER BY c.fullname ASC
            ";

            $params = [
                'siteid' => SITEID,
                'userid' => $USER->id,
            ];

            $courses = $DB->get_records_sql_menu($sql, $params);
        }


        foreach ($courses as $id => $name) {
            if ($id == SITEID) {
                continue;
            }
            $options[$id] = $name;
        }

        return $options;
    }
}
