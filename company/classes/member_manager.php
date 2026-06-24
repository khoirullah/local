<?php

namespace local_company;

defined('MOODLE_INTERNAL') || die();

class member_manager {

    public static function add_member(
        int $companyid,
        int $userid,
        ?string $role = null
    ): int {

        global $DB;

        $exists = $DB->record_exists(
            'local_company_user',
            [
                'companyid' => $companyid,
                'userid' => $userid
            ]
        );

        if ($exists) {
            return $userid;
        }

        $company = $DB->get_record(
            'local_company',
            ['id' => $companyid],
            'id, cohortid',
            MUST_EXIST
        );

        // User pertama otomatis PIC.
        if ($role === null) {

            $haspic = $DB->record_exists(
                'local_company_user',
                [
                    'companyid' => $companyid,
                    'role' => 'pic'
                ]
            );

            $role = $haspic
                ? 'employee'
                : 'pic';
        }

        $companyuser = new \stdClass();
        $companyuser->companyid   = $companyid;
        $companyuser->userid      = $userid;
        $companyuser->role        = $role;
        $companyuser->timecreated = time();

        $DB->insert_record(
            'local_company_user',
            $companyuser
        );

        // Cohort membership.
        if (
            !empty($company->cohortid)
            && !cohort_is_member(
                $company->cohortid,
                $userid
            )
        ) {

            cohort_add_member(
                $company->cohortid,
                $userid
            );
        }

        // Moodle role assignment.
        if ($role === 'pic') {

            $picrole = $DB->get_record(
                'role',
                ['shortname' => 'pic'],
                '*',
                IGNORE_MISSING
            );

            if ($picrole) {

                $systemcontext =
                    \context_system::instance();

                if (
                    !user_has_role_assignment(
                        $userid,
                        $picrole->id,
                        $systemcontext->id
                    )
                ) {

                    role_assign(
                        $picrole->id,
                        $userid,
                        $systemcontext->id
                    );
                }
            }
        }

        return $userid;
    }

    public static function has_pic(
        int $companyid
    ): bool {

        global $DB;

        return $DB->record_exists(
            'local_company_user',
            [
                'companyid' => $companyid,
                'role' => 'pic'
            ]
        );
    }

    public static function promote_to_pic(
        int $companyid,
        int $userid
    ): void {

        global $DB;

        $member = $DB->get_record(
            'local_company_user',
            [
                'companyid' => $companyid,
                'userid' => $userid
            ],
            '*',
            MUST_EXIST
        );

        $member->role = 'pic';

        $DB->update_record(
            'local_company_user',
            $member
        );

        $role = $DB->get_record(
            'role',
            ['shortname' => 'pic'],
            '*',
            IGNORE_MISSING
        );

        if ($role) {

            $systemcontext =
                \context_system::instance();

            if (
                !user_has_role_assignment(
                    $userid,
                    $role->id,
                    $systemcontext->id
                )
            ) {

                role_assign(
                    $role->id,
                    $userid,
                    $systemcontext->id
                );
            }
        }
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
            $record->editurl = (
                new \moodle_url(
                    '/user/edit.php',
                    [
                        'id' => $record->id,
                        'returnto' => 'profile'
                    ]
                )
            )->out(false);
            $record->suspendurl = (
                new \moodle_url(
                    '/local/company/suspend.php',
                    [
                        'id' => $record->id
                    ]
                )
            )->out(false);
        }

        return array_values($records);
    }
    /* public static function add_member(
        int $companyid,
        int $userid,
        string $role = 'employee'
    ): int {

        global $DB;

        $exists = $DB->record_exists(
            'local_company_user',
            [
                'companyid' => $companyid,
                'userid' => $userid
            ]
        );

        if (!$exists) {

            $companyuser = new \stdClass();
            $companyuser->companyid   = $companyid;
            $companyuser->userid      = $userid;
            $companyuser->role        = 'employee';
            $companyuser->timecreated = time();

            $DB->insert_record(
                'local_company_user',
                $companyuser
            );

            // Add user to cohort if not already member.
            if (!cohort_is_member($cohortid, $userid)) {
                cohort_add_member(
                    $cohortid,
                    $userid
                );
            }
        }
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
    } */
}