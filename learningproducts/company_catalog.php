<?php
require('../../config.php');

use local_learningproducts\product_manager;

require_login();

$context = context_system::instance();

require_capability(
    'local/company:manage',
    $context
);

$products =
    product_manager::get_all_products();

$templatecontext = [
    'products' => []
];

foreach ($products as $product) {

    $templatecontext['products'][] = [

        'id' => $product->id,

        'name' => $product->name,

        'price' => $product->price,

        'url' => new moodle_url(
            '/local/learningproducts/buy.php',
            [
                'id' => $product->id
            ]
        )
    ];
}

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_learningproducts/company_catalog',
    $templatecontext
);

echo $OUTPUT->footer();