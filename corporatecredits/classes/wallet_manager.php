<?php

namespace local_corporatecredits;

defined('MOODLE_INTERNAL') || die();

class wallet_manager {

    public static function welcome_coins(int $companyid): int {
        global $DB;

        $welcomecoins =  (int)get_config(
                'local_corporatecredits',
                'welcomecoins'
            ) ?: 0;

        $record = (object)[
            'companyid'   => $companyid,
            'balance'     => $welcomecoins,
            'status'      => 'active',
            'timecreated' => time(),
            'timemodified'=> time()
        ];

        return $DB->insert_record('local_corpcredits_wallet', $record);
    }

    public static function create_wallet(int $companyid): int {
        global $DB;

        $record = (object)[
            'companyid'   => $companyid,
            'balance'     => 0,
            'status'      => 'active',
            'timecreated' => time(),
            'timemodified'=> time()
        ];

        return $DB->insert_record('local_corpcredits_wallet', $record);
    } 

    public static function get_wallet(int $companyid) {
        global $DB;

        return $DB->get_record(
            'local_corpcredits_wallet',
            ['companyid' => $companyid]
        );
    }

    public static function get_balance(int $companyid): float {

        $wallet = self::get_wallet($companyid);

        if (!$wallet) {
            return 0;
        }

        return (float)$wallet->balance;
    }

    public static function has_balance(
        int $companyid,
        float $amount
    ): bool {

        return self::get_balance($companyid) >= $amount;
    }

    public static function deduct_credit(
        int $companyid,
        float $amount,
        string $source = 'manual',
        int $referenceid = 0,
        string $description = ''
        ): void {

            global $DB;

            $transaction = $DB->start_delegated_transaction();

            $wallet = $DB->get_record(
                'local_corpcredits_wallet',
                [
                    'companyid' => $companyid
                ],
                '*',
                MUST_EXIST
            );

            if ($wallet->balance < $amount) {
                throw new \moodle_exception(
                    'insufficientcredit',
                    'local_corporatecredits'
                );
            }

            $before = (float)$wallet->balance;

            $wallet->balance -= $amount;
            $wallet->timemodified = time();

            $after = (float)$wallet->balance;

            $DB->update_record(
                'local_corpcredits_wallet',
                $wallet
            );

            transaction_manager::create([
                'walletid'       => $wallet->id,
                'companyid'      => $companyid,
                'type'           => 'debit',
                'source'         => $source,
                'referenceid'    => $referenceid,
                'amount'         => $amount,
                'createdby'      => $USER->id ?? 0,
                'balancebefore'  => $before,
                'balanceafter'   => $after,
                'description'    => $description
            ]);

            $transaction->allow_commit();
    }

    public static function add_credit(
        int $companyid,
        float $amount,
        string $source = 'manual',
        int $referenceid = 0,
        string $description = ''
    ): void {
 
        global $DB, $USER;

        $transaction = $DB->start_delegated_transaction();

        $wallet = $DB->get_record(
            'local_corpcredits_wallet',
            [
                'companyid' => $companyid
            ],
            '*',
            MUST_EXIST
        );

        $before = (float)$wallet->balance;

        $wallet->balance += $amount;
        $wallet->timemodified = time();

        $after = (float)$wallet->balance;

        $DB->update_record(
            'local_corpcredits_wallet',
            $wallet
        );

        transaction_manager::create([
            'walletid'       => $wallet->id,
            'companyid'      => $companyid,
            'type'           => 'credit',
            'source'         => $source,
            'referenceid'    => $referenceid,
            'amount'         => $amount,
            'createdby'      => $USER->id ?? 0,
            'balancebefore'  => $before,
            'balanceafter'   => $after,
            'description'    => $description
        ]);

        $transaction->allow_commit();
    }

    public static function get_summary(
        int $companyid
    ): array {

        global $DB;

        $balance =
            self::get_balance(
                $companyid
            );

        $creditin = $DB->get_field_sql(
            "
            SELECT COALESCE(
                SUM(amount),
                0
            ) AS 'creditin'
            FROM {local_corpcredits_txn}
            WHERE companyid = ?
            AND type = 'credit'
            ",
            [$companyid]
        );

        $creditout = $DB->get_field_sql(
            "
            SELECT COALESCE(
                SUM(amount),
                0
            ) AS 'creditout'
            FROM {local_corpcredits_txn}
            WHERE companyid = ?
            AND type = 'debit'
            ",
            [$companyid]
        );

        return [
            'balance'        => number_format($balance, 0, ',', '.'),
            'balance_raw'    => $balance,

            'creditin'       => number_format($creditin, 0, ',', '.'),
            'creditin_raw'   => $creditin,

            'creditout'      => number_format($creditout, 0, ',', '.'),
            'creditout_raw'  => $creditout,
        ];
    }
    
    public static function get_or_create_wallet(
        int $companyid
    ) {

        global $DB;

        $wallet = self::get_or_create_wallet(
            $companyid
        );

        if ($wallet) {
            return $wallet;
        }

        $record = new \stdClass();

        $record->companyid = $companyid;
        $record->balance = 0;
        $record->timecreated = time();
        $record->timemodified = time();

        $record->id = $DB->insert_record(
            'local_corpcredits_wallet',
            $record
        );

        return $record;
    }

    public static function delete_wallet(int $companyid): bool {
        global $DB;

        $wallet = $DB->get_record(
            'local_corpcredits_wallet',
            ['companyid' => $companyid]
        );

        if (!$wallet) {
            return true;
        }

        $wallet->status = 'deleted';
        $wallet->timemodified = time();

        return $DB->update_record(
            'local_corpcredits_wallet',
            $wallet
        );
    }
}