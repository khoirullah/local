<?php

require('../../../config.php');

$productid = required_param('productid', PARAM_INT);

require_login();

$context = context_system::instance();

require_capability(
    'local/learningproducts:manage',
    $context
);

$product = \local_learningproducts\product_manager::get_product($productid);

if (!$product) {
    throw new moodle_exception('invalidproduct');
}

$PAGE->set_url(
    new moodle_url(
        '/local/learningproducts/admin/mapping.php',
        ['productid' => $productid]
    )
);

$PAGE->set_context($context);

$title = $product->type === 'bundle'
    ? get_string('bundlemap', 'local_learningproducts')
    : get_string('coursemap', 'local_learningproducts');

$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add(
    get_string('administrationsite'),
    new moodle_url('/admin/search.php')
);
$PAGE->navbar->add(
    get_string('manage', 'local_learningproducts'),
    new moodle_url('/local/learningproducts/admin/index.php')
);
$PAGE->navbar->add(
    get_string('bundlemap', 'local_learningproducts')
);
echo $OUTPUT->header();

//
// HANDLE DELETE / UNLINK
//
if (
    optional_param('action', '', PARAM_ALPHA) === 'delete'
) {

    require_sesskey();

    $mappingid = required_param('mappingid', PARAM_INT);

    if ($product->type === 'bundle') {

        \local_learningproducts\bundle_manager
            ::remove_product($mappingid);

    } else {

        \local_learningproducts\course_mapper
            ::remove_mapping($mappingid);
    }

    redirect($PAGE->url);
}

//
// HANDLE SUBMIT
//
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    require_sesskey();

    $action = optional_param(
        'action',
        'add',
        PARAM_ALPHA
    );

    /**
     * REMOVE MAPPING
     */
    if ($action === 'bulkdelete') {

        $mappingids = required_param_array(
            'mappingid',
            PARAM_INT
        );

        foreach ($mappingids as $mappingid) {

            \local_learningproducts\bundle_manager
                ::remove_product($mappingid);
        }
    }elseif ($action === 'bulkdeletecourse') {

        $mappingids = required_param_array(
            'mappingid',
            PARAM_INT
        );

        foreach ($mappingids as $mappingid) {

            \local_learningproducts\course_mapper
                ::remove_mapping($mappingid);
        }
    }
    /**
     * ADD MAPPING
     */
    else {

        if ($product->type === 'bundle') {

            $childproductids = required_param_array(
                'childproductid',
                PARAM_INT
            );

            foreach ($childproductids as $childproductid) {

                \local_learningproducts\bundle_manager
                    ::add_product(
                        $productid,
                        $childproductid
                    );
            }

        } else {

            $courseids = required_param_array(
                'courseid',
                PARAM_INT
            );

            foreach ($courseids as $courseid) {

                \local_learningproducts\course_mapper
                    ::add_course(
                        $productid,
                        $courseid
                    );
            }
        }
    }

    redirect($PAGE->url);
}

//
// SINGLE PRODUCT = COURSE MAPPING
//
if ($product->type !== 'bundle') {

    /**
     * Existing mapped courses.
     */
    $mappedcourses =
        \local_learningproducts\course_mapper
            ::get_courses($productid);

    $existingids = [];

    foreach ($mappedcourses as $course) {
        $existingids[] = $course->id;
    }

    /**
     * Potential courses.
     */
    $courses = get_courses();

    $options = [];

    foreach ($courses as $course) {

        if ($course->id == SITEID) {
            continue;
        }

        /**
         * Prevent duplicate mapping.
         */
        if (in_array($course->id, $existingids)) {
            continue;
        }

        $options[$course->id] =
            format_string($course->fullname);
    }

    /**
     * Existing options.
     */
    $existingoptions = [];

    foreach ($mappedcourses as $course) {

        $existingoptions[$course->mappingid] =
            format_string($course->fullname);
    }

    echo html_writer::start_div(
        'card'
    );
    echo html_writer::start_div(
        'card-header text-center'
    );
    echo $OUTPUT->heading(
        format_string($product->name)
    );
    echo html_writer::end_div();
    
    echo html_writer::start_div(
        'card-body text-center'
    );
    echo html_writer::start_div(
        'row justify-content-center'
    );

    /**
     * EXISTING COURSES
     */
    echo html_writer::start_div(
        'col-md-5'
    );

    echo html_writer::tag(
        'h5',
        get_string('existingcourse', 'local_learningproducts')
    );

    echo html_writer::start_tag('form', [
        'method' => 'post'
    ]);

    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey()
    ]);

    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'action',
        'value' => 'bulkdeletecourse'
    ]);

    echo html_writer::select(
        $existingoptions,
        'mappingid[]',
        '',
        null,
        [
            'multiple' => true,
            'size' => 20,
            'id' => 'existingcourses',
            'class' => 'form-control w-100'
        ]
    );

    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'value' => get_string('remove', 'local_learningproducts').' ▶',
        'id' => 'removecoursebtn',
        'class' => 'btn btn-secondary mt-2',
        'disabled' => 'disabled'
    ]);

    echo html_writer::end_tag('form');

    echo html_writer::end_div();

    /**
     * POTENTIAL COURSES
     */
    echo html_writer::start_div(
        'col-md-5'
    );

    echo html_writer::tag(
        'h5',
        get_string('potentialcourse', 'local_learningproducts')
    );

    echo html_writer::start_tag('form', [
        'method' => 'post'
    ]);

    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey()
    ]);

    echo html_writer::select(
        $options,
        'courseid[]',
        '',
        null,
        [
            'multiple' => true,
            'size' => 20,
            'id' => 'potentialcourses',
            'class' => 'form-control w-100'
        ]
    );

    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'value' => '◀ '.get_string('add', 'local_learningproducts'),
        'id' => 'addcoursebtn',
        'class' => 'btn btn-secondary mt-2',
        'disabled' => 'disabled'
    ]);

    echo html_writer::end_tag('form');

    echo html_writer::end_div();

    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

