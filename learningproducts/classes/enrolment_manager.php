<?php
namespace local_learningproducts;

use local_company\company_manager;

defined('MOODLE_INTERNAL') || die();

/**
 * Enrollment manager.
 *
 * Handle:
 * - auto enrollment
 * - bundle enrollment
 * - resolver integration
 *
 * @package local_learningproducts
 */
class enrolment_manager {

    /**
     * Enroll user into product.
     *
     * @param int $productid
     * @param int $userid
     * @return bool
     */
    public static function enrol_product(
        int $productid,
        int $userid
    ): bool {

        

        $product =
            product_manager::get_product(
                $productid
            );

        if (!$product) {
            return false;
        }

        if ($product->type === 'bundle') {

            return self::enrol_bundle(
                $productid,
                $userid
            );
        }

        $courses =
            course_mapper::get_courses(
                $productid
            );

        if (empty($courses)) {
            return false;
        }

        $success = true;

        foreach ($courses as $course) {

            $result =
                self::enrol_user_to_course(
                    $userid,
                    $course->id
                );

            if (!$result) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Enroll bundle products.
     *
     * @param int $bundleid
     * @param int $userid
     * @return bool
     */
    protected static function enrol_bundle(
        int $bundleid,
        int $userid
    ): bool {

        $products =
            bundle_manager::get_bundle_products(
                $bundleid
            );

        $success = true;

        foreach ($products as $product) {

            $result = self::enrol_product(
                $product->id,
                $userid
            );

            if (!$result) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Moodle enrollment logic.
     *
     * @param int $userid
     * @param int $courseid
     * @return bool
     */
    protected static function enrol_user_to_course(
        int $userid,
        int $courseid
    ): bool {

        $context =
            \context_course::instance(
                $courseid
            );

        if (
            is_enrolled(
                $context,
                $userid
            )
        ) {
            return true;
        }

        $instances =
            enrol_get_instances(
                $courseid,
                true
            );

        foreach ($instances as $instance) {

            if ($instance->enrol !== 'manual') {
                continue;
            }

            $plugin =
                enrol_get_plugin(
                    'manual'
                );

            $plugin->enrol_user(
                $instance,
                $userid,
                self::get_student_roleid()
            );

            return true;
        }

        return false;
    }

    public static function get_product_courses(
        int $productid
    ): array {

        $product =
            product_manager::get_product(
                $productid
            );

        if (!$product) {
            return [];
        }

        if ($product->type === 'bundle') {

            $courses = [];

            $products =
                bundle_manager::get_bundle_products(
                    $productid
                );

            foreach ($products as $bundleproduct) {

                $courses = array_merge(
                    $courses,
                    self::get_product_courses(
                        $bundleproduct->id
                    )
                );
            }

            return $courses;
        }

        return course_mapper::get_courses(
            $productid
        );
    }

    public static function sync_user_product(
        int $userid,
        int $productid,
        bool $assigned
    ): bool {

        if ($assigned) {

            return self::enrol_product(
                $productid,
                $userid
            );
        }

        return self::unenrol_product(
            $productid,
            $userid
        );
    }

    private static function get_student_roleid(): int {

        global $DB;

        return (int)$DB->get_field(
            'role',
            'id',
            ['shortname' => 'student'],
            MUST_EXIST
        );
    }

    /**
     * Check whether user already owns/enrolled product.
     *
     * @param int $productid
     * @param int $userid
     * @return bool
     */
    public static function is_product_enrolled(
        int $productid,
        int $userid
    ): bool {

        $product = \local_learningproducts\product_manager
            ::get_product($productid);

        if (!$product) {
            return false;
        }

        // SINGLE PRODUCT.
        if ($product->type === 'single') {

            $courses =
                \local_learningproducts\course_mapper
                    ::get_courses($productid);

            foreach ($courses as $course) {

                $context =
                    \context_course::instance($course->id);

                if (is_enrolled($context, $userid)) {
                    return true;
                }
            }

            return false;
        }

        // BUNDLE PRODUCT.
        $bundleproducts =
            \local_learningproducts\bundle_manager
                ::get_bundle_products($productid);

        foreach ($bundleproducts as $bundleproduct) {

            if (
                self::is_product_enrolled(
                    $bundleproduct->id,
                    $userid
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get learning URL for enrolled product.
     *
     * @param int $productid
     * @return \moodle_url|null
     */
    public static function get_product_learning_url(
        int $productid
    ): ?\moodle_url {

        $product =
            \local_learningproducts\product_manager
                ::get_product($productid);

        if (!$product) {
            return null;
        }

        if ($product->type === 'single') {

            $courses =
                \local_learningproducts\course_mapper
                    ::get_courses($productid);

            if (!empty($courses)) {

                $course = reset($courses);

                return new \moodle_url(
                    '/course/view.php',
                    [
                        'id' => $course->id
                    ]
                );
            }
        }

        return new \moodle_url(
            '/local/learningproducts/view.php',
            [
                'id' => $productid
            ]
        );
    }

    public static function unenrol_product(
        int $productid,
        int $userid
    ): bool {

        $product =
            product_manager::get_product(
                $productid
            );

        if (!$product) {
            return false;
        }

        if ($product->type === 'bundle') {

            $products =
                bundle_manager::get_bundle_products(
                    $productid
                );

            $success = true;

            foreach ($products as $bundleproduct) {

                $result =
                    self::unenrol_product(
                        $bundleproduct->id,
                        $userid
                    );

                if (!$result) {
                    $success = false;
                }
            }

            return $success;
        }

        $courses =
            course_mapper::get_courses(
                $productid
            );

        $success = true;

        foreach ($courses as $course) {

            $result =
                self::unenrol_user_from_course(
                    $userid,
                    $course->id
                );

            if (!$result) {
                $success = false;
            }
        }

        return $success;
    }

    protected static function unenrol_user_from_course(
        int $userid,
        int $courseid
        ): bool {

            $instances = enrol_get_instances(
                $courseid,
                true
            );

            foreach ($instances as $instance) {

                if ($instance->enrol !== 'manual') {
                    continue;
                }

                $plugin = enrol_get_plugin(
                    'manual'
                );

                $plugin->unenrol_user(
                    $instance,
                    $userid
                );

                return true;
            }

            return false;
        }


    /**
     * Generate enrol key.
     *
     * @param int $length
     * @return string
     */
    public static function generate_enrol_key(
        int $length = 10
    ): string {

        return substr(
            strtoupper(bin2hex(random_bytes(16))),
            0,
            $length
        );
    }

    public static function create_self_enrol(
        int $courseid,
        string $password
    ): int {

        global $DB;

        require_once($GLOBALS['CFG']->dirroot . '/enrol/self/lib.php');

        if ($instance = $DB->get_record('enrol', [
            'courseid' => $courseid,
            'enrol' => 'self'
        ])) {

            $instance->status = ENROL_INSTANCE_ENABLED;
            $instance->password = $password;
            $instance->customint3 = 1;

            $DB->update_record('enrol', $instance);

            return $instance->id;
        }

        $plugin = enrol_get_plugin('self');

        return $plugin->add_instance(
            get_course($courseid),
            [
                'status' => ENROL_INSTANCE_ENABLED,
                'password' => $password,
                'customint3' => 1,
            ]
        );
    }

    /**
     * Enrol user manually.
     *
     * @param int $userid
     * @param int $courseid
     */
    public static function enrol_manual(
        int $userid,
        int $courseid
    ): void {

        $instances = enrol_get_instances(
            $courseid,
            true
        );

        foreach ($instances as $instance) {

            if ($instance->enrol !== 'manual') {
                continue;
            }

            enrol_get_plugin('manual')->enrol_user(
                $instance,
                $userid,
                self::get_student_roleid()
            );

            return;
        }
    }
}