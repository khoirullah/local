<?php
defined('MOODLE_INTERNAL') || die();

function local_company_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    if ($filearea !== 'logo') {
        return false;
    }

    require_login();

    $itemid = array_shift($args); // company id
    $filename = array_pop($args);
    $filepath = '/';

    $fs = get_file_storage();

    $file = $fs->get_file(
        $context->id,
        'local_company',
        'logo',
        $itemid,
        $filepath,
        $filename
    );

    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}