//
// BUNDLE PRODUCT = PRODUCT MAPPING
//
} else {

    $bundleproducts =
        \local_learningproducts\bundle_manager
            ::get_bundle_products($productid);

    $existingids = [];

    foreach ($bundleproducts as $bp) {
        $existingids[] = $bp->id;
    }

    $products =
        \local_learningproducts\product_manager
            ::get_products();

    $options = [];

    echo html_writer::start_div(
        'card'
    );
    echo html_writer::start_div(
        'card-header text-center'
    );
    echo $OUTPUT->heading(
        format_string($product->name)
    );
    foreach ($products as $p) {

        /**
         * Prevent self mapping.
         */
        if ($p->id == $productid) {
            continue;
        }

        /**
         * Prevent bundle inside bundle.
         */
        if ($p->type === 'bundle') {
            continue;
        }

        /**
         * Prevent duplicate mapping.
         */
        if (in_array($p->id, $existingids)) {
            continue;
        }

        $options[$p->id] =
            format_string($p->name);
    }

    $existingoptions = [];

    foreach ($bundleproducts as $bp) {

            $existingoptions[$bp->mappingid] = format_string($bp->name);
    }

    echo html_writer::end_div();

    echo html_writer::start_div(
        'card-body'
    );

        echo html_writer::start_div(
            'row justify-content-center mt-4'
        );

        // REMOVE
        echo html_writer::start_tag('form', [
            'method' => 'post'
        ]);

        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey()
        ]);

        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'action',
            'value' => 'bulkdelete'
        ]);

        echo html_writer::tag(
            'h5',
            get_string('existingproduct', 'local_learningproducts')
        );

        echo html_writer::select(
            $existingoptions,
            'mappingid[]',
            '',
            null,
            [
                'multiple' => true,
                'size' => 20,
                'id' => 'existingproducts',
                'class' => 'form-control w-100'
            ]
        );

        echo html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => get_string('remove', 'local_learningproducts').' ▶',
            'id' => 'removebtn',
            'class' => 'btn btn-secondary mt-2',
            'disabled' => 'disabled'
        ]);

        echo html_writer::end_tag('form');
        //END OF REMOVE

        //ADD
        echo html_writer::start_div(
            'col-md-5'
        );

        echo html_writer::tag(
            'h5',
            get_string('potentialproduct', 'local_learningproducts')
        );

        echo html_writer::start_tag('form', [
            'method' => 'post'
        ]);

        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey()
        ]);

        echo html_writer::select(
            $options,
            'childproductid[]',
            '',
            null,
            [
                'multiple' => true,
                'size' => 20,
                'id' => 'potentialproducts',
                'class' => 'form-control w-100'
            ]
        );

        echo html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => '◀ '.get_string('add', 'local_learningproducts'),
            'id' => 'addbtn',
            'class' => 'btn btn-secondary mt-2',
            'disabled' => 'disabled'
        ]);

        echo html_writer::end_tag('form');
        //END OF ADD

        echo html_writer::end_div();

    echo html_writer::end_div();

    echo html_writer::end_div();

    echo html_writer::end_div();

    echo html_writer::end_div();
    
}

$PAGE->requires->js_init_code("
    const existingCourses =
        document.getElementById('existingcourses');

    const potentialCourses =
        document.getElementById('potentialcourses');

    const removeCourseBtn =
        document.getElementById('removecoursebtn');

    const addCourseBtn =
        document.getElementById('addcoursebtn');

    if (existingCourses) {

        existingCourses.addEventListener(
            'change',
            () => {
                removeCourseBtn.disabled =
                    existingCourses.selectedOptions.length === 0;
            }
        );
    }

    if (potentialCourses) {

        potentialCourses.addEventListener(
            'change',
            () => {
                addCourseBtn.disabled =
                    potentialCourses.selectedOptions.length === 0;
            }
        );
    }
        
    const existingSelect =
        document.getElementById('existingproducts');

    const potentialSelect =
        document.getElementById('potentialproducts');

    const removeBtn =
        document.getElementById('removebtn');

    const addBtn =
        document.getElementById('addbtn');

    function toggleButtons() {

        removeBtn.disabled =
            existingSelect.selectedOptions.length === 0;

        addBtn.disabled =
            potentialSelect.selectedOptions.length === 0;
    }

    existingSelect.addEventListener(
        'change',
        toggleButtons
    );

    potentialSelect.addEventListener(
        'change',
        toggleButtons
    );

    toggleButtons();
");

echo $OUTPUT->footer();