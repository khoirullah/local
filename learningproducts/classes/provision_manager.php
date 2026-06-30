<?php
namespace local_learningproducts;

use local_learningproducts\purchase_manager;
use local_learningproducts\product_manager;
use local_learningproducts\course_mapper;
use local_learningproducts\enrolment_manager;
use local_company\subscription_manager;
use local_company\company_manager;

defined('MOODLE_INTERNAL') || die();

class provision_manager {

    /**
     * Create provisioning request.
     *
     * @param array $data
     * @return int
     */
    public static function create(array $data): int {

        global $DB;

        $record = new \stdClass();

        $record->purchaseid       = $data['purchaseid'];
        $record->companyid        = $data['companyid'];
        $record->productid        = $data['productid'];
        $record->templatecourseid = $data['templatecourseid'];
        $record->courseid         = $data['courseid'] ?? 0;
        $record->subscriptionid   = $data['subscriptionid'] ?? 0;
        $record->quota            = $data['quota'];
        $record->startdate        = $data['startdate'];
        $record->enddate          = $data['enddate'];
        $record->status           = $data['status'] ?? 'pending';
        $record->requestedby      = $data['requestedby'];
        $record->processedby      = $data['processedby'] ?? 0;
        $record->notes            = $data['notes'] ?? '';
        $record->timecreated      = time();
        $record->timemodified     = time();

        return $DB->insert_record(
            'local_company_provision',
            $record
        );
    }

    /**
     * Get provision by id.
     *
     * @param int $id
     * @return \stdClass|false
     */
    public static function get(int $id) {

        global $DB;

        return $DB->get_record(
            'local_company_provision',
            [
                'id' => $id
            ]
        );
    }

    /**
     * Get all pending provisions.
     *
     * @return array
     */
    public static function get_pending(): array {

        global $DB;

        return $DB->get_records(
            'local_company_provision',
            [
                'status' => 'pending'
            ],
            'timecreated ASC'
        );
    }

    /**
     * Get provisions by purchase.
     *
     * @param int $purchaseid
     * @return array
     */
    public static function get_by_purchase(int $purchaseid): array {

        global $DB;

        return $DB->get_records(
            'local_company_provision',
            [
                'purchaseid' => $purchaseid
            ]
        );
    }

    /**
     * Update duplicated course.
     *
     * @param int $id
     * @param int $courseid
     */
    public static function set_course(
        int $id,
        int $courseid
    ): void {

        global $DB;

        $record = self::get($id);

        $record->courseid = $courseid;
        $record->timemodified = time();

        $DB->update_record(
            'local_company_provision',
            $record
        );
    }

    /**
     * Attach subscription.
     *
     * @param int $id
     * @param int $subscriptionid
     */
    public static function set_subscription(
        int $id,
        int $subscriptionid
    ): void {

        global $DB;

        $record = self::get($id);

        $record->subscriptionid = $subscriptionid;
        $record->timemodified = time();

        $DB->update_record(
            'local_company_provision',
            $record
        );
    }

    /**
     * Change status.
     *
     * @param int $id
     * @param string $status
     * @param int|null $processedby
     */
    public static function set_status(
        int $id,
        string $status,
        ?int $processedby = null
    ): void {

        global $DB;

        $record = self::get($id);

        $record->status = $status;
        $record->timemodified = time();

        if ($processedby !== null) {
            $record->processedby = $processedby;
        }

        $DB->update_record(
            'local_company_provision',
            $record
        );
    }

    /**
     * Update notes.
     *
     * @param int $id
     * @param string $notes
     */
    public static function set_notes(
        int $id,
        string $notes
    ): void {

        global $DB;

        $record = self::get($id);

        $record->notes = $notes;
        $record->timemodified = time();

        $DB->update_record(
            'local_company_provision',
            $record
        );
    }

    /**
     * Delete provision.
     *
     * @param int $id
     */
    public static function delete(
        int $id
    ): void {

        global $DB;

        $DB->delete_records(
            'local_company_provision',
            [
                'id' => $id
            ]
        );
    }

    public static function notify_admins(int $purchaseid): void {
        global $DB;

        $purchase = purchase_manager::get_purchase($purchaseid);

        $company = $DB->get_record(
            'local_company',
            ['id' => $purchase->companyid],
            '*',
            MUST_EXIST
        );

        $product = product_manager::get_product($purchase->productid);

        $buyer = $DB->get_record(
            'user',
            ['id' => $purchase->purchasedby],
            '*',
            MUST_EXIST
        );

        $admins = get_admins();

        foreach ($admins as $admin) {

            $message = new \core\message\message();

            $message->component = 'local_learningproducts';
            $message->name = 'provisionrequest';

            $message->userfrom = \core_user::get_noreply_user();
            $message->userto = $admin;

            $message->subject =
                'New Course Provision Request';

            $message->fullmessage =
                "A new learning product has been purchased.\n\n" .
                "Company : {$company->name}\n" .
                "Product : {$product->name}\n" .
                "Purchased by : {$buyer->firstname} {$buyer->lastname}\n" .
                "Seats : {$purchase->qty}\n\n" .
                "Please duplicate the template course(s).";

            $message->fullmessageformat = FORMAT_PLAIN;

            $message->fullmessagehtml =
                nl2br($message->fullmessage);

            $message->smallmessage =
                'New provisioning request';

            message_send($message);
        }
    }

