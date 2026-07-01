<?php
namespace local_company;

require_once($CFG->dirroot . '/cohort/lib.php');
defined('MOODLE_INTERNAL') || die();

class company_manager {

    public static function create($data) {
        global $DB, $USER;

        $userid = $USER->id;
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
        $record->status       = 1;
        $record->timecreated  = time();
        $record->timemodified = time();

        $companyid = $DB->insert_record('local_company', $record);

        $is_exist = \local_corporatecredits\wallet_manager::get_wallet($companyid);
        
        if ($is_exist) {
            \local_corporatecredits\wallet_manager::create_wallet($companyid);
        } else {
            \local_corporatecredits\wallet_manager::welcome_coins($companyid);
        }
        
        // ==========================
        // CREATE COHORT
        // ==========================

        $cohort = new \stdClass();
        $cohort->name              = $record->name;
        $cohort->idnumber          = $record->shortname;
        $cohort->contextid         = \context_system::instance()->id;
        $cohort->description       = $record->description;
        $cohort->descriptionformat = FORMAT_HTML;
        $cohort->visible           = 1;
        $cohort->component         = 'local_company';

        $cohortid = cohort_add_cohort($cohort);

        $DB->set_field(
            'local_company',
            'cohortid',
            $cohortid,
            ['id' => $companyid]
        );

        // ==========================
        // AUTO PIC
        // ==========================
        if (!is_siteadmin($userid)) {

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
                $companyuser->role        = 'pic';
                $companyuser->timecreated = time();

                $DB->insert_record(
                    'local_company_user',
                    $companyuser
                );

                // Assign system role "pic".
                $role = $DB->get_record(
                    'role',
                    ['shortname' => 'pic'],
                    '*',
                    IGNORE_MISSING
                );

                if ($role) {

                    $systemcontext = \context_system::instance();

                    if (!user_has_role_assignment(
                        $userid,
                        $role->id,
                        $systemcontext->id
                    )) {

                        role_assign(
                            $role->id,
                            $userid,
                            $systemcontext->id
                        );
                    }
                }
            }

            // Add user to cohort if not already member.
            if (!cohort_is_member($cohortid, $userid)) {
                cohort_add_member(
                    $cohortid,
                    $userid
                );
            }
        }

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

        $company = $DB->get_record(
            'local_company',
            ['id' => $id],
            '*',
            MUST_EXIST
        );

        // ==========================
        // REMOVE COMPANY WALLET
        // ==========================
        \local_corporatecredits\wallet_manager::delete_wallet($company->id);

        // ==========================
        // REMOVE COMPANY USERS
        // ==========================
        $companyusers = $DB->get_records(
            'local_company_user',
            ['companyid' => $id]
        );

        $role = $DB->get_record(
            'role',
            ['shortname' => 'pic'],
            '*',
            IGNORE_MISSING
        );

        $systemcontext = \context_system::instance();

        foreach ($companyusers as $companyuser) {

            // Remove cohort membership.
            if (!empty($company->cohortid)
                && cohort_is_member(
                    $company->cohortid,
                    $companyuser->userid
                )) {

                cohort_remove_member(
                    $company->cohortid,
                    $companyuser->userid
                );
            }

            // Unassign PIC role only if user is not PIC elsewhere.
            if ($role && $companyuser->role === 'pic') {

                $othercompanies = $DB->count_records_select(
                    'local_company_user',
                    'userid = ? AND role = ? AND companyid <> ?',
                    [
                        $companyuser->userid,
                        'pic',
                        $id
                    ]
                );

                if ($othercompanies == 0) {

                    role_unassign(
                        $role->id,
                        $companyuser->userid,
                        $systemcontext->id
                    );
                }
            }
        }

        // Delete company-user mappings.
        $DB->delete_records(
            'local_company_user',
            ['companyid' => $id]
        );

        // Suspend user jika tidak tergabung di company lain.
        $othermemberships = $DB->count_records_select(
            'local_company_user',
            'userid = ? AND companyid <> ?',
            [
                $companyuser->userid,
                $id
            ]
        );

        if ($othermemberships == 0) {

            $user = $DB->get_record(
                'user',
                ['id' => $companyuser->userid],
                '*',
                IGNORE_MISSING
            );

            if ($user && !is_siteadmin($user)) {

                $originalemail = $user->email;

                $user->suspended = 1;

                $user->email =
                    'deleted_' .
                    $user->id . '_' .
                    time() . '_' .
                    random_int(1000, 9999) .
                    '@deleted.local';

                $user->timemodified = time();

                $DB->update_record('user', $user);
            }
        }
        
        // ==========================
        // DELETE COHORT
        // ==========================
        if (!empty($company->cohortid)) {

            $cohort = $DB->get_record(
                'cohort',
                [
                    'id' => $company->cohortid,
                    'component' => 'local_company'
                ]
            );

            if ($cohort) {
                cohort_delete_cohort($cohort);
            }
        }

        // ==========================
        // SOFT DELETE COMPANY
        // ==========================
        $company->status = 0;
        $company->timemodified = time();

        return $DB->update_record(
            'local_company',
            $company
        );
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
            $data = new \stdClass();
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

    public static function get_company_admins(int $companyid) {
        global $DB;

        $sql = "
            SELECT c.*, cu.*
            FROM {local_company} c
            JOIN {local_company_user} cu
                ON cu.companyid = c.id
            WHERE c.id = ?
            AND cu.role = 'pic'
        ";

        return $DB->get_record_sql($sql, [$companyid]);
    }
}