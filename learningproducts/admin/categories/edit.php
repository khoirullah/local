<?php

require('../../../../config.php');

$id = optional_param('id', 0, PARAM_INT);

require_login();

$context = context_system::instance();

require_capability(
    'local/learningproducts:manage',
    $context
);

require_once(
    $CFG->dirroot .
    '/local/learningproducts/classes/form/category_form.php'
);

$form =
    new \local_learningproducts\form\category_form();

if ($id) {

    $category =
        \local_learningproducts\category_manager
            ::get_category($id);

    $form->set_data($category);
}

if ($form->is_cancelled()) {

    redirect(new moodle_url('/local/learningproducts/admin/index.php'));
}

if ($data = $form->get_data()) {

    if ($id) {

        $data->id = $id;

        \local_learningproducts\category_manager
            ::update_category($data);

    } else {

        \local_learningproducts\category_manager
            ::create_category($data);
    }

    redirect(new moodle_url('/local/learningproducts/admin/index.php'));
}

$PAGE->set_url(
    new moodle_url(
        '/local/learningproducts/admin/categories/edit.php'
    )
);

$PAGE->set_context($context);

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();