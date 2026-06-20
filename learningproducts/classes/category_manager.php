<?php
namespace local_learningproducts;

defined('MOODLE_INTERNAL') || die();

/**
 * Product category manager.
 *
 * Handle:
 * - category CRUD
 * - category retrieval
 *
 * @package local_learningproducts
 */
class category_manager {

    /**
     * Get all visible categories.
     *
     * @return array
     */
    public static function get_categories(): array {

        global $DB;

        return $DB->get_records(
            'local_lp_categories',
            ['visible' => 1],
            'sortorder ASC, id ASC'
        );
    }

    /**
     * Get category options for forms.
     *
     * @return array
     */
    public static function get_category_options(): array {

        $categories = self::get_categories();

        $options = [];

        foreach ($categories as $category) {

            $options[$category->id] =
                format_string($category->name);
        }

        return $options;
    }

    public static function get_category(int $id) {
        global $DB;

        return $DB->get_record(
            'local_lp_categories',
            ['id' => $id]
        );
    }

    /**
     * Create category.
     */
    public static function create_category(object $data): int {

        global $DB;

        if (is_array($data->description)) {

            $data->description =
                $data->description['text'];
        }

        $data->timecreated = time();

        return $DB->insert_record(
            'local_lp_categories',
            $data
        );
    }

    /**
     * Update category.
     */
    public static function update_category(object $data): bool {

        global $DB;

        if (is_array($data->description)) {

            $data->description =
                $data->description['text'];
        }

        return $DB->update_record(
            'local_lp_categories',
            $data
        );
    }

    /**
     * Delete category.
     */
    public static function delete_category(
        int $id
    ): bool {

        global $DB;

        return $DB->delete_records(
            'local_lp_categories',
            ['id' => $id]
        );
    }
}