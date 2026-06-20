<?php
namespace local_company;

require_once($CFG->dirroot . '/cohort/lib.php');
defined('MOODLE_INTERNAL') || die();

class company_manager {

    public static function create( $data) {
        global $DB;

        $description = '';

        if (!empty($data->description)) {

            if (is_array($data->description)) {
                $description = $data->description['text'] ?? '';
            } else {
                $description = $data->description;
            }
        }

        $record = new \stdClass();
        $record->name         = trim($data['name']);
        $record->shortname    = trim($data['shortname']);
        $record->description  = trim($description);
        //$record->logo         = trim($data['logo']);
        $record->status       = 1;
        $record->timecreated  = time();
        $record->timemodified = time();

        $companyid = $DB->insert_record('local_company', $record);

        // ==========================
        // AUTO CREATE COHORT (SAFE)
        // ==========================

        $cohort = new \stdClass();
        $cohort->name           = $record->name;
        $cohort->idnumber       = $record->shortname;
        $cohort->contextid      = \context_system::instance()->id;
        $cohort->description    = $record->description;
        $cohort->descriptionformat = FORMAT_HTML;
        $cohort->visible        = 1;
        $cohort->component      = 'local_company';

        $cohortid = cohort_add_cohort($cohort);

        // OPTIONAL (recommended)
        $DB->set_field('local_company', 'cohortid', $cohortid, ['id' => $companyid]);

        return $companyid;
    }

    public static function update(int $id, array $data) {
        global $DB;

        $record = $DB->get_record('local_company', ['id' => $id], '*', MUST_EXIST);

        $record->name         = trim($data['name']);
        $record->shortname    = trim($data['shortname']);
        $record->description  = trim($data['description']);
        //$record->logo         = trim($data['logo']);
        $record->timemodified = time();

        $DB->update_record('local_company', $record);

        return $id;
    }

    public static function delete(int $id) {
        global $DB;

        $company = $DB->get_record('local_company', ['id' => $id], '*', MUST_EXIST);

        // ==========================
        // DELETE COHORT (SAFE)
        // ==========================
        if (!empty($company->cohortid)) {

            $cohort = $DB->get_record('cohort', [
                'id' => $company->cohortid,
                'component' => 'local_company'
            ]);

            if ($cohort) {
                cohort_delete_cohort($cohort);
            }
        }

        // ==========================
        // SOFT DELETE COMPANY
        // ==========================
        $company->status = 0;
        $company->timemodified = time();

        return $DB->update_record('local_company', $company);
    }

    public static function get_all($search = '', $status = '', $util = '', $page = 0, $perpage = 9) {
        global $DB;

        $params = ['status' => 1];
        $where = "c.status = :status";

        if (!empty($search)) {
            $where .= " AND c.name LIKE :search";
            $params['search'] = "%{$search}%";
        }

        $today = time();
        $params['today'] = $today;

        $sql = "
            SELECT 
                c.*,
                MAX(s.enddate) as maxenddate,
                COUNT(DISTINCT CASE 
                    WHEN s.enddate >= :today AND s.status = 1 
                    THEN s.courseid 
                END) as activecourses,
                COALESCE(SUM(CASE 
                    WHEN s.status = 1 
                    THEN s.quota 
                END),0) as quota
            FROM {local_company} c
            LEFT JOIN {local_company_subscription} s 
                ON s.companyid = c.id
            WHERE $where
            GROUP BY c.id
            ORDER BY c.timecreated DESC
        ";

        $records = $DB->get_records_sql($sql, $params);

        $nextmonth = strtotime('+30 days', $today);

        foreach ($records as $record) {

            /* =====================
            STATUS
            ===================== */

            if (empty($record->maxenddate) || $record->maxenddate < $today) {
                $record->clientstatus = 'Suspended';
                $record->statusclass = 'badge bg-secondary text-muted';
            } elseif ($record->maxenddate <= $nextmonth) {
                $record->clientstatus = 'Expiring';
                $record->statusclass = 'badge bg-warning text-dark';
            } else {
                $record->clientstatus = 'Active';
                $record->statusclass = 'badge bg-success';
            }

            /* =====================
            USED
            ===================== */

            $used = 0;
            if (!empty($record->cohortid)) {
                $used = $DB->count_records('cohort_members', [
                    'cohortid' => $record->cohortid
                ]);
            }

            $record->used = $used;
            $record->left = max(0, $record->quota - $used);
            $record->util = $record->quota > 0
                ? round(($used / $record->quota) * 100)
                : 0;

            /* =====================
            UTILIZATION LEVEL
            ===================== */

            if ($record->util >= 70) {
                $record->utiltext = 'High';
                $record->rowclass = 'text-danger';
            } elseif ($record->util >= 40) {
                $record->utiltext = 'Medium';
                $record->rowclass = 'text-warning';
            } else {
                $record->utiltext = 'Low';
                $record->rowclass = 'text-success';
            }
        }

        /* =====================
        FILTER (PHP LEVEL)
        ===================== */

        $records = array_filter($records, function($r) use ($status, $util) {

            if (!empty($status) && $r->clientstatus !== $status) {
                return false;
            }

            if (!empty($util) && $r->utiltext !== $util) {
                return false;
            }

            return true;
        });

        $total = count($records);

        // Paging manual setelah filter
        $records = array_slice(
            array_values($records),
            $page * $perpage,
            $perpage
        );

        return [$records, $total];
    }

    public static function get(int $id) {
        global $DB;
        
        return $DB->get_record('local_company', ['id' => $id], '*', MUST_EXIST);
    }

    public static function get_user_company(int $userid) {
        global $DB;

        $companyuser = $DB->get_record(
            'local_company_user',
            ['userid' => $userid]
        );

        if (!$companyuser) {
            return false;
        }

        return $DB->get_record(
            'local_company',
            ['id' => $companyuser->companyid]
        );
    }

    public static function get_company_users(
        int $companyid
    ): array {

        global $DB;

        return $DB->get_records(
            'local_company_member',
            [
                'companyid' => $companyid
            ]
        );
    }

    public static function update_user_company_profile(
        int $userid,
        string $companyname
    ) {
        global $DB;

        $DB->set_field(
            'user',
            'institution',
            $companyname,
            ['id' => $userid]
        );

        $field = $DB->get_record(
            'user_info_field',
            ['shortname' => 'company']
        );

        if (!$field) {
            return;
        }

        $data = $DB->get_record(
            'user_info_data',
            [
                'userid' => $userid,
                'fieldid' => $field->id
            ]
        );

        if ($data) {
            $data->data = $companyname;
            $DB->update_record('user_info_data', $data);
        } else {
            $data = new stdClass();
            $data->userid = $userid;
            $data->fieldid = $field->id;
            $data->data = $companyname;
            $data->dataformat = 0;

            $DB->insert_record('user_info_data', $data);
        }
    }

    public static function is_company_pic(int $userid): bool {
        global $DB;

        return $DB->record_exists(
            'local_company_user',
            [
                'userid' => $userid,
                'role' => 'pic'
            ]
        );
    }

    public static function get_pic_company(int $userid) {
        global $DB;

        $sql = "
            SELECT c.*
            FROM {local_company} c
            JOIN {local_company_user} cu
                ON cu.companyid = c.id
            WHERE cu.userid = ?
            AND cu.role = 'pic'
        ";

        return $DB->get_record_sql($sql, [$userid]);
    }
}