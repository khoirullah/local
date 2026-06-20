<?php
/**
 * Product detail page.
 *
 * Halaman detail product:
 * - overview
 * - linked courses
 * - bundle content
 *
 * @package    local_learningproducts
 */

require('../../../config.php');

$id = required_param('id', PARAM_INT);

//require_login();

$context = context_system::instance();

$PAGE->set_url(
    new moodle_url('/local/learningproducts/view.php', [
        'id' => $id
    ])
);

$PAGE->set_context($context);

/**
 * Get product.
 */
$product = \local_learningproducts\product_manager::get_product($id);

/**
 * Validate product.
 */
if (!$product) {
    throw new moodle_exception(
        'invalidproduct',
        'local_learningproducts'
    );
}

/**
 * Page setup.
 */
$PAGE->set_title(format_string($product->name));
$PAGE->set_heading(format_string($product->name));

/**
 * Handle enrollment action.
 */
if (optional_param('enrol', 0, PARAM_BOOL)) {

    require_login();

    \local_learningproducts\enrolment_manager::enrol_product(
        $id,
        $USER->id
    );

    redirect(
        new moodle_url('/my/courses.php'),
        'Enrollment successful'
    );
}

/**
 * Get mapped courses.
 */
$courses = \local_learningproducts\course_mapper::get_courses($id);

/**
 * Get bundle products.
 */
$bundleproducts = [];

if ($product->type === 'bundle') {

    $bundleproducts =
        \local_learningproducts\bundle_manager::get_bundle_products($id);
}

/**
 * Template context.
 */
$templatecontext = [
    'enrolurl' => new moodle_url(
        '/local/learningproducts/view.php',
        [
            'id' => $product->id,
            'enrol' => 1
        ]
    ),
    'product' => [
        'id' => $product->id,
        'name' => format_string($product->name),
        'shortname' => format_string($product->shortname),
        'description' => format_text(
            $product->description,
            FORMAT_HTML
        ),
        'type' => $product->type,
        'price' => number_format($product->price, 2)
    ],

    'courses' => array_values($courses),

    'bundleproducts' => array_values($bundleproducts),

    'isbundle' => $product->type === 'bundle'
];

/**
 * Render page.
 */
echo $OUTPUT->header();

/**
 * Render template.
 */
echo $OUTPUT->render_from_template(
    'local_learningproducts/product-detail',
    $templatecontext
);

echo $OUTPUT->footer();