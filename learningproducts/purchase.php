<?php
require('../../config.php');

use local_corporatecredits\wallet_manager;
use local_company\company_manager;
//use local_company\subscription_manager;
use local_learningproducts\product_manager;
use local_learningproducts\provision_manager;
use local_learningproducts\purchase_manager;
//use local_learningproducts\enrolment_manager;

require_login();
require_sesskey();

$companyid = required_param('companyid', PARAM_INT);
$productid = required_param('productid', PARAM_INT);
$total = required_param('total', PARAM_FLOAT);
$seat = required_param('seat', PARAM_INT);
$duration = required_param('duration', PARAM_INT);

$start = new DateTime();

$end = clone $start;
$end->modify("+{$duration} months");

$startdate = $start->format('Y-m-d H:i:s');
$enddate   = $end->format('Y-m-d H:i:s');
$startdate = strtotime($startdate);
$enddate   = strtotime($enddate);
$product = product_manager::get_product($productid);
$company = company_manager::get($companyid);
$wallet = wallet_manager::get_summary($companyid);

if (!$product) {
    redirect(
        new moodle_url('/local/learningproducts/index.php'),
        get_string('productnotfound', 'local_learningproducts'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}
if (!$company) {
    redirect(
        new moodle_url(
            '/local/learningproducts/topup.php',
            [
                'id' => $companyid
            ]
        )
    );
}

$transaction = $DB->start_delegated_transaction();

try {
    global $USER;

    $courses = [];

    if ($product->type === 'single') {

        $productcourses = product_manager::get_product_courses($productid);

        if (!empty($productcourses)) {
            $courses[] = reset($productcourses);
        }

    } else {

        $bundle = product_manager::get_product_bundle_items($productid);

        foreach ($bundle as $item) {

            $productcourses = product_manager::get_product_courses($item->productid);

            if (!empty($productcourses)) {
                $courses[] = reset($productcourses);
            }
        }
    }

    // Remove duplicate courses.
    $uniquecourses = [];

    foreach ($courses as $course) {
        $uniquecourses[$course->courseid] = $course;
    }

    $courses = array_values($uniquecourses);

    wallet_manager::deduct_credit(
        $company->id,
        $product->price,
        'learningproducts',
        $product->id,
        'Purchase ' . $product->name
    );

    $purchaseid = purchase_manager::create_purchase(
        $company->id,
        $product->id,
        $product->price,
        $seat,
        $USER->id
    );

    // Create provisioning request for each course.
    foreach ($courses as $course) {

        provision_manager::create([
            'purchaseid'       => $purchaseid,
            'companyid'        => $companyid,
            'productid'        => $productid,
            'templatecourseid' => $course->courseid,
            'quota'            => $seat,
            'startdate'        => $startdate,
            'enddate'          => $enddate,
            'requestedby'      => $USER->id,
        ]);
    }

    provision_manager::notify_admins($purchaseid);

    $transaction->allow_commit();

    redirect(
        new moodle_url('/local/learningproducts/view.php', [
            'id' => $product->id
        ]),
        get_string('purchasesuccess', 'local_learningproducts'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );

} catch (Exception $e) {

    $transaction->rollback($e);

    redirect(
        new moodle_url('/local/learningproducts/view.php', [
            'id' => $product->id
        ]),
        $e->getMessage(),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}
