<?php

require('../../../config.php');

$id = optional_param('id', 0, PARAM_INT);

require_login();

$context = context_system::instance();

require_capability(
    'local/learningproducts:manage',
    $context
);

require_once(
    $CFG->dirroot .
    '/local/learningproducts/classes/form/product_form.php'
);

$PAGE->set_url(
    new moodle_url('/local/learningproducts/admin/edit.php')
);

$PAGE->set_context($context);

$form = new \local_learningproducts\form\product_form();

if ($id) {

    $product = \local_learningproducts\product_manager::get_product($id);

    $filemanageroptions = [
        'subdirs' => 0,
        'maxfiles' => 1
    ];

    $draftitemid = file_get_submitted_draft_itemid(
        'productimage'
    );

    $product->description = [
        'text' => $product->description,
        'format' => FORMAT_HTML
    ];
    
    file_prepare_draft_area(
        $draftitemid,
        $context->id,
        'local_learningproducts',
        'productimage',
        $product->id,
        $filemanageroptions
    );

    $product->productimage = $draftitemid;
    $form->set_data($product);
}

if ($form->is_cancelled()) {

    redirect(new moodle_url('/local/learningproducts/admin/index.php'));
}

if ($data = $form->get_data()) {

    if ($id) {

        $data->id = $id;

        $draftitemid = $data->productimage;

        unset($data->productimage);

        $data->id = $id;

        \local_learningproducts\product_manager
            ::update_product($data); 

        file_save_draft_area_files(
            $draftitemid,
            $context->id,
            'local_learningproducts',
            'productimage',
            $id,
            [
                'subdirs' => 0,
                'maxfiles' => 1
            ]
        );

    } else {

        //\local_learningproducts\product_manager::create_product($data);
        $draftitemid = $data->productimage;

        unset($data->productimage);

        $productid =
            \local_learningproducts\product_manager
                ::create_product($data);

        file_save_draft_area_files(
            $draftitemid,
            $context->id,
            'local_learningproducts',
            'productimage',
            $productid,
            [
                'subdirs' => 0,
                'maxfiles' => 1
            ]
        );
    }

    redirect(new moodle_url('/local/learningproducts/admin/index.php'));
}

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();