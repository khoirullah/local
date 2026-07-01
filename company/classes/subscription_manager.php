<?php
namespace local_company;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/enrol/self/locallib.php');

class subscription_manager {
 
    public static function get_by_company(int $companyid, $search="", $status="") {
        global $DB;

        $params = ['companyid' => $companyid];
        $where = "s.companyid = :companyid";

        if (!empty($search)) {
            $where .= " AND c.fullname LIKE :search";
            $params['search'] = "%{$search}%";
        }

        if ($status !== '') {
            $where .= " AND s.status = :status";
            $params['status'] = $status;
        }

        $sql = "
            SELECT 
                s.*,
                c.fullname, c.id AS courseid, cat.name AS category,

                COUNT(cc.userid) AS totalusers,

                SUM(
                    CASE 
                        WHEN cc.timecompleted IS NOT NULL 
                        THEN 1 
                        ELSE 0 
                    END
                ) AS completed,

                COALESCE(
                    ROUND(
                        (SUM(
                            CASE 
                                WHEN cc.timecompleted IS NOT NULL 
                                THEN 1 
                                ELSE 0 
                            END
                        ) / NULLIF(COUNT(cc.userid),0)) * 100
                    ),
                0) AS completionrate

            FROM {local_company_subscription} s

            JOIN {course} c 
                ON c.id = s.courseid
            LEFT JOIN {course_categories} cat 
                ON cat.id = c.category
            LEFT JOIN {course_completions} cc
                ON cc.course = s.courseid

            WHERE 
            $where

            GROUP BY s.id

            ORDER BY s.timecreated DESC
        ";

        return $DB->get_records_sql($sql, $params);
    }

    public static function create(array $data) {
        global $DB; 

        $record = new \stdClass();
        $record->companyid   = $data['companyid'];
        $record->courseid    = $data['courseid'];
        $record->quota       = $data['quota'];
        $record->startdate   = $data['startdate'];
        $record->enddate     = $data['enddate'];
        $record->status      = 1;
        $record->timecreated = time();
        $record->timemodified= time();

        $subscriptionid = $DB->insert_record('local_company_subscription', $record);

        // 🔥 AUTO CREATE SELF ENROL INSTANCE
        self::create_self_enrol_instance($record);

        return $subscriptionid;
    }

    private static function create_self_enrol_instance($subscription) {
        global $DB;

        if (empty($subscription->courseid)) {
            throw new \moodle_exception('Course ID missing');
        }
        // Ambil course object dulu
        $course = $DB->get_record('course',
            ['id' => $subscription->courseid],
            '*',
            MUST_EXIST
        );

        $company = $DB->get_record('local_company',
            ['id' => $subscription->companyid],
            '*',
            MUST_EXIST
        );

        $cohort = $DB->get_record('cohort',
            ['idnumber' => $company->shortname],
            '*',
            MUST_EXIST
        );

        $studentrole = $DB->get_record('role',
            ['shortname' => 'student'],
            '*',
            MUST_EXIST
        );

        $plugin = enrol_get_plugin('self');
        if (!$plugin) {
            return;
        }

        $fields = [
            'status'            => ENROL_INSTANCE_ENABLED,
            'name'              => $company->shortname . ' Enrolment',
            'enrolperiod'       => 0,
            'enrolstartdate'    => $subscription->startdate,
            'enrolenddate'      => $subscription->enddate,
            'customint3'        => $subscription->quota,
            'customint4'        => 1,
            'customint6'        => 1,
            'customint5'        => $cohort->id,
            'password'          => $company->shortname,
            'roleid'            => $studentrole->id
        ];

        // 🔥 kirim object $course, bukan ID
        $plugin->add_instance($course, $fields);
    }

    public static function delete(int $subscriptionid) {
        global $DB;

        $subscription = $DB->get_record(
            'local_company_subscription',
            ['id' => $subscriptionid],
            '*',
            MUST_EXIST
        );

        // 🔥 Hapus self enrol instance yang sesuai
        $company = $DB->get_record('local_company',
            ['id' => $subscription->companyid],
            '*',
            MUST_EXIST
        );

        $instances = $DB->get_records('enrol', [
            'courseid' => $subscription->courseid,
            'enrol'    => 'self',
            'name'     => $company->shortname . ' Enrolment'
        ]);

        foreach ($instances as $instance) {
            $plugin = enrol_get_plugin('self');
            $plugin->delete_instance($instance);
        }

        // Hapus subscription
        $DB->delete_records('local_company_subscription', [
            'id' => $subscriptionid
        ]);
    }
 
    public static function get_used_count(int $companyid, int $courseid) {
        global $DB;

        $sql = "SELECT COUNT(DISTINCT ue.userid)
                FROM {local_company_user} cu
                JOIN {user_enrolments} ue ON ue.userid = cu.userid
                JOIN {enrol} e ON e.id = ue.enrolid
                WHERE cu.companyid = :companyid
                  AND e.courseid = :courseid";

        return $DB->count_records_sql($sql, [
            'companyid' => $companyid,
            'courseid'  => $courseid
        ]);
    }

    public static function get_enrolment_chart_data(int $companyid, ?int $courseid = null, ?int $year = null): array {
        global $DB;

        if (!$year) {
            $year = date('Y');
        }

        $start = strtotime("{$year}-01-01 00:00:00");
        $end   = strtotime("{$year}-12-31 23:59:59");

        $params = [
            'companyid' => $companyid,
            'start' => $start,
            'end' => $end
        ];

        $coursefilter = "";
        if (!empty($courseid)) {
            $coursefilter = "AND e.courseid = :courseid";
            $params['courseid'] = $courseid;
        }

        $sql = "
            SELECT 
                MONTH(FROM_UNIXTIME(ue.timecreated)) AS month,
                COUNT(ue.id) AS total
            FROM {local_company_user} cu
            JOIN {user_enrolments} ue 
                ON ue.userid = cu.userid
            JOIN {enrol} e 
                ON e.id = ue.enrolid
            WHERE cu.companyid = :companyid
            AND ue.timecreated BETWEEN :start AND :end
            $coursefilter
            GROUP BY month
        ";

        $records = $DB->get_records_sql($sql, $params);

        $labels = [];
        $data = [];

        for ($m = 1; $m <= 12; $m++) {

            $labels[] = date('M', mktime(0,0,0,$m,1));

            if (isset($records[$m])) {
                $data[] = (int)$records[$m]->total;
            } else {
                $data[] = 0;
            }
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    public static function get_year_list(): array {

        $current = date('Y');

        $years = [];
        for ($i = $current; $i >= $current - 5; $i--) {
            $years[] = ['year' => $i];
        }

        return $years;
    }

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