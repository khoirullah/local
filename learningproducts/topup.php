<?php

require('../../config.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

require_login();

global $USER, $DB, $OUTPUT, $PAGE;
use local_company\company_manager;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/corporatecredits/topup_validate.php');
$PAGE->set_title(get_string('companyvalidation','local_learningproducts'));
$PAGE->navbar->add(
    get_string('pluginname', 'local_learningproducts'),
    new moodle_url('/local/learningproducts/')
);
$PAGE->navbar->add(
    get_string('companyvalidation','local_learningproducts')
);

$companyfield = profile_user_record($USER->id);

$institutioncompany = trim(
    $USER->institution
    ?: ($companyfield->company ?? '')
);

$company = company_manager::get_user_company($USER->id);

$action = optional_param('action', '', PARAM_ALPHA);

if ($action === 'createcompany' && confirm_sesskey()) {

    $data = [
        'name' => required_param('name', PARAM_TEXT),
        'shortname' => required_param('shortname', PARAM_ALPHANUMEXT),
        'description' => optional_param('description', '', PARAM_RAW)
    ];

    $companyid =
        \local_company\company_manager::create($data);

    redirect(
        new moodle_url(
            '/local/corporatecredits/topup.php',
            [
                'companyid' => $companyid
            ]
        )
    );
}

if ($action === 'requestjoin' && confirm_sesskey()) {

    $request = new stdClass();
    $request->companyid = required_param('companyid', PARAM_INT);
    $request->userid = $USER->id;
    $request->status = 'pending';
    $request->timecreated = time();
    $request->timemodified = time();

    $DB->insert_record(
        'local_company_joinrequest',
        $request
    );

    redirect(
        new moodle_url('/my'),
        'Permintaan telah dikirim ke PIC perusahaan.'
    );
}

echo $OUTPUT->header();

if ($company) {

    $membership = $DB->get_record(
        'local_company_user',
        [
            'companyid' => $company->id,
            'userid' => $USER->id
        ]
    );

    $role = $membership->role ?? 'member';

    if ($role === 'pic') {

        redirect(
            new moodle_url(
                '/local/corporatecredits/topup.php',
                [
                    'companyid' => $company->id
                ]
            )
        );
    }

    echo $OUTPUT->notification(
        'Anda merupakan anggota perusahaan <strong>' .
        format_string($company->name) .
        '</strong>.<br><br>
        Saldo Corporate Credits dikelola oleh PIC perusahaan.
        Jika ingin menggunakan credits silakan hubungi PIC perusahaan.',
        'info'
    );

    echo html_writer::link(
        new moodle_url('/my'),
        'Kembali'
    );

    echo $OUTPUT->footer();
    die();
}

if (empty($institutioncompany)) {

    redirect(
        new moodle_url('/local/company/create.php'),
        'Anda belum memiliki data company. Silakan buat company terlebih dahulu.'
    );
    
}

$existingcompany = $DB->get_record_sql(
    "
    SELECT *
      FROM {local_company}
     WHERE status = 1 AND LOWER(name)=LOWER(?)
        OR LOWER(shortname)=LOWER(?)
    ",
    [
        $institutioncompany,
        $institutioncompany
    ]
);

if ($existingcompany) {

    echo $OUTPUT->notification(
        'Kami menemukan perusahaan yang cocok dengan profil Anda.',
        'info'
    );

    echo html_writer::tag(
        'h3',
        format_string($existingcompany->name)
    );

    echo '
    <form method="post">

        <input type="hidden"
               name="action"
               value="requestjoin">

        <input type="hidden"
               name="companyid"
               value="' . $existingcompany->id . '">

        <button class="btn btn-success">
            Ya, Saya Karyawan Perusahaan Ini
        </button>

    </form>

    <hr>

    <form method="post">

        <input type="hidden"
               name="action"
               value="createcompany">

        <button class="btn btn-secondary">
            Tidak, Buat Company Baru
        </button>

    </form>
    ';
}else {

    echo '<h3 class="mx-3">'.get_string('companyvalidation','local_learningproducts').'</h3>';

    echo $OUTPUT->notification(
        get_string('foundcompany', 'local_learningproducts'),
        'warning'
    );

    $shortname = '';

    $words = preg_split('/\s+/', trim($institutioncompany));

    foreach ($words as $word) {
        $shortname .= strtoupper(substr($word, 0, 1));
    }

    echo '
        <form method="post" class="card p-4 mx-auto" style="max-width:600px;">

            <input type="hidden" name="action" value="createcompany">
            <input type="hidden" name="sesskey" value="' . sesskey() . '">

            <div class="form-group text-left">
                <label for="id_name">
                    <strong>' . get_string('companyname', 'local_company') . '</strong> 
                    <i class="icon fa fa-circle-exclamation text-danger fa-fw " title="Required" role="img" aria-label="Required"></i>
                </label>
                <input type="text"
                    id="id_name"
                    name="name"
                    class="form-control"
                    value="' . s($institutioncompany) . '"
                    required>
            </div>

            <div class="form-group text-left">
                <label for="id_shortname">
                    <strong>' . get_string('shortname', 'local_company') . '</strong>
                    <i class="icon fa fa-circle-exclamation text-danger fa-fw " title="Required" role="img" aria-label="Required"></i>
                </label>
                <input type="text"
                    id="id_shortname"
                    name="shortname"
                    class="form-control"
                    value="' . s($shortname) . '"
                    required>
            </div>

            <div class="form-group form-check text-left">
                <input type="checkbox"
                    class="form-check-input"
                    id="id_agree">
                <label class="form-check-label" for="id_agree">
                    ' . get_string('agree', 'local_company') . '
                </label>
            </div>

            <button type="submit"
                    id="id_submit"
                    class="btn btn-primary"
                    disabled>
                ' . get_string('addcompany', 'local_company') . '
            </button>

        </form>

        <script>
        document.addEventListener("DOMContentLoaded", function() {

            const checkbox = document.getElementById("id_agree");
            const button = document.getElementById("id_submit");
            const nameField = document.getElementById("id_name");
            const shortnameField = document.getElementById("id_shortname");

            checkbox.addEventListener("change", function() {
                
                button.disabled = !this.checked;

                nameField.readOnly = this.checked;
                shortnameField.readOnly = this.checked;

            });

        });
        </script>';
}

echo $OUTPUT->footer();