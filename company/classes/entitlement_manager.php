<?php

namespace local_company;

defined('MOODLE_INTERNAL') || die();

class entitlement_manager {

    /**
     * Create entitlement.
     */
    public static function create(
        int $companyid,
        int $productid,
        int $qty,
        ?int $startdate = null,
        ?int $enddate = null
    ): int {

        global $DB;

        $record = new \stdClass();

        $record->companyid = $companyid;
        $record->productid = $productid;

        $record->qty = $qty;
        $record->usedqty = 0;

        $record->startdate = $startdate;
        $record->enddate = $enddate;

        $record->status = 'active';

        $record->timecreated = time();
        $record->timemodified = time();

        return $DB->insert_record(
            'local_company_entitlement',
            $record
        );
    }

    /**
     * Get entitlement.
     */
    public static function get(int $id) {

        global $DB;

        return $DB->get_record(
            'local_company_entitlement',
            ['id' => $id]
        );
    }

    /**
     * Get company entitlements.
     */
    public static function get_company_entitlements(
        int $companyid
    ): array {

        global $DB;

        return $DB->get_records(
            'local_company_entitlement',
            [
                'companyid' => $companyid,
                'status' => 'active'
            ]
        );
    }

    /**
     * Available seats.
     */
    public static function get_available_seats(
        int $entitlementid
    ): int {

        $entitlement = self::get($entitlementid);

        return max(
            0,
            $entitlement->qty - $entitlement->usedqty
        );
    }

    /**
     * Has available seats.
     */
    public static function has_available_seats(
        int $entitlementid
    ): bool {

        return self::get_available_seats(
            $entitlementid
        ) > 0;
    }

    /**
     * Consume seat.
     */
    public static function consume_seat(
        int $entitlementid
    ): bool {

        global $DB;

        $entitlement = self::get($entitlementid);

        if (
            $entitlement->usedqty >=
            $entitlement->qty
        ) {
            return false;
        }

        $entitlement->usedqty++;
        $entitlement->timemodified = time();

        $DB->update_record(
            'local_company_entitlement',
            $entitlement
        );

        return true;
    }

    /**
     * Release seat.
     */
    public static function release_seat(
        int $entitlementid
    ): bool {

        global $DB;

        $entitlement = self::get($entitlementid);

        if ($entitlement->usedqty <= 0) {
            return false;
        }

        $entitlement->usedqty--;
        $entitlement->timemodified = time();

        $DB->update_record(
            'local_company_entitlement',
            $entitlement
        );

        return true;
    }

    public static function get_used_seats(
        int $entitlementid
    ): int {

        $entitlement = self::get(
            $entitlementid
        );

        return (int)$entitlement->usedqty;
    }

    public static function get_total_seats(
        int $entitlementid
    ): int {

        $entitlement = self::get(
            $entitlementid
        );

        return (int)$entitlement->qty;
    }

    public static function get_usage_percentage(
        int $entitlementid
    ): float {

        $entitlement = self::get(
            $entitlementid
        );

        if ($entitlement->qty <= 0) {
            return 0;
        }

        return round(
            ($entitlement->usedqty / $entitlement->qty) * 100,
            2
        );
    }

    public static function grant_seats(
        int $companyid,
        int $productid,
        int $qty,
        ?int $startdate = null,
        ?int $enddate = null
    ): int {

        global $DB;

        $existing = $DB->get_record(
            'local_company_entitlement',
            [
                'companyid' => $companyid,
                'productid' => $productid,
                'status' => 'active'
            ]
        );

        if ($existing) {

            $existing->qty += $qty;
            $existing->timemodified = time();

            $DB->update_record(
                'local_company_entitlement',
                $existing
            );

            return $existing->id;
        }

        return self::create(
            $companyid,
            $productid,
            $qty,
            $startdate,
            $enddate
        );
    }

    public static function get_company_product_entitlement(
        int $companyid,
        int $productid
    ) {

        global $DB;

        return $DB->get_record(
            'local_company_entitlement',
            [
                'companyid' => $companyid,
                'productid' => $productid,
                'status' => 'active'
            ]
        );
    }

    public static function get_seat_summary(
        int $companyid,
        int $productid
    ): array {

        $entitlement =
            self::get_company_product_entitlement(
                $companyid,
                $productid
            );

        if (!$entitlement) {

            return [
                'total' => 0,
                'used' => 0,
                'available' => 0
            ];
        }

        return [
            'total' => (int)$entitlement->qty,
            'used' => (int)$entitlement->usedqty,
            'available' =>
                (int)$entitlement->qty -
                (int)$entitlement->usedqty
        ];
    }

    public static function is_expired(
        int $entitlementid
    ): bool {

        $entitlement =
            self::get($entitlementid);

        if (
            empty($entitlement->enddate)
        ) {
            return false;
        }

        return (
            $entitlement->enddate < time()
        );
    }

    public static function revoke_seats(
        int $entitlementid,
        int $qty
    ): bool {

        global $DB;

        $entitlement =
            self::get($entitlementid);

        $newqty =
            $entitlement->qty - $qty;

        if (
            $newqty < $entitlement->usedqty
        ) {
            return false;
        }

        $entitlement->qty =
            $newqty;

        $entitlement->timemodified =
            time();

        $DB->update_record(
            'local_company_entitlement',
            $entitlement
        );

        return true;
    }
}