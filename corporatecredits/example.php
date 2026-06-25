perbaiki ini buat halaman xendit, dimana sekarang cuma buat bayar topup coins
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Listens for Instant Payment Notification from PayPal
 *
 * This script waits for Payment notification from PayPal,
 * then double checks that data by sending it back to PayPal.
 * If PayPal verifies this then it sets up the enrolment for that
 * user.
 *
 * @package    enrol_paypal
 * @copyright 2010 Eugene Venter
 * @author     Eugene Venter - based on code by others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Disable moodle specific debug messages and any errors in output,
// comment out when debugging or better look into error log!
define('NO_DEBUG_DISPLAY', true);

// This script does not require login.
require("../../config.php"); // phpcs:ignore
require_once("lib.php");
require_once($CFG->libdir.'/enrollib.php');

require_login();

// PayPal does not like when we return error messages here,
// the custom handler just logs exceptions and stops.
set_exception_handler(\enrol_paypal\util::get_exception_handler());


// Make sure we are enabled in the first place.
if (!enrol_is_enabled('paypal')) {
    http_response_code(503);
    throw new moodle_exception('errdisabled', 'enrol_paypal');
}

/// Keep out casual intruders
if (empty($_POST) or !empty($_GET)) {
    http_response_code(400);
    throw new moodle_exception('invalidrequest', 'core_error');
}

/// Read all the data from PayPal and get it ready for later;
/// we expect only valid UTF-8 encoding, it is the responsibility
/// of user to set it up properly in PayPal business account,
/// it is documented in docs wiki.

$req = 'cmd=_notify-validate';

$data = new stdClass();
$short = new stdClass();

foreach ($_POST as $key => $value) {
    if ($key !== clean_param($key, PARAM_ALPHANUMEXT)) {
        throw new moodle_exception('invalidrequest', 'core_error', '', null, $key);
    }
    if (is_array($value)) {
        throw new moodle_exception('invalidrequest', 'core_error', '', null, 'Unexpected array param: '.$key);
    }
    $req .= "&$key=".urlencode($value);
    $data->$key = fix_utf8($value);
}

if (empty($data->custom)) {
    throw new moodle_exception('invalidrequest', 'core_error', '', null, 'Missing request param: custom');
}

$custom = explode('-', $data->custom);
unset($data->custom);

if (empty($custom) || count($custom) < 3) {
    throw new moodle_exception('invalidrequest', 'core_error', '', null, 'Invalid value of the request param: custom');
}

$short->userid           = $custom[0];
$short->courseid         = $custom[1];
$short->instanceid       = $custom[2];
$short->payment_gross    = (int)$data->amount;
$short->payment_currency = $data->currency_code;
$short->timeupdated      = time();

