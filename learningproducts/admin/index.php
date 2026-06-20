<?php

require('../../../config.php');

require_login();

$context = context_system::instance();
$categories =
    \local_learningproducts\category_manager
        ::get_categories();


$firstcategory = reset($categories);

$selectedcategoryid = optional_param(
    'categoryid',
    $firstcategory ? $firstcategory->id : 0,
    PARAM_INT
);

$selectedcategory =
    \local_learningproducts\category_manager
        ::get_category($selectedcategoryid);

$products =
    \local_learningproducts\product_manager
        ::get_products_by_category(
            $selectedcategoryid
        );

require_capability(
    'local/learningproducts:manage',
    $context
);

$PAGE->set_url(
    new moodle_url('/local/learningproducts/admin/index.php')
);

$PAGE->set_context($context);

$PAGE->set_title(
    get_string('managecat', 'local_learningproducts')
);
$PAGE->set_heading(
    get_string('managecat', 'local_learningproducts')
);
$PAGE->navbar->add(
    get_string('administrationsite'),
    new moodle_url('/admin/search.php')
);
$PAGE->navbar->add(
    'Manage Products' 
);

echo $OUTPUT->header();

echo html_writer::start_div(
    'mb-4'
);

    echo html_writer::start_div(
        'card my-4'
    );

    /**
     * Card header Category.
     */
    echo html_writer::start_div(
        'card-header d-flex justify-content-between align-items-center'
    );

        echo html_writer::tag(
            'h4',
            get_string('productcat', 'local_learningproducts')
        );

    echo html_writer::end_div();

    echo html_writer::start_div(
        'card-body text-center'
    );

    echo html_writer::link(
        new moodle_url(
            '/local/learningproducts/admin/categories/edit.php'
        ),
        'Create new category',
        ['class' => 'btn btn-primary m-2']
    );

    echo html_writer::start_tag(
        'ul',
        ['class' => 'ms-1 list-unstyled']
    );
        foreach ($categories as $category) {
            $isactive = $selectedcategoryid == $category->id;
            echo html_writer::start_tag(
                'li',
                ['class' =>
                    'listitem listitem-category list-group-item list-group-item-action'
                    . ($isactive ? ' border-left border-primary' : '')],
            );

                echo html_writer::start_div(
                    'clearfix'
                );
                    echo html_writer::link(
                        new moodle_url(
                            '/local/learningproducts/admin/index.php',
                            [
                                'categoryid' => $category->id
                            ]
                        ),
                        format_string($category->name),
                        ['class' => 'float-start categoryname aalink']

                    ); 
                    echo html_writer::start_div(
                        'float-end d-flex'
                    );

                        echo html_writer::start_div(
                            'action-menu moodle-actionmenu category-item-actions item-actions'
                        );

                            echo html_writer::start_div(
                                'action-menu-1-menubar'
                            );

                                echo html_writer::start_div(
                                    'action-menu-item'
                                );
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
                        echo html_writer::end_div();
                    echo html_writer::end_div();
                echo html_writer::end_div();
            echo html_writer::end_tag(
                'li'
            );
        }
    echo html_writer::end_tag(
        'ul'
    );    
    echo html_writer::end_div();

echo html_writer::end_div();

