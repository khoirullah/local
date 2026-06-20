<?php
namespace local_learningproducts;

defined('MOODLE_INTERNAL') || die();

/**
 * Bundle management.
 *
 * Handle:
 * - bundle items
 * - bundle retrieval
 *
 * @package local_learningproducts
 */
class bundle_manager {

    /**
     * Get child products.
     *
     * @param int $bundleid
     * @return array
     */
    public static function get_bundle_products(
        int $bundleid
    ): array {

        global $DB;

        $sql = "
            SELECT
                bi.id AS mappingid,
                p.*
            FROM {local_lp_bundle_items} bi
            JOIN {local_lp_products} p
                ON p.id = bi.productid
            WHERE bi.bundleid = :bundleid
        ";

        return $DB->get_records_sql($sql, [
            'bundleid' => $bundleid
        ]);
    }

    /**
     * Add product to bundle.
     *
     * @param int $bundleid
     * @param int $productid
     * @return int
     */
    public static function add_product(
        int $bundleid,
        int $productid
    ): int {

        global $DB;

        return $DB->insert_record(
            'local_lp_bundle_items',
            (object)[
                'bundleid' => $bundleid,
                'productid' => $productid,
                'sortorder' => 0
            ]
        );
    }

    /**
     * Remove bundle product.
     *
     * @param int $mappingid
     * @return bool
     */
    public static function remove_product(
        int $mappingid
    ): bool {

        global $DB;

        return $DB->delete_records(
            'local_lp_bundle_items',
            ['id' => $mappingid]
        );
    }
}