$user = $DB->get_record("user", array("id" => $short->userid), "*", MUST_EXIST);
$course = $DB->get_record("course", array("id" => $short->courseid), "*", MUST_EXIST);
$category = $DB->get_record("course_categories", array("id" => $course->category), "*", MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

$PAGE->set_context($context);
// Execute the database query
$records = $DB->get_records("payment_gateways");

//declare data array
$paygw = array();

// Display the retrieved records
foreach ($records as $record) {
    $paygw['id'] = $record->id;
    $paygw['accountid']= $record->accountid;
    $paygw['gateway']= $record->gateway;
    $paygw['config']=$record->config;
}

// get paypal API Key
$arrConfig = json_decode($paygw['config'], true);

/// Open a connection back to paypal to validate the data
$xenditaddr = "https://api.xendit.co";

// Create a new cURL handle
$c = curl_init();
$apiKey = $arrConfig['secret'];

$description = get_string('desc_xendit', 'local_product', (object) array(
    'item_name' => $short->item_name,
    'payment_currency' => $short->payment_currency,
    'amount' => $short->amount
));

$externalid = strtolower(base64_encode('cust'.$short->userid.'-dev'.$short->courseid.'-'.time()));
$success = base64_encode('SUCCESS-'.$short->userid.'-'.$short->courseid.'-'.$short->instanceid.'-'.$externalid);
$failed = base64_encode('FAILED'.$short->userid.'-'.$short->courseid.'-'.$short->instanceid.'-'.$externalid);

$req = array(
    'external_id' => $externalid,  // Replace with your own external ID
    'payer_email' => $data->email,  // Replace with customer's email
    'description' => $description,  // Replace with invoice description
    'amount' => (int)str_replace('.', '', $data->amount),  // Replace with invoice amount in cents
    'customer' => [
        'given_names' => $data->first_name,
        'surname' => $data->last_name,
        'email' => $data->email,
        'addresses' => [
            [
                'city' => $data->city,
                'country' => $data->country,
                'street_line1' => $data->address
            ]
        ]
    ],
    'success_redirect_url' => $CFG->wwwroot.'/enrol/paypal/?status='.$success,
    'failure_redirect_url' => $CFG->wwwroot.'/enrol/paypal/?status='.$failed,
    'currency' => $short->payment_currency,
    'items' => [
        [
            'id' => $short->courseid,
            'name' => $data->item_name,
            'quantity' => $data->quantity,
            'price' => (int)str_replace('.', '', $data->amount),
            'category' => $category->name,
            'url' => $CFG->wwwroot."/course/view.php?id=".$short->courseid
        ]
    ]
);

// Convert the $req data to JSON format
$reqJson = json_encode($req);

$options = array(
    CURLOPT_URL => $xenditaddr."/v2/invoices",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'Authorization: Basic ' . base64_encode($apiKey . ':')),
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_POSTFIELDS => $reqJson,
);

// Set the cURL options on the handle
curl_setopt_array($c, $options);

// Execute the cURL request
$result = curl_exec($c);
$arrData = json_decode($result, true);


// Get the response information
$http_status = curl_getinfo($c, CURLINFO_HTTP_CODE);

$admin = get_admin();
$adminEmail = $admin->email;
$enrol_paypal = new stdClass();

