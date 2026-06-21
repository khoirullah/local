<?php

namespace local_company;

defined('MOODLE_INTERNAL') || die();

class assignment_manager {

    /**
     * Assign user.
     */
    public static function assign_user(
        int $entitlementid,
        int $userid,
        int $assignedby
    ): int {

        global $DB;

        if (
            !$DB->record_exists(
                'local_company_entitlement',
                ['id' => $entitlementid]
            )
        ) {
            throw new \moodle_exception(
                'invalidentitlement',
                'local_company'
            );
        }

        if (
            $DB->record_exists(
                'local_company_assignment',
                [
                    'entitlementid' => $entitlementid,
                    'userid' => $userid
                ]
            )
        ) {
            throw new \moodle_exception(
                'useralreadyassigned',
                'local_company'
            );
        }

        if (
            !entitlement_manager::has_available_seats(
                $entitlementid
            )
        ) {
            throw new \moodle_exception(
                'noseatsavailable',
                'local_company'
            );
        }

        $record = new \stdClass();

        $record->entitlementid = $entitlementid;
        $record->userid = $userid;
        $record->assignedby = $assignedby;

        $record->status = 'active';

        $record->timecreated = time();

        $id = $DB->insert_record(
            'local_company_assignment',
            $record
        );

        entitlement_manager::consume_seat(
            $entitlementid
        );

        return $id;
    }

    /**
     * Unassign user.
     */
    public static function unassign_user(
        int $assignmentid
    ): bool {

        global $DB;

        $assignment = $DB->get_record(
            'local_company_assignment',
            [
                'id' => $assignmentid
            ],
            '*',
            MUST_EXIST
        );

        $assignment->status = 'inactive';

        $DB->update_record(
            'local_company_assignment',
            $assignment
        );

        entitlement_manager::release_seat(
            $assignment->entitlementid
        );

        return true;
    }

    /**
     * Get assignments.
     */
    public static function get_entitlement_assignments(
        int $entitlementid
    ): array {

        global $DB;

        return $DB->get_records(
            'local_company_assignment',
            [
                'entitlementid' => $entitlementid,
                'status' => 'active'
            ]
        );
    }

    /**
     * Get user assignments.
     */
    public static function get_user_assignments(
        int $userid
    ): array {

        global $DB;

        return $DB->get_records(
            'local_company_assignment',
            [
                'userid' => $userid,
                'status' => 'active'
            ]
        );
    }

    /**
     * Check assignment.
     */
    public static function is_assigned(
        int $entitlementid,
        int $userid
    ): bool {

        global $DB;

        return $DB->record_exists(
            'local_company_assignment',
            [
                'entitlementid' => $entitlementid,
                'userid' => $userid,
                'status' => 'active'
            ]
        );
    }

    /**
     * Get Company assignment.
     */
    public static function get_company_assignments(
        int $companyid
    ): array {

        global $DB;

        $sql = "
            SELECT
                a.*,

                u.firstname,
                u.lastname,
                u.email,

                e.productid,

                p.name AS productname

            FROM {local_company_assignment} a

            JOIN {user} u
                ON u.id = a.userid

            JOIN {local_company_entitlement} e
                ON e.id = a.entitlementid

            JOIN {local_lp_products} p
                ON p.id = e.productid

            WHERE e.companyid = :companyid

            AND a.status = 'active'

            ORDER BY a.timecreated DESC
        ";

        $records = $DB->get_records_sql(
            $sql,
            [
                'companyid' => $companyid
            ]
        );

        foreach ($records as $record) {
            $record->fullname = fullname($record);
        }

        return array_values($records);
    }
}