<?php
namespace local_user_management\service;

defined('MOODLE_INTERNAL') || die();

class certificate_verifier {

    public function verify(string $code): array {
        global $DB;

        $record = $DB->get_record_sql("
            SELECT ci.code, u.firstname, u.lastname, c.fullname AS coursename, FROM_UNIXTIME(ci.timecreated, '%d %b %Y') AS issueddate
                FROM {customcert_issues} ci
                JOIN {user} u ON u.id = ci.userid
                JOIN {customcert} cr ON cr.id = ci.customcertid
                JOIN {course} c ON c.id = cr.course
                WHERE ci.code = :code
        ", ['code' => $code]);

        if (!$record) {
            return ['valid' => false];
        }

        return [
            'valid'      => true,
            'fullname'   => fullname($record),
            'coursename' => $record->coursename,
            'issuedate'  => $record->issueddate,
        ];
    }
}
