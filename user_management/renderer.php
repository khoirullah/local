<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

class local_user_management_renderer extends plugin_renderer_base {

    /**
     * Render main menu using mustache template.
     *
     * @return string
     */
    public function render_main_menu(): string {

        $cards = [
            [
                'title'       => get_string('menu_create_user', 'local_user_management'),
                'description' => get_string('menu_create_user_desc', 'local_user_management'),
                'icon'        => 'fa-user',
                'iconcolor'   => 'text-info',
                'url'         => (new moodle_url('/local/user_management/pages/create_user.php'))->out(),
                'class'       => 'danger',
                'warning'     => false,
            ],
            [
                'title'       => get_string('menu_suspend_user', 'local_user_management'),
                'description' => get_string('menu_suspend_user_desc', 'local_user_management'),
                'icon'        => 'fa-ban',
                'iconcolor'   => 'text-danger',
                'url'         => (new moodle_url('/local/user_management/pages/suspend_user.php'))->out(),
                'class'       => 'danger',
                'warning'     => true,
                'warningtext' => get_string('suspend_warning', 'local_user_management'),
            ],
            [
                'title'       => get_string('menu_bulk_upload', 'local_user_management'),
                'description' => get_string('menu_bulk_upload_desc', 'local_user_management'),
                'icon'        => 'fa-upload',
                'iconcolor'   => 'text-warning',
                'url'         => (new moodle_url('/local/user_management/pages/bulk_upload.php'))->out(),
                'class'       => 'danger',
                'warning'     => false,
            ],
            [
                'title'       => get_string('menu_certificate_verify', 'local_user_management'),
                'description' => get_string('menu_certificate_verify_desc', 'local_user_management'),
                'icon'        => 'fa-graduation-cap',
                'iconcolor'   => 'text-dark',
                'url'         => (new moodle_url('/local/user_management/pages/certificate_verify.php'))->out(),
                'class'       => 'danger',
                'warning'     => false,
            ],
        ];


        $data = [
            'cards'    => $cards,
            'openbtn' => get_string('open_menu', 'local_user_management'),
        ];

        return $this->render_from_template(
            'local_user_management/main_menu',
            $data
        );
    }
}