echo html_writer::start_div(
    'mb-4'
);

    echo html_writer::start_div(
        'card my-4'
    );

    /**
     * Card header Product.
     */
    echo html_writer::start_div(
        'card-header d-flex justify-content-between align-items-center'
    );

        echo html_writer::tag(
            'h4',
            format_string($selectedcategory->name),
        );

    echo html_writer::end_div();

    echo html_writer::start_div(
        'card-body text-center'
    );

    echo html_writer::link(
        new moodle_url(
            '/local/learningproducts/admin/edit.php',
            [
                'categoryid' => $selectedcategoryid
            ]
        ),
        'Create new product',
        ['class' => 'btn btn-primary m-2']
    );

    echo html_writer::start_tag(
        'ul',
        ['class' => 'ms-1 list-unstyled']
    );
        foreach ($products as $product) {
            echo html_writer::start_tag(
                'li',
                ['class' => 'listitem listitem-category list-group-item list-group-item-action']
            );

                echo html_writer::start_div(
                    'clearfix'
                );
                    echo html_writer::link(
                        new moodle_url(
                            '/local/learningproducts/admin/view.php',
                            ['id' => $product->id]
                        ),
                        format_string($product->name),
                        ['class' => 'float-start categoryname aalink']

                    ); 
                    echo html_writer::start_div(
                        'float-end d-flex'
                    );

                        echo html_writer::start_div(
                            'action-menu moodle-actionmenu category-item-actions item-actions'
                        );

                            echo html_writer::start_div(
                                'action-menu-1-menubar'
                            );

                                echo html_writer::start_div(
                                    'action-menu-item'
                                );
                                    echo html_writer::link(
                                        new moodle_url(
                                            '/local/learningproducts/admin/edit.php',
                                            ['id' => $product->id]
                                        ),
                                        $OUTPUT->pix_icon(
                                            't/edit',
                                            'Edit'
                                        ),
                                        [
                                            'title' => get_string('editproduct', 'local_learningproducts'),
                                            'data-toggle' => 'tooltip'
                                        ]
                                    );
                                    echo html_writer::link(
                                        new moodle_url(
                                            '/local/learningproducts/admin/duplicate.php',
                                            ['id' => $product->id]
                                        ),
                                        $OUTPUT->pix_icon(
                                            't/copy',
                                            'Duplicate'
                                        ),
                                        [
                                            'title' => get_string('duplicateproduct', 'local_learningproducts'),
                                            'data-toggle' => 'tooltip'
                                        ]
                                    );
                                    echo html_writer::link(
                                        new moodle_url(
                                            '/local/learningproducts/admin/visible.php',
                                            [
                                                'id' => $product->id,
                                                'sesskey' => sesskey()
                                            ]
                                        ),
                                        $OUTPUT->pix_icon(
                                            $product->visible
                                                ? 't/hide'
                                                : 't/show',
                                            $product->visible
                                                ? 'Hide Product'
                                                : 'Show Product'
                                        ),
                                        [
                                            'title' => $product->visible
                                                ? get_string(
                                                    'hideproduct',
                                                    'local_learningproducts'
                                                )
                                                : get_string(
                                                    'showproduct',
                                                    'local_learningproducts'
                                                ),
                                            'data-toggle' => 'tooltip'
                                        ]
                                    );
                                    echo html_writer::link(
                                        new moodle_url(
                                            '/local/learningproducts/admin/mapping.php',
                                            ['productid' => $product->id]
                                        ),
                                        html_writer::tag(
                                            'i',
                                            '',
                                            ['class' => 'fa fa-sitemap mr-1']
                                        ),
                                        [
                                            'title' => get_string('mappingcourse', 'local_learningproducts'),
                                            'data-toggle' => 'tooltip'
                                        ]
                                    );
                                    echo html_writer::link(
                                        new moodle_url(
                                            '/local/learningproducts/admin/delete.php',
                                            ['id' => $product->id]
                                        ),
                                        $OUTPUT->pix_icon(
                                            't/delete',
                                            'Delete'
                                        ),
                                        [
                                            'title' => get_string('deleteproduct', 'local_learningproducts'),
                                            'data-toggle' => 'tooltip'
                                        ]
                                    );
                                echo html_writer::end_div();
                            echo html_writer::end_div();
                        echo html_writer::end_div();
                    echo html_writer::end_div();
                echo html_writer::end_div();
            echo html_writer::end_tag(
                'li'
            );
        }
    echo html_writer::end_tag(
        'ul'
    );    
    echo html_writer::end_div();

echo html_writer::end_div();


echo $OUTPUT->footer();