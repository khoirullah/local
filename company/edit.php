<?php
require('../../config.php');

require_once($CFG->dirroot.'/local/company/classes/form/company_form.php');

use local_company\company_manager;
use local_company\form\company_form;

$id = optional_param('id', 0, PARAM_INT);

require_login();
$context = context_system::instance();
require_capability('local/company:manage', $context);

$PAGE->set_url('/local/company/edit.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_title('Edit Company');
$PAGE->set_heading('Company');

$mform = new company_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/company/index.php'));
}

if ($data = $mform->get_data()) {

    if ($data->id) {
        $companyid = company_manager::update($data->id, (array)$data);
    } else {
        $companyid = company_manager::create((array)$data);
    }

    // HANDLE FILE UPLOAD
    if ($file = $mform->get_new_filename('logo')) {

        $context = context_system::instance();
        $fs = get_file_storage();

        // delete old logo (if update)
        $fs->delete_area_files(
            $context->id,
            'local_company',
            'logo',
            $companyid
        );

        $fs->create_file_from_pathname([
            'contextid' => $context->id,
            'component' => 'local_company',
            'filearea'  => 'logo',
            'itemid'    => $companyid,
            'filepath'  => '/',
            'filename'  => $file
        ], $mform->save_temp_file('logo'));
    }

    redirect(new moodle_url('/local/company/index.php'));
}

if ($id) {
    $company = company_manager::get($id);
    $mform->set_data($company);
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();