<?php
/**
 * Product catalog page.
 *
 * Halaman utama untuk menampilkan:
 * - product catalog
 * - bundle catalog
 * - filters
 *
 * @package    local_learningproducts
 */

require('../../config.php');

//require_login();

$PAGE->set_url(
    new moodle_url('/local/learningproducts/index.php')
);

$PAGE->set_context(context_system::instance());

$PAGE->set_title(get_string('pluginname', 'local_learningproducts'));
//$PAGE->set_heading(get_string('pluginname', 'local_learningproducts'));

$PAGE->navbar->add(
    get_string('pluginname', 'local_learningproducts')
);

echo $OUTPUT->header();

$products = \local_learningproducts\product_manager::get_products();

foreach ($products as $product) {

    $imageurl =
        \local_learningproducts\product_manager
            ::get_product_image_url($product->id);

    if ($imageurl) {

        $product->imageurl = $imageurl->out(false);

    } else {

        $svg = '
        <svg xmlns="http://www.w3.org/2000/svg"
            width="600"
            height="400"
            viewBox="0 0 600 400">

            <defs>
                <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#0f0f0f"/>
                    <stop offset="100%" stop-color="#7f1d1d"/>
                </linearGradient>
            </defs>

            <rect width="600" height="400" fill="url(#bg)"/>

            <circle cx="120" cy="100" r="90"
                fill="#dc2626"
                fill-opacity="0.25"/>

            <circle cx="500" cy="320" r="140"
                fill="#991b1b"
                fill-opacity="0.35"/>

        </svg>';

        $product->imageurl =
            'data:image/svg+xml;base64,' .
            base64_encode($svg);
    } 

    $ratingsummary =
        \local_learningproducts\product_manager
            ::get_product_rating_summary($product->id);

    $product->type =
        ucwords($product->type);

    $product->price =
        (int)$product->price;

    $product->rating =
        $ratingsummary->avgrating;

    $product->ratingcount =
        $ratingsummary->totalratings;

    $product->studentcount =
        \local_learningproducts\product_manager
            ::get_product_total_students($product->id);

    $product->isenrolled = false;

    $product->actionurl = (
        new moodle_url(
            '/local/learningproducts/view.php',
            [
                'id' => $product->id,
                'enrol' => 1
            ]
        )
    )->out(false);

    if (isloggedin() && !isguestuser()) {

        $product->isenrolled =
            \local_learningproducts\enrolment_manager
                ::is_product_enrolled(
                    $product->id,
                    $USER->id
                );

        if ($product->isenrolled) {

            $product->actionurl =
                \local_learningproducts\enrolment_manager
                    ::get_product_learning_url(
                        $product->id
                    )->out(false);
        }
    }
}

/**
 * Categories.
 */
$categories =
    \local_learningproducts\category_manager
        ::get_categories();

/**
 * Newest products.
 */
$newproducts = array_slice(
    array_values($products),
    0,
    10
);

$topupurl = new moodle_url(
    '/local/learningproducts/topup.php'
);

$templatecontext = [

    'topupurl'  => $topupurl,
    
    'products' => array_values($products),

    'newproducts' => $newproducts,

    'categories' => array_values($categories)
];

echo $OUTPUT->render_from_template(
    'local_learningproducts/catalog',
    $templatecontext
);

echo $OUTPUT->footer();