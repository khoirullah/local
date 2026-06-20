<?php
require('../../config.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot.'/local/company/classes/form/company_form.php');

use local_company\company_manager;
use local_company\form\company_form;

//$id = optional_param('id', 0, PARAM_INT);

require_login();
$context = context_system::instance();

$PAGE->set_url('/local/company/create.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('addcompany', 'local_company'));
$PAGE->set_heading(get_string('addcompany', 'local_company'));

$mform = new company_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/my'));
}

if ($data = $mform->get_data()) {

    $companyid = company_manager::create((array)$data);

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

    if (!is_siteadmin($USER->id)) {
        
        company_manager::update_user_company_profile(
            $USER->id,
            $data->name
        );
    } 

    // Assign user as company PIC.
    $picroleid = $DB->get_field(
        'role',
        'id',
        ['shortname' => 'pic']
    );

    if ($picroleid) {
        role_assign(
            $picroleid,
            $USER->id,
            context_system::instance()->id
        );
    }

    redirect(new moodle_url('/local/corporatecredits/topup.php', ['companyid'=>$companyid]),get_string('created', 'local_company'));
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();