<?php

namespace local_corporatecredits;

defined('MOODLE_INTERNAL') || die();

class invoice_manager {

    public static function create_invoice(
        int $companyid,
        int $coins,
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
        int $invoiceid
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

        wallet_manager::credit(
            $invoice->companyid,
            $invoice->coins,
            'Topup invoice ' .
            $invoice->invoicecode
        );
    }
}