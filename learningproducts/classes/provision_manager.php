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
}