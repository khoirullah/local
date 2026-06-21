<?php

namespace local_company;

defined('MOODLE_INTERNAL') || die();

class member_manager {

    public static function add_member(
        int $companyid,
        int $userid,
        string $role = 'employee'
    ): int {

        global $DB;

        $existing = $DB->get_record(
            'local_company_member',
            [
                'companyid' => $companyid,
                'userid' => $userid
            ]
        );

        if ($existing) {
            return $existing->id;
        }

        $record = new \stdClass();

        $record->companyid = $companyid;
        $record->userid = $userid;
        $record->role = $role;
        $record->status = 'active';
        $record->timecreated = time();

        \local_company\log_manager::add(
            $companyid,
            $userid,
            'member_added',
            'User added to company'
        );
        
        return $DB->insert_record(
            'local_company_member',
            $record
        );
    }

    public static function remove_member(
        int $companyid,
        int $userid,
        int $removedby = 0
    ): bool {

        global $DB;

        $transaction = $DB->start_delegated_transaction();

        try {

            $member = $DB->get_record(
                'local_company_member',
                [
                    'companyid' => $companyid,
                    'userid' => $userid
                ],
                '*',
                MUST_EXIST
            );

            $activeassignments =
                $DB->count_records_sql(
                    "
                        SELECT COUNT(1)

                        FROM {local_company_assignment} a

                        JOIN {local_company_entitlement} e
                            ON e.id = a.entitlementid

                        WHERE e.companyid = ?
                        AND a.userid = ?
                        AND a.status = ?
                    ",
                    [
                        $companyid,
                        $userid,
                        'active'
                    ]
                );

            if ($activeassignments > 0) {

                throw new \moodle_exception(
                    'memberhasassignments',
                    'local_company'
                );
            }

            $member->status = 'inactive';

            $DB->update_record(
                'local_company_member',
                $member
            );

            log_manager::add(
                $companyid,
                $removedby,
                'member_removed',
                'User #' . $userid . ' removed from company'
            );

            $transaction->allow_commit();

            return true;

        } catch (\Throwable $e) {

            $transaction->rollback($e);

            throw $e;
        }
    }

    public static function is_member(
        int $companyid,
        int $userid
    ): bool {

        global $DB;

        return $DB->record_exists(
            'local_company_member',
            [
                'companyid' => $companyid,
                'userid' => $userid,
                'status' => 'active'
            ]
        );
    }

    public static function get_company_members(
        int $companyid
    ): array {

        global $DB;

        $company = $DB->get_record(
            'local_company',
            ['id' => $companyid],
            'id, cohortid',
            MUST_EXIST
        );

        if (empty($company->cohortid)) {
            return [];
        }

        $sql = "
            SELECT
                u.id,
                u.firstname,
                u.lastname,
                u.email,
                u.username,
                u.department,
                cu.role

            FROM {cohort_members} cm

            JOIN {user} u
                ON u.id = cm.userid

            JOIN {cohort} c
                ON c.id = cm.cohortid

            JOIN {local_company} com
                ON com.cohortid = c.id

            JOIN {local_company_user} cu
                ON cu.userid = cm.userid AND cu.companyid = com.id

            WHERE cm.cohortid = ?

            ORDER BY u.firstname, u.lastname
        ";

        $records = $DB->get_records_sql(
            $sql,
            [$company->cohortid]
        );

        foreach ($records as $record) {
            $record->fullname = fullname($record);
        }

        return array_values($records);
    }

    public static function get_user_products(
        int $userid
    ): array {

        global $DB;

        $records = $DB->get_records_sql(
            "
            SELECT DISTINCT e.productid
            FROM {local_company_assignment} a
            JOIN {local_company_entitlement} e
                ON e.id = a.entitlementid
            WHERE a.userid = ?
            AND a.status = 'active'
            AND e.status = 'active'
            ",
            [$userid]
        );

        return array_map(
            fn($r) => (int)$r->productid,
            $records
        );
    }

    public static function search_members(
        int $companyid,
        string $query = ''
    ): array {

        global $DB;

        $params = [
            'companyid' => $companyid
        ];

        $where = "
            m.companyid = :companyid
            AND m.status = 'active'
        ";

        if (!empty($query)) {

            $where .= "
                AND (
                    " . $DB->sql_like('u.firstname', ':q1') . "
                    OR
                    " . $DB->sql_like('u.lastname', ':q2') . "
                    OR
                    " . $DB->sql_like('u.email', ':q3') . "
                )
            ";

            $params['q1'] = "%{$query}%";
            $params['q2'] = "%{$query}%";
            $params['q3'] = "%{$query}%";
        }

        return $DB->get_records_sql(
            "
            SELECT
                m.*,
                u.firstname,
                u.lastname,
                u.email

            FROM {local_company_member} m

            JOIN {user} u
                ON u.id = m.userid

            WHERE {$where}

            ORDER BY u.firstname
            ",
            $params
        );
    }

    public static function get_role(
        int $companyid,
        int $userid
    ): ?string {

        global $DB;

        $member = $DB->get_record(
            'local_company_member',
            [
                'companyid' => $companyid,
                'userid' => $userid,
                'status' => 'active'
            ]
        );

        return $member
            ? $member->role
            : null;
    }

    public static function is_pic(
        int $companyid,
        int $userid
    ): bool {

        return self::get_role(
            $companyid,
            $userid
        ) === 'pic';
    }

    public static function is_hod(
        int $companyid,
        int $userid
    ): bool {

        return self::get_role(
            $companyid,
            $userid
        ) === 'hod';
    }

    public static function is_employee(
        int $companyid,
        int $userid
    ): bool {

        return self::get_role(
            $companyid,
            $userid
        ) === 'employee';
    }
}