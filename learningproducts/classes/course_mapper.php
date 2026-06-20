<?php
namespace local_learningproducts;

defined('MOODLE_INTERNAL') || die();

/**
 * Product course mapper.
 *
 * Handle:
 * - product course mappings
 * - course retrieval
 * - default course selection
 *
 * @package local_learningproducts
 */
class course_mapper {

    /**
     * Get mapped courses.
     *
     * @param int $productid
     * @return array
     */
    public static function get_courses(int $productid): array {
        global $DB;

        $sql = "
            SELECT
                pc.id AS mappingid,
                c.*
            FROM {local_lp_product_courses} pc
            JOIN {course} c
                ON c.id = pc.courseid
            WHERE pc.productid = :productid
        ORDER BY pc.sortorder ASC
        ";

        return $DB->get_records_sql($sql, [
            'productid' => $productid
        ]);
    }

    /**
     * Add course mapping.
     *
     * @param int $productid
     * @param int $courseid
     * @return int
     */
    public static function add_course(
        int $productid,
        int $courseid
    ): int {

        global $DB;

        //echo $productid.' and '.$courseid;
        $record = (object)[
            'productid' => $productid,
            'courseid' => $courseid,
            'timecreated' => time()
        ];

        return $DB->insert_record(
            'local_lp_product_courses',
            $record
        );
    }

    /**
     * Remove course mapping.
     *
     * @param int $mappingid
     * @return bool
     */
    public static function remove_course(
        int $mappingid
    ): bool {

        global $DB;

        return $DB->delete_records(
            'local_lp_product_courses',
            ['id' => $mappingid]
        );
    }

    /**
     * Remove mapping.
     *
     * @param int $mappingid
     * @return bool
     */
    public static function remove_mapping(
        int $mappingid
    ): bool {

        global $DB;

        return $DB->delete_records(
            'local_lp_product_courses',
            ['id' => $mappingid]
        );
    }
}