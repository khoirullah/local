<?php
namespace local_learningproducts;

defined('MOODLE_INTERNAL') || die();

/**
 * Product management class.
 *
 * Handle:
 * - CRUD product
 * - catalog retrieval
 * - product visibility
 *
 * @package local_learningproducts
 */
class product_manager {

    /**
     * Get all products.
     *
     * @return array
     */
    public static function get_products(): array {
        global $DB;

        $where = "";

        /**
         * Non admin/manager/teacher
         * hanya lihat visible = 1
         */
        if (
            !is_siteadmin() &&
            !has_capability(
                'moodle/site:config',
                \context_system::instance()
            ) &&
            !has_capability(
                'moodle/course:update',
                \context_system::instance()
            )
        ) {

            $where = "WHERE p.visible = 1"; 
        }

        $sql = "
            SELECT
                p.*,
                c.name AS categoryname
            FROM {local_lp_products} p

            LEFT JOIN {local_lp_categories} c
                ON c.id = p.categoryid

            {$where}

            ORDER BY p.id DESC
        ";

        return $DB->get_records_sql($sql);
    }

    /**
     * Get single product.
     *
     * @param int $id
     * @return object|false
     */
    public static function get_product(int $id) {
        global $DB;

        $sql = "
            SELECT p.*,
                c.name AS categoryname
            FROM {local_lp_products} p
        LEFT JOIN {local_lp_categories} c
                ON c.id = p.categoryid
            WHERE p.id = :productid
        ";

        return $DB->get_record_sql($sql, [
            'productid' => $id
        ]);
    }

    /**
     * Create product.
     *
     * @param object $data
     * @return int
     */
    public static function create_product(object $data): int {
        global $DB;

        if (is_array($data->description)) {
            $data->description = $data->description['text'];
        }
        $data->timecreated = time();
        $data->timemodified = time();

        return $DB->insert_record(
            'local_lp_products',
            $data
        );
    }

    /**
     * Update product.
     *
     * @param object $data
     * @return bool
     */
    public static function update_product(object $data): bool {
        global $DB;

        if (is_array($data->description)) {
            $data->description = $data->description['text'];
        }

        $data->timemodified = time();

        return $DB->update_record(
            'local_lp_products',
            $data
        );
    }

    /**
     * Delete product.
     *
     * @param int $id
     * @return bool
     */
    public static function delete_product(int $id): bool {
        global $DB;

        return $DB->delete_records(
            'local_lp_products',
            ['id' => $id]
        );
    }

    public static function get_products_by_category(
        int $categoryid
    ): array {

        global $DB;

        $sql = "
            SELECT *
            FROM {local_lp_products}
            WHERE categoryid = :categoryid
        ORDER BY id DESC
        ";

        return $DB->get_records_sql($sql, [
            'categoryid' => $categoryid
        ]);
    }

    public static function get_product_image_url(
        int $productid
    ): ?\moodle_url {

        global $CFG;

        $context = \context_system::instance();

        $fs = get_file_storage();

        $files = $fs->get_area_files(
            $context->id,
            'local_learningproducts',
            'productimage',
            $productid,
            'itemid, filepath, filename',
            false
        );

        if ($files) {

            $file = reset($files);

            return \moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            );
        }

        return null;
    }

    /**
     * Get Rating product.
     *
     * @param int $id
     * @return object|false
     */
    public static function get_product_rating_summary(
        int $productid
    ): object {

        global $DB;

        $sql = "
            SELECT
                AVG(crs.avgrating) AS avgrating,
                SUM(crs.cntall) AS totalratings
            FROM {local_lp_product_courses} cm
            JOIN {tool_courserating_summary} crs
                ON crs.courseid = cm.courseid
            WHERE cm.productid = :productid
        ";

        $data = $DB->get_record_sql($sql, [
            'productid' => $productid
        ]);

        if (!$data) {

            $data = (object) [
                'avgrating' => 0,
                'totalratings' => 0
            ];
        }

        /**
         * Round rating.
         */
        $data->avgrating =
            round((float)$data->avgrating, 1);

        $data->totalratings =
            (int)$data->totalratings;

        return $data;
    }

    /**
     * Get Participant product.
     *
     * @param int $id
     * @return object|false
     */
    public static function get_product_total_students(
        int $productid
    ): int {

        global $DB;

        $sql = "
            SELECT COUNT(DISTINCT ue.userid)
            FROM {local_lp_product_courses} cm

            JOIN {enrol} e
                ON e.courseid = cm.courseid

            JOIN {user_enrolments} ue
                ON ue.enrolid = e.id

            WHERE cm.productid = :productid
        ";

        return (int) $DB->count_records_sql(
            $sql,
            [
                'productid' => $productid
            ]
        );
    }

    public static function get_product_price(
        int $productid
    ): float {

        $product = self::get_product(
            $productid
        );

        return (float)$product->price;
    }

    public static function get_product_courses(
        int $productid
    ): array {

        global $DB;

        return $DB->get_records(
            'local_lp_product_courses',
            [
                'productid' => $productid
            ]
        );
    }

    public static function get_product_bundle_items(
        int $productid
    ): array {

        global $DB;

        return $DB->get_records(
            'local_lp_bundle_items',
            [
                'bundleid' => $productid
            ]
        );
    }
}