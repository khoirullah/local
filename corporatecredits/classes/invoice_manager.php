<?php

namespace local_corporatecredits;

defined('MOODLE_INTERNAL') || die();

class invoice_manager {

    public static function create_invoice(
        int $companyid,
        int $coins,
        string $payment,
        float $amount
    ) {

        global $DB;

        $record = new \stdClass();

        $record->companyid = $companyid;

        $record->invoicecode =
            'INV-' .
            strtoupper(
                random_string(10)
            );

        $record->coins = $coins;

        $record->paymentmethod = $payment;

        $record->amount = $amount;

        $record->status = 'pending';

        $record->timecreated = time();

        $record->timemodified = time();

        return $DB->insert_record(
            'local_corpcredits_invoice',
            $record
        );
    }

    public static function get_invoice(
        int $id
    ) {

        global $DB;

        return $DB->get_record(
            'local_corpcredits_invoice',
            [
                'id' => $id
            ],
            '*',
            MUST_EXIST
        );
    }

    public static function mark_paid(
        int $invoiceid,
        string $source
    ) {

        global $DB;

        $invoice =
            self::get_invoice(
                $invoiceid
            );

        $invoice->status = 'paid';

        $invoice->timepaid = time();

        $invoice->timemodified = time();

        $DB->update_record(
            'local_corpcredits_invoice',
            $invoice 
        );

        wallet_manager::add_credit(
            $invoice->companyid,
            $invoice->coins,
            $source.'_topup',
            0,
            'Topup invoice ' 
            .$invoice->invoicecode.
            ' via '.$source.' payment gateway.'
        );
    }

    public static function mark_failed(
        int $invoiceid
    ) {

        global $DB;

        $invoice =
            self::get_invoice(
                $invoiceid
            );

        $invoice->status = 'failed';

        $invoice->timemodified = time();

        $DB->update_record(
            'local_corpcredits_invoice',
            $invoice 
        );
    }
}