if (strlen($result) > 0) {
    $status = $arrData['status'];
    // check the payment_status and payment_reason
    // If status is not completed or pending then unenrol the student if already enrolled
    // and notify admin

    if ($status != "SUCCEEDED" and $status != "PENDING") {
        $plugin->unenrol_user($plugin_instance, $data->userid);
        \enrol_paypal\util::message_paypal_error_to_admin("Status not completed or pending. User unenrolled from course",
                                                          $data);
        die;
        $plugin->unenrol_user($plugin_instance, $data->userid);
        \enrol_paypal\util::message_paypal_error_to_admin("Status not completed or pending. User unenrolled from course ",
        $arrData['items'][0]['name']);
        die;
    }
     // If status is pending and reason is other than echeck then we are on hold until further notice
    // Email user to let them know. Email admin.

    if ($status == "PENDING") {
        $eventdata = new \core\message\message();
        $eventdata->courseid          = empty($short->courseid) ? SITEID : $short->courseid;
        $eventdata->modulename        = 'moodle';
        $eventdata->component         = 'enrol_paypal';
        $eventdata->name              = 'paypal_enrolment';
        $eventdata->userfrom          = get_admin();
        $eventdata->userto            = $user;
        $eventdata->subject           = "Moodle: Xendit payment";
        $eventdata->fullmessage       = "Your Xendit payment is pending.";
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml   = '';
        $eventdata->smallmessage      = '';
        message_send($eventdata);

        \enrol_paypal\util::message_paypal_error_to_admin("Payment pending", $data);
        //die;
    }

    // At this point we only proceed with a status of completed or pending with a reason of echeck
    // Make sure this transaction doesn't exist already.
    $txnID = $arrData['external_id'];
    if ($existing = $DB->get_record("enrol_paypal", array("txn_id" => $txnID), "*", IGNORE_MULTIPLE)) {
        \enrol_paypal\util::message_paypal_error_to_admin("Transaction $txnID is being repeated!", $data);
        die;
    }

    // Check that the receiver email is the one we want it to be.
    if (isset($adminEmail)) {
        $recipient = $adminEmail;
    } else if (isset($arrData['payer_email'])) {
        $recipient = $arrData['payer_email'];
    } else {
        $recipient = 'empty';
    }

    if (!$user = $DB->get_record('user', array('id'=>$short->userid))) {   // Check that user exists
        \enrol_paypal\util::message_paypal_error_to_admin("User $short->userid doesn't exist", $data);
        //die;
    }

    if (!$course = $DB->get_record('course', array('id'=>$short->courseid))) { // Check that course exists
        \enrol_paypal\util::message_paypal_error_to_admin("Course $short->courseid doesn't exist", $data);
        //die;
    } 

    $coursecontext = context_course::instance($course->id, IGNORE_MISSING);

    if ($data->business != 'personal') {
        // Define the user ID and new values.
        $userid = $data->userid; // User ID to update
        $new_institution = $data->company;
        $new_company = $data->company;

        // Update the institution in the user table.
        $update_user = $DB->set_field('user', 'institution', $new_institution, array('id' => $userid));

        // Get the profile field ID for the company field.
        $company_field_id = $DB->get_field('user_info_field', 'id', array('shortname' => 'company'));
        if ($company_field_id) {
            // Check if the user already has a value for the company field.
            $record = $DB->get_record('user_info_data', array('userid' => $userid, 'fieldid' => $company_field_id));
            if ($record) {
                // Update the existing record.
                $record->data = $new_company;
                $update_field = $DB->update_record('user_info_data', $record);
                //print_r($update_field);
            } else {
                // Insert a new record.
                $record = new stdClass();
                $record->userid = $userid;
                $record->fieldid = $company_field_id;
                $record->data = $new_company;
                $DB->insert_record('user_info_data', $record);
            }
        }
    }

    //Push data to enrol_paypal
    $enrol_paypal-> business = $adminEmail;
    $enrol_paypal-> receiver_email = $arrData['payer_email'];
    $enrol_paypal-> receiver_id = $arrData['user_id'];
    $enrol_paypal-> item_name = $arrData['items'][0]['name'];
    $enrol_paypal-> courseid = $short->courseid;
    $enrol_paypal-> userid = $short->userid;
    $enrol_paypal-> instanceid = $short->instanceid;
    $enrol_paypal-> memo = $arrData['payer_email']." order ".$arrData['items'][0]['name'];
    $enrol_paypal-> tax = "";
    $enrol_paypal-> option_name1 = "";
    $enrol_paypal-> option_selection1_x = "";
    $enrol_paypal-> option_name2 = "";
    $enrol_paypal-> option_selection2_x = "";
    $enrol_paypal-> payment_status = $arrData['status'];
    $enrol_paypal-> pending_reason = "haven't made a payment yet";
    $enrol_paypal-> reason_code = "01";
    $enrol_paypal-> txn_id = $arrData['external_id'];
    $enrol_paypal-> parent_txn_id = "";
    $enrol_paypal-> payment_type = "";
    $enrol_paypal-> timeupdated = time();
    $record_enrolpaypal = $DB->insert_record("enrol_paypal", $enrol_paypal);

    //Push data to payments
    $payments = new stdClass();
    $payments->component = $arrData['external_id'];
    $payments->paymentarea = $data->business;
    $payments->itemid = $short->courseid;
    $payments->userid = $short->userid;
    $payments->amount = $short->payment_gross;
    $payments->currency = $short->payment_currency;
    $payments->accountid = $paygw['accountid'];
    $payments->gateway = $paygw['gateway'];
    $payments->timecreated = time();
    $payments->timemodified = time();
    
    //print_r($custom);
    if ($record_enrolpaypal) {
        $record_payment = $DB->insert_record("payments", $payments);
        if ($record_payment) {
            header("Location: ".$arrData['invoice_url']);
        }else {
            header("Location: ".$CFG->wwwroot."/course/view.php?id=".$value-> courseid);
        }
    }else {
        header("Location: ".$CFG->wwwroot."/course/view.php?id=".$value-> courseid);
    }
    
}