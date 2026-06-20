<?php

require('../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();

$context = context_system::instance();

require_capability(
    'local/learningproducts:manage',
    $context
);

$product = \local_learningproducts\product_manager::get_product($id);

if (!$product) {
    throw new moodle_exception('invalidproduct');
}

/**
 * Clone product object.
 */
$newproduct = clone $product;

unset($newproduct->id);

/**
 * Rename duplicated product.
 */
$newproduct->name = $product->name . ' (Copy)';

$newproduct->shortname = $product->shortname . '_copy';

$newproduct->timecreated = time();
$newproduct->timemodified = time();

/**
 * Create duplicated product.
 */
$newproductid =
    \local_learningproducts\product_manager::create_product($newproduct);

/**
 * Duplicate course mappings.
 */
$mappings =
    \local_learningproducts\course_mapper::get_courses($product->id);

foreach ($mappings as $course) {

    \local_learningproducts\course_mapper::add_course(
        $newproductid,
        $course->id
    );
}

/**
 * Duplicate bundle items.
 */
if ($product->type === 'bundle') {

    $bundleproducts =
        \local_learningproducts\bundle_manager::get_bundle_products($id);

    foreach ($bundleproducts as $bundleproduct) {

        \local_learningproducts\bundle_manager::add_product(
            $newproductid,
            $bundleproduct->id
        );
    }
}

redirect(
    new moodle_url(
        '/local/learningproducts/admin/index.php'
    ),
    'Product duplicated successfully'
);