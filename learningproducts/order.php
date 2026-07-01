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
use \local_learningproducts\product_manager;
use \local_company\company_manager;
use \local_corporatecredits\wallet_manager;

$id = required_param('id', PARAM_INT);

require_login();

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
$product = product_manager::get_product($id);

/**
 * Render page.
 */
$PAGE->set_title(format_string($product->name));
$PAGE->set_heading($product->name);

$PAGE->navbar->add(
    get_string('pluginname', 'local_learningproducts')
);


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
 * Get company
 */
$company = company_manager::get_user_company($USER->id);
if (!$company) {
    redirect( 
        new moodle_url(
            '/local/learningproducts/topup.php'
        )
    );
}
$wallet = wallet_manager::get_summary($company->id);
$imageurl =
        \local_learningproducts\product_manager
            ::get_product_image_url( $product->id);

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

    $item->imageurl = 
        'data:image/svg+xml;base64,' .
        base64_encode($svg);
}
$plans = [
    [
        'id' => 1,
        'name' => 'Monthly',
        'months' => 1,
        'price' => 20,
        'checked' => true,
    ],
    [
        'id' => 2,
        'name' => 'Semester',
        'months' => 6,
        'price' => 100,
    ],
    [
        'id' => 3,
        'name' => 'Yearly',
        'months' => 12,
        'price' => 180,
    ],
];
$templatecontext = [
    'plans' => $plans,  
    'purchaseurl' =>(
        new moodle_url(
            '/local/learningproducts/purchase.php',
            [
                'companyid' => $company->id
            ]
        )
    )->out(false),
    'topupurl' =>(
        new moodle_url(
            '/local/corporatecredits/topup.php',
            [
                'companyid' => $company->id
            ]
        )
    )->out(false),

    'topupicon' => 
        $OUTPUT->image_url(
            'topup',
            'local_company'
        )->out(false),

    'company' => $company,

    'wallet' => $wallet,

    'product' => [

        'id' => $product->id,
        'name' => $product->name,
        'description' => format_text($product->description),
        'price' => number_format($product->price),
        'priceraw' => $product->price,
        'categoryname' => $product->categoryname,
        'type' => ucfirst($product->type),
        'image' => $imageurl

    ],
    'walletbalance' => $wallet['balance_raw'],

    'remainingbalance' => $wallet['balance_raw'] - $product->price,

    'remainingclass' =>
        $wallet['balance_raw'] >= $product->price
            ? 'text-success'
            : 'text-danger',

    'notenoughcredit' =>
        $wallet['balance_raw'] < $product->price,

    'sesskey' => sesskey()

];

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_learningproducts/order',
    $templatecontext
);

echo $OUTPUT->footer();