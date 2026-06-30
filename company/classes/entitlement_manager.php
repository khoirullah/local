<?php

namespace local_company;

defined('MOODLE_INTERNAL') || die();

class entitlement_manager {

    /**
     * Get active entitlements for a company.
     *
     * @param int $companyid
     * @return array
     */
    public static function get_company_entitlements(
        int $companyid
    ): array {

        global $DB;

        $sql = "
            SELECT
                s.id AS subscriptionid,
                s.companyid,
                s.courseid,
                s.quota,
                s.startdate,
                s.enddate,
                s.status,

                p.id AS productid,
                p.name AS productname,

                c.fullname,
                cc.name AS category,

                COUNT(DISTINCT ue.userid) AS used,
                COUNT(
                    DISTINCT CASE
                        WHEN ccp.timecompleted IS NOT NULL
                        THEN ue.userid
                    END
                ) AS completed

            FROM {local_company_subscription} s

            JOIN {course} c
                ON c.id = s.courseid

            LEFT JOIN {local_lp_product_courses} cm
                ON cm.courseid = c.id

            LEFT JOIN {local_lp_products} p
                ON p.id = cm.productid

            LEFT JOIN {course_categories} cc
                ON cc.id = c.category

            LEFT JOIN {enrol} e
                ON e.courseid = c.id

            LEFT JOIN {user_enrolments} ue
                ON ue.enrolid = e.id

            LEFT JOIN {course_completions} ccp
                ON ccp.course = c.id
            AND ccp.userid = ue.userid

            WHERE s.companyid = :companyid
            AND s.status = 1

            GROUP BY
                s.id,
                s.companyid,
                s.courseid,
                s.quota,
                s.startdate,
                s.enddate,
                s.status,
                p.id,
                p.name,
                c.fullname,
                cc.name

            ORDER BY
                p.name,
                c.fullname
        ";

        $records = $DB->get_records_sql(
            $sql,
            [
                'companyid' => $companyid
            ]
        );

        foreach ($records as $record) {

            $record->remaining = max(
                0,
                $record->quota - $record->used
            );

            $record->completionrate =
                $record->used > 0
                    ? round(
                        ($record->completed / $record->used) * 100
                    )
                    : 0;
        }

        return array_values($records);
    }
}