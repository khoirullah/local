<?php

namespace local_learningproducts;

defined('MOODLE_INTERNAL') || die();

class purchase_manager {

    public static function create_purchase(
        int $companyid,
        int $productid,
        float $price,
        int $qty,
        int $userid
    ): int {

        global $DB;

        $record = new \stdClass();

        $record->companyid = $companyid;
        $record->productid = $productid;

        $record->price = $price;
        $record->qty = $qty;

        $record->status = 'completed';

        $record->purchasedby = $userid;

        $record->timecreated = time();

        \local_company\log_manager::add(
            $companyid,
            $userid,
            'product_purchased',
            'Product #' . $productid .
            ' Qty ' . $qty
        );
        
        return $DB->insert_record(
            'local_lp_purchase',
            $record
        );

    }

    public static function get_purchase(
        int $purchaseid
    ) {

        global $DB;

        return $DB->get_record(
            'local_lp_purchase',
            [
                'id' => $purchaseid
            ]
        );
    }

    public static function get_company_purchases(
        int $companyid
    ): array {

        global $DB;

        return $DB->get_records(
            'local_lp_purchase',
            [
                'companyid' => $companyid
            ],
            'timecreated DESC'
        );
    }

    
    public static function purchase_product(
        int $companyid,
        int $productid,
        int $qty,
        int $userid
    ): int {

        global $DB;

        $transaction = $DB->start_delegated_transaction();

        try {
            $price = product_manager::get_product_price(
                $productid
            );

            $total = $price * $qty;

            if (
                !\local_corporatecredits\wallet_manager::has_balance(
                    $companyid,
                    $total
                )
            ) {
                throw new \moodle_exception(
                    'insufficientcredit',
                    'local_corporatecredits'
                );
            }

            $purchaseid = self::create_purchase(
                $companyid,
                $productid,
                $price,
                $qty,
                $userid
            );

            \local_corporatecredits\wallet_manager::deduct_credit(
                $companyid,
                $total,
                'product_purchase',
                $purchaseid,
                'Purchase Product #' . $productid
            );

            \local_company\entitlement_manager::grant_seats(
                $companyid,
                $productid,
                $qty
            );

            $transaction->allow_commit();

            return $purchaseid;
        } catch (\Throwable $e) {
            $transaction->rollback($e);
            throw $e;
        }
        
    }

}