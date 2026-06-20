<?php

defined('MOODLE_INTERNAL') || die();

error_log('✅ local_auth_pluginfile() CALLED');

/**
 * Inject JS ke halaman setting plugin ini aja.
 */
function local_auth_before_http_headers() {
    global $SCRIPT, $PAGE, $DB;

    // Pastikan di halaman settings plugin ini aja.
    if ($SCRIPT === '/admin/settings.php' && optional_param('section', '', PARAM_ALPHANUMEXT) === 'local_auth_settings') {

        $disabled = [];

        $records = $DB->get_records('oauth2_issuer', ['enabled' => 1]);
        foreach ($records as $issuer) {
            if (empty($issuer->systemaccount)) {
                $disabled[] = $issuer->id;
            }
        }

        $disabledjson = json_encode($disabled);
        $msg = get_string('noconnectedaccount', 'local_auth');

        $PAGE->requires->js_init_code("
            document.addEventListener('DOMContentLoaded', function () {
                var disabled = $disabledjson;
                disabled.forEach(function (id) {
                    var input = document.querySelector('input[name=\"s_local_auth_loginby[' + id + ']\"]');
                    if (input) {
                        input.disabled = true;
                        input.closest('label').style.opacity = '0.6';
                        input.closest('label').title = '$msg';
                    }
                });
            });
        ");
    }
}

function local_auth_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    $fs = get_file_storage();

    $filename = array_pop($args);
    $filepath = '/' . implode('/', $args) . '/';

    // DEBUGGING
    error_log("PLUGINFILE DEBUG: contextid=$context->id filearea=$filearea itemid=0 filepath=$filepath filename=$filename");

    $file = $fs->get_file($context->id, 'local_auth', $filearea, 0, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        error_log("PLUGINFILE NOT FOUND");
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}


