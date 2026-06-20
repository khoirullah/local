<style>
    #page-header {
       display: none;
    }
</style>
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

require('../../config.php');

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
        get_string('enrolsuccess', 'local_learningproducts')
    );
}

/**
 * Get bundle products.
 */
$bundleproducts = [];

if ($product->type === 'bundle') {
    
    $bundleproducts =
        \local_learningproducts\bundle_manager::get_bundle_products($id);

    $itemcount = count($bundleproducts);

    if ($itemcount <= 1) {
        $colclass = 'col-md-12';
    } else if ($itemcount == 2) {
        $colclass = 'col-md-6';
    } else if ($itemcount == 3) {
        $colclass = 'col-md-4';
    } else {
        $colclass = 'col-md-3';
    }

    $displayitems = array_slice(
        array_values($bundleproducts),
        0,
        4
    );

    foreach ($displayitems as $item) {

        $item->colclass = $colclass;
        $item->producturl = new moodle_url(
            '/local/learningproducts/view.php',
            [
                'id' => $item->id
            ]
        );
        $imageurl =
        \local_learningproducts\product_manager
            ::get_product_image_url( $item->id);

        if ($imageurl) {

            $item->imageurl = $imageurl->out(false);

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

            $item->imageurl =
                'data:image/svg+xml;base64,' .
                base64_encode($svg);
        }
    }

    $product->itemcount = $itemcount;
    $product->items = $displayitems;
    $product->morecount = max(
        0,
        count($bundleproducts) - 4
    );
    
}else {
    
    /**
     * Get mapped courses.
     */
    $courses = \local_learningproducts\course_mapper::get_courses($id);
    
    foreach ($courses as $course) {

        $course->summary = format_text(
            $course->summary,
            $course->summaryformat
        );

        $course->colclass = 'col-md-12';
    }

    $product->items = array_values($courses);
}

$imageurl =
        \local_learningproducts\product_manager
            ::get_product_image_url($id);

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


/**
 * Page setup.
 */

$product->homeurl =
    new moodle_url('/my');

$product->producturl =
    new moodle_url('/local/learningproducts/');

$product->tipe =
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

$product->description =
    format_text( $product->description, FORMAT_HTML);
     
$isenrolled = false;
$actionurl = new moodle_url(
    '/local/learningproducts/view.php',
    [
        'id' => $product->id,
        'enrol' => 1
    ]
);

if (isloggedin() && !isguestuser()) {

    $isenrolled =
        \local_learningproducts\enrolment_manager
            ::is_product_enrolled(
                $product->id,
                $USER->id
            );

    if ($isenrolled) {

        $actionurl =
            \local_learningproducts\enrolment_manager
                ::get_product_learning_url(
                    $product->id
                );
    }
}

/**
 * Template context.
 */
$templatecontext = [
    'isenrolled' => $isenrolled,
    'actionurl' => $actionurl,
    'products' => $product,
    'issingle' => $product->type === 'single'
];

/**
 * Render page.
 */
$PAGE->set_title(format_string($product->name));
echo $OUTPUT->header();

/**
 * Render template.
 */
echo $OUTPUT->render_from_template(
    'local_learningproducts/product-detail',
    $templatecontext
);

//var_dump($product->items);
echo $OUTPUT->footer();