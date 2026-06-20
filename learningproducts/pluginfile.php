<?php

defined('MOODLE_INTERNAL') || die();

function local_learningproducts_pluginfile(
    $course,
    $cm,
    $context,
    $filearea,
    $args,
    $forcedownload,
    array $options = []
) {

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    if ($filearea !== 'productimage') {
        return false;
    }

    $itemid = array_shift($args);

    $filepath = '/';

    $filename = array_pop($args);

    if (!$args) {
        $args = [];
    }

    $fs = get_file_storage();

    $file = $fs->get_file(
        $context->id,
        'local_learningproducts',
        $filearea,
        $itemid,
        $filepath,
        $filename
    );

    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file);
}