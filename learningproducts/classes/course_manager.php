<?php
namespace local_learningproducts;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

use core_course_copy\copy_helper;

/**
 * Course manager.
 *
 * @package local_learningproducts
 */
class course_manager {

    /**
     * Duplicate template course.
     *
     * @param int $templatecourseid
     * @return int
     */
    public static function duplicate_course(
        int $templatecourseid
    ): int {

        global $DB;

        $course = get_course($templatecourseid);

        $data = copy_helper::create_copy(
            $course->id,
            $course->fullname . ' Copy',
            $course->shortname . '_' . time(),
            $course->category
        );

        copy_helper::execute_copy(
            $data
        );

        return $data->newcourseid;
    }

    /**
     * Rename course for company.
     *
     * @param int $courseid
     * @param int $companyid
     */
    public static function rename_for_company(
        int $courseid,
        int $companyid
    ): void {

        global $DB;

        $company = $DB->get_record(
            'local_company',
            ['id' => $companyid],
            '*',
            MUST_EXIST
        );

        $course = get_course($courseid);

        $course->fullname =
            $course->fullname . ' - ' . $company->name;

        $course->shortname =
            clean_param(
                $course->shortname . '_' . $company->id,
                PARAM_ALPHANUMEXT
            );

        update_course($course);
    }

    /**
     * Move course to company category.
     *
     * @param int $courseid
     * @param int $companyid
     */
    public static function move_to_company_category(
        int $courseid,
        int $companyid
    ): void {

        global $DB;

        $company = $DB->get_record(
            'local_company',
            ['id' => $companyid],
            '*',
            MUST_EXIST
        );

        if (!empty($company->categoryid)) {
            move_courses(
                [$courseid],
                $company->categoryid
            );
        }
    }

    /**
     * Hide course.
     *
     * @param int $courseid
     */
    public static function archive(
        int $courseid
    ): void {

        $course = get_course($courseid);

        $course->visible = 0;

        update_course($course);
    }

    /**
     * Delete course.
     *
     * @param int $courseid
     */
    public static function delete(
        int $courseid
    ): void {

        delete_course(
            $courseid,
            false
        );
    }
}