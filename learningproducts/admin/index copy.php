<?php

require('../../../config.php');

require_login();

$context = context_system::instance();

require_capability(
    'local/learningproducts:manage',
    $context
);

$PAGE->set_url(
    new moodle_url('/local/learningproducts/admin/index.php')
);

$PAGE->set_context($context);

$PAGE->set_title('Manage Products');
$PAGE->set_heading('Manage Products');

echo $OUTPUT->header();

$categories =
    \local_learningproducts\category_manager
        ::get_categories();
        
echo html_writer::tag(
    'h2',
    'Manage product categories and products',
    ['class' => 'mb-4']
);

echo html_writer::start_div(
    'mb-4'
);

echo html_writer::link(
    new moodle_url(
        '/local/learningproducts/admin/categories/edit.php'
    ),
    'Create new category',
    ['class' => 'btn btn-primary']
);

echo html_writer::end_div();

foreach ($categories as $category) {

    echo html_writer::start_div(
        'card mb-4'
    );

    /**
     * Card header.
     */
    echo html_writer::start_div(
        'card-header d-flex justify-content-between align-items-center'
    );

    echo html_writer::tag(
        'h4',
        format_string($category->name),
        ['class' => 'mb-0']
    );

    echo html_writer::start_div();

    echo html_writer::link(
        new moodle_url(
            '/local/learningproducts/admin/categories/edit.php',
            ['id' => $category->id]
        ),
        $OUTPUT->pix_icon(
            't/edit',
            'Edit'
        )
    );

    echo html_writer::link(
        new moodle_url(
            '/local/learningproducts/admin/categories/delete.php',
            ['id' => $category->id]
        ),
        $OUTPUT->pix_icon(
            't/delete',
            'Delete'
        )
    );

    echo html_writer::end_div();

    echo html_writer::end_div();

    echo html_writer::start_div(
        'card-body'
    );

    echo html_writer::link(
        new moodle_url(
            '/local/learningproducts/admin/edit.php',
            ['categoryid' => $category->id]
        ),
        'Create new product',
        ['class' => 'btn btn-secondary mb-4']
    );

        $products =
        \local_learningproducts\product_manager
            ::get_products_by_category(
                $category->id
            );

    if (!$products) {

        echo html_writer::div(
            'No products found.',
            'text-muted'
        );

    } else {

        foreach ($products as $product) {

            $editurl = new moodle_url(
                '/local/learningproducts/admin/edit.php',
                ['id' => $product->id]
            );

            $mappingurl = new moodle_url(
                '/local/learningproducts/admin/mapping.php',
                ['productid' => $product->id]
            );

            $deleteurl = new moodle_url(
                '/local/learningproducts/admin/delete.php',
                ['id' => $product->id]
            );

            echo html_writer::start_div(
                'border rounded p-3 mb-2 d-flex justify-content-between align-items-center'
            );

                        echo html_writer::start_div();

            echo html_writer::tag(
                'h5',
                format_string($product->name),
                ['class' => 'mb-1']
            );

            echo html_writer::div(
                ucfirst($product->type)
                . ' • '
                . number_format($product->price, 0)
                . ' 🪙',
                'text-muted small'
            );

            echo html_writer::end_div();

                        echo html_writer::start_div();

            echo html_writer::link(
                $editurl,
                $OUTPUT->pix_icon(
                    't/edit',
                    'Edit'
                )
            );

            echo html_writer::link(
                $mappingurl,
                $OUTPUT->pix_icon(
                    'i/course',
                    'Mapping'
                )
            );

            echo html_writer::link(
                $deleteurl,
                $OUTPUT->pix_icon(
                    't/delete',
                    'Delete'
                )
            );

            echo html_writer::end_div();

            echo html_writer::end_div();
        }
    }
        echo html_writer::end_div();

    echo html_writer::end_div();
}

echo $OUTPUT->footer();