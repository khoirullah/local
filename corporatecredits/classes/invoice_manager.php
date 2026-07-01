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

    public static function get_all_invoice(): array {
        global $DB;

        $sql = "
            SELECT
                i.*,
                c.name AS companyname
            FROM {local_corpcredits_invoice} i
            INNER JOIN {local_company} c
                ON c.id = i.companyid
            ORDER BY i.timecreated DESC
        ";

        return $DB->get_records_sql($sql);
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

        // Sudah pernah diproses.
        if ($invoice->status === 'paid') {
            return;
        }

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