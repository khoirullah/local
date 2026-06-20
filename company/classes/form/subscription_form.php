<?php
namespace local_company\form;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');

class subscription_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'companyid');
        $mform->setType('companyid', PARAM_INT);

        global $DB;

        $sql = "SELECT *
                FROM {course}
                WHERE visible = 1
                AND id <> :siteid
                AND category <> 0
                AND format <> :siteformat
                ORDER BY fullname";

        $courses = $DB->get_records_sql($sql, [
            'siteid' => SITEID,
            'siteformat' => 'site'
        ]);
        $options = [];
        foreach ($courses as $course) {
            $options[$course->id] = $course->fullname;
        }

        $mform->addElement('select', 'courseid', 'Course', $options);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('text', 'quota', 'Quota');
        $mform->setType('quota', PARAM_INT);

        $mform->addElement('date_selector', 'startdate', 'Start Date');
        $mform->addElement('date_selector', 'enddate', 'End Date');

        $this->add_action_buttons();
    }
}