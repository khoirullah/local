<?php
/**
 * Global library callbacks.
 *
 * File ini digunakan untuk hook Moodle:
 * - navigation
 * - extend settings
 * - inject assets
 *
 * @package    local_learningproducts
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Serve plugin files.
 */
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

    $filename = array_pop($args);

    $filepath = '/';

    if ($args) {
        $filepath .= implode('/', $args) . '/';
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
        return false;
    }

    send_stored_file(
        $file,
        86400,
        0,
        $forcedownload,
        $options
    );
}