    /**
     * Process provisioning request.
     *
     * Workflow:
     * 1. Create company subscription.
     * 2. Enrol company PIC.
     * 3. Send notification.
     * 4. Mark completed.
     *
     * @param int $id
     * @throws \Throwable
     */
    public static function process(int $id, int $newcourseid): void {
        
        global $DB, $USER;

        $transaction = $DB->start_delegated_transaction();
        $returnurl = new \moodle_url(
            '/local/learningproducts/provision/index.php'
        );

        try {
            $provision = self::get($id);
            
            if (!$provision) {
                redirect(
                    $returnurl,
                    'Provision request not found.',
                    null,
                    \core\output\notification::NOTIFY_ERROR
                );
            }

            if ($provision->status !== 'pending') {
                redirect(
                    $returnurl,
                    'Provision has already been processed.',
                    null,
                    \core\output\notification::NOTIFY_ERROR
                );
            }

            self::set_status(
                $id,
                'processing',
                $USER->id
            );

            //--------------------------------------------------
            // Load data.
            //--------------------------------------------------

            $company = company_manager::get($provision->companyid);

            $product = product_manager::get_product(
                $provision->productid
            );

            try {
                $newcourse = get_course($newcourseid);
            } catch (\Exception $e) {
                redirect(
                    $returnurl,
                    'The selected course does not exist.',
                    null,
                    \core\output\notification::NOTIFY_ERROR
                );
            }

            //--------------------------------------------------
            // Add New Course to product
            //--------------------------------------------------

            course_mapper::add_course($product->id, $newcourse->id);
            
            //--------------------------------------------------
            // Enroll User to Product and course automatically
            //--------------------------------------------------
            enrolment_manager::enrol_product($product->id, $provision->requestedby);
                
            //--------------------------------------------------
            // Create subscription.
            //--------------------------------------------------
            $subscriptionid =
                subscription_manager::create([
                    'companyid'  => $company->id,
                    'courseid'   => $newcourseid,
                    'quota'      => $provision->quota,
                    'startdate'  => $provision->startdate,
                    'enddate'    => $provision->enddate,
                ]);

            //--------------------------------------------------
            // Update provision record.
            //--------------------------------------------------

            self::set_course(
                $id,
                $newcourseid
            );

            self::set_subscription(
                $id,
                $subscriptionid
            );

            //--------------------------------------------------
            // Notify PIC.
            //--------------------------------------------------
            self::send_email_to_pic($provision->requestedby,[
                'companyid'   => $company->id,
                'courseid'    => $newcourseid,
                'course'      => $newcourse->fullname,
                'startdate'   => $provision->startdate,
                'enddate'     => $provision->enddate
            ]);

            //--------------------------------------------------
            // Completed.
            //--------------------------------------------------

            self::set_status(
                $id,
                'completed',
                $USER->id
            );

            $transaction->allow_commit();

        } catch (\Throwable $e) {

            $transaction->rollback($e);

            self::set_notes(
                $id,
                $e->getMessage()
            );

            self::set_status(
                $id,
                'failed',
                $USER->id
            );

            throw $e;
        }
    }

    /**
     * Send provisioning completed email to company PIC.
     *
     * @param array $data
     */
    public static function send_email_to_pic(int $userid, array $data): void {

        global $DB;

        $companyid = $data['companyid'];
        $courseid  = $data['courseid'];

        $course = get_course($courseid);

        $company = company_manager::get($companyid);

        $user = $DB->get_record(
            'user',
            ['id' => $userid],
            '*',
            MUST_EXIST
        );

        $message = new \core\message\message();

        $message->component = 'local_learningproducts';
        $message->name      = 'provisionready';

        $message->userfrom = \core_user::get_noreply_user();
        $message->userto   = $user;

        $message->subject =
            'Your learning product is ready';

        $courseurl = new \moodle_url(
            '/course/view.php',
            ['id' => $courseid]
        );

        $message->fullmessage =
            "Hello {$user->firstname},\n\n" .
            "Your company's learning product has been provisioned successfully.\n\n" .
            "Company : {$company->name}\n" .
            "Course : {$course->fullname}\n" .
            "Start : " . userdate($data['startdate']) . "\n" .
            "End : " . userdate($data['enddate']) . "\n\n" .
            "Course URL:\n" .
            $courseurl->out(false);

        $message->fullmessageformat = FORMAT_PLAIN;

        $message->fullmessagehtml =
            nl2br($message->fullmessage);

        $message->smallmessage =
            'Learning product is ready';

        message_send($message);
    }
}