<?php

namespace local_credits;

defined('MOODLE_INTERNAL') || die();

class enrolment_manager {

    public function purchase_course(
        int $userid,
        int $courseid,
        int $credits
    ): bool {

        global $DB;

        $transaction = $DB->start_delegated_transaction();

        try {

            $wallet = new wallet_manager();

            if (!$wallet->deduct_balance($userid, $credits)) {
                return false;
            }

            enrol_try_internal_enrol($courseid, $userid);

            $txn = new transaction_manager();

            $txn->create_transaction([
                'userid' => $userid,
                'type' => 'purchase',
                'amount' => -$credits,
                'description' => 'Course purchase'
            ]);

            $transaction->allow_commit();

            return true;

        } catch (\Exception $e) {

            $transaction->rollback($e);
        }
    }
}