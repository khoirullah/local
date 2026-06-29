<?php
namespace local_learningproducts;

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
     * 1. Duplicate template course.
     * 2. Rename course.
     * 3. Move course to company category.
     * 4. Create self enrol instance.
     * 5. Generate enrolment key.
     * 6. Create company subscription.
     * 7. Enrol company PIC.
     * 8. Send notification.
     * 9. Mark completed.
     *
     * @param int $id
     * @throws \Throwable
     */
    public static function process(int $id): void {

        global $DB, $USER;

        $transaction = $DB->start_delegated_transaction();

        try {

            $provision = self::get($id);

            if (!$provision) {
                throw new \moodle_exception('invalidprovision');
            }

            self::set_status(
                $id,
                'processing',
                $USER->id
            );

            //--------------------------------------------------
            // Load data.
            //--------------------------------------------------

            $company = company_manager::get_company(
                $provision->companyid
            );

            $product = product_manager::get_product(
                $provision->productid
            );

            $templatecourse = get_course(
                $provision->templatecourseid
            );

            //--------------------------------------------------
            // Duplicate template course.
            //--------------------------------------------------

            $newcourseid = course_manager::duplicate_course(
                $templatecourse->id
            );

            //--------------------------------------------------
            // Rename course.
            //--------------------------------------------------

            course_manager::rename_course(
                $newcourseid,
                $templatecourse->fullname .
                    ' - ' .
                    $company->name
            );

            //--------------------------------------------------
            // Move course into company category.
            //--------------------------------------------------

            $companycategoryid =
                company_manager::get_course_category(
                    $company->id
                );

            course_manager::move_course(
                $newcourseid,
                $companycategoryid
            );

            //--------------------------------------------------
            // Create self enrol instance.
            //--------------------------------------------------

            $enrolid =
                enrol_manager::create_self_enrol_instance(
                    $newcourseid
                );

            //--------------------------------------------------
            // Generate enrolment key.
            //--------------------------------------------------

            $enrolkey =
                enrol_manager::generate_key();

            enrol_manager::set_enrol_key(
                $enrolid,
                $enrolkey
            );

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
            // Enrol PIC.
            //--------------------------------------------------

            company_manager::enrol_pic(
                $company->id,
                $newcourseid
            );

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

            notification_manager::send_ready_email([
                'companyid'   => $company->id,
                'courseid'    => $newcourseid,
                'course'      => $templatecourse->fullname,
                'enrolkey'    => $enrolkey,
                'startdate'   => $provision->startdate,
                'enddate'     => $provision->enddate,
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
}