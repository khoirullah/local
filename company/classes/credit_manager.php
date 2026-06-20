<?php

namespace local_company;

defined('MOODLE_INTERNAL') || die();

class credit_manager {

    /**
     * Get company balance.
     *
     * @param int $companyid
     * @return float
     */
    public static function get_balance(int $companyid): float {

        global $DB;

        $wallet = $DB->get_record(
            'local_company_wallet',
            ['companyid' => $companyid],
            'balance',
            IGNORE_MISSING
        );

        return $wallet ? (float)$wallet->balance : 0;
    }

    /**
     * Add credit to company wallet.
     *
     * @param int $companyid
     * @param float $amount
     * @param string $source
     * @param string $description
     * @param string|null $referencecomponent
     * @param int|null $referenceid
     * @param int $createdby
     * @return int transaction id
     */
    public static function credit(
        int $companyid,
        float $amount,
        string $source,
        string $description = '',
        ?string $referencecomponent = null,
        ?int $referenceid = null,
        int $createdby = 0
    ): int {

        global $DB;

        if ($amount <= 0) {
            throw new \moodle_exception('invalidamount');
        }

        $transaction = $DB->start_delegated_transaction();

        $wallet = $DB->get_record(
            'local_company_wallet',
            ['companyid' => $companyid],
            '*',
            MUST_EXIST
        );

        $before = (float)$wallet->balance;
        $after = $before + $amount;

        $wallet->balance = $after;
        $wallet->timemodified = time();

        $DB->update_record('local_company_wallet', $wallet);

        $record = (object)[
            'companyid' => $companyid,
            'type' => 'credit',
            'amount' => $amount,
            'source' => $source,
            'referencecomponent' => $referencecomponent,
            'referenceid' => $referenceid,
            'description' => $description,
            'balancebefore' => $before,
            'balanceafter' => $after,
            'createdby' => $createdby,
            'timecreated' => time()
        ];

        $transactionid = $DB->insert_record(
            'local_company_transaction',
            $record
        );

        $transaction->allow_commit();

        return $transactionid;
    }

    /**
     * Deduct credit from company wallet.
     *
     * @param int $companyid
     * @param float $amount
     * @param string $source
     * @param string $description
     * @param string|null $referencecomponent
     * @param int|null $referenceid
     * @param int $createdby
     * @return int transaction id
     */
    public static function debit(
        int $companyid,
        float $amount,
        string $source,
        string $description = '',
        ?string $referencecomponent = null,
        ?int $referenceid = null,
        int $createdby = 0
    ): int {

        global $DB;

        if ($amount <= 0) {
            throw new \moodle_exception('invalidamount');
        }

        $transaction = $DB->start_delegated_transaction();

        $wallet = $DB->get_record(
            'local_company_wallet',
            ['companyid' => $companyid],
            '*',
            MUST_EXIST
        );

        $before = (float)$wallet->balance;

        if ($before < $amount) {
            throw new \moodle_exception('insufficientbalance');
        }

        $after = $before - $amount;

        $wallet->balance = $after;
        $wallet->timemodified = time();

        $DB->update_record('local_company_wallet', $wallet);

        $record = (object)[
            'companyid' => $companyid,
            'type' => 'debit',
            'amount' => $amount,
            'source' => $source,
            'referencecomponent' => $referencecomponent,
            'referenceid' => $referenceid,
            'description' => $description,
            'balancebefore' => $before,
            'balanceafter' => $after,
            'createdby' => $createdby,
            'timecreated' => time()
        ];

        $transactionid = $DB->insert_record(
            'local_company_transaction',
            $record
        );

        $transaction->allow_commit();

        return $transactionid;
    }

    /**
     * Transfer balance between companies.
     *
     * @param int $fromcompanyid
     * @param int $tocompanyid
     * @param float $amount
     * @param string $description
     * @param int $createdby
     * @return void
     */
    public static function transfer(
        int $fromcompanyid,
        int $tocompanyid,
        float $amount,
        string $description = '',
        int $createdby = 0
    ): void {

        global $DB;

        $transaction = $DB->start_delegated_transaction();

        self::debit(
            $fromcompanyid,
            $amount,
            'transfer',
            $description,
            'local_company',
            $tocompanyid,
            $createdby
        );

        self::credit(
            $tocompanyid,
            $amount,
            'transfer',
            $description,
            'local_company',
            $fromcompanyid,
            $createdby
        );

        $transaction->allow_commit();
    }

    /**
     * Get company transaction history.
     *
     * @param int $companyid
     * @param int $limitfrom
     * @param int $limitnum
     * @return array
     */
    public static function get_transactions(
        int $companyid,
        int $limitfrom = 0,
        int $limitnum = 50
    ): array {

        global $DB;

        return $DB->get_records(
            'local_company_transaction',
            ['companyid' => $companyid],
            'timecreated DESC',
            '*',
            $limitfrom,
            $limitnum
        );
    }
}