<?php

namespace local_corporatecredits;

defined('MOODLE_INTERNAL') || die();

class transaction_manager {

    public static function create(
        array $data
    ): int {

        global $DB;

        $record = new \stdClass();

        $record->walletid =
            $data['walletid'];

        $record->companyid =
            $data['companyid'];

        $record->type =
            $data['type'];

        $record->source =
            $data['source'] ?? 'manual';

        $record->referenceid =
            $data['referenceid'] ?? 0;

        $record->amount =
            $data['amount'];

        $record->balancebefore =
            $data['balancebefore'] ?? 0;

        $record->balanceafter =
            $data['balanceafter'] ?? 0;

        $record->description =
            $data['description'] ?? '';

        $record->createdby =
            $data['createdby'] ?? 0;

        $record->timecreated =
            time();

        return $DB->insert_record(
            'local_corpcredits_txn',
            $record
        );
    }

    public static function get(
        int $id
    ) {

        global $DB;

        return $DB->get_record(
            'local_corpcredits_txn',
            [
                'id' => $id
            ]
        );
    }

    public static function get_company_transactions(
        int $companyid
    ): array {

        global $DB;

        return $DB->get_records(
            'local_corpcredits_txn',
            [
                'companyid' => $companyid
            ],
            'timecreated DESC'
        );
    }

    public static function get_total_credit(
        int $companyid
    ): float {

        global $DB;

        return (float)$DB->get_field_sql(
            "
            SELECT COALESCE(
                SUM(amount),
                0
            )
            FROM {local_corpcredits_txn}
            WHERE companyid = ?
            AND type = 'credit'
            ",
            [
                $companyid
            ]
        );
    }

    public static function get_total_debit(
        int $companyid
    ): float {

        global $DB;

        return (float)$DB->get_field_sql(
            "
            SELECT COALESCE(
                SUM(amount),
                0
            )
            FROM {local_corpcredits_txn}
            WHERE companyid = ?
            AND type = 'debit'
            ",
            [
                $companyid
            ]
        );
    }

    public static function get_company_transactions_paginated(
        int $companyid,
        int $page = 0,
        int $perpage = 20
    ): array {

        global $DB;

        $total = $DB->count_records(
            'local_corpcredits_txn',
            [
                'companyid' => $companyid
            ]
        );

        $records = $DB->get_records(
            'local_corpcredits_txn',
            [
                'companyid' => $companyid
            ],
            'timecreated DESC',
            '*',
            $page * $perpage,
            $perpage
        );

        return [
            'records' => $records,
            'total' => $total
        ];
    }
}