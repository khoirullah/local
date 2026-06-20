<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_company_upgrade($oldversion) {

    if ($oldversion < 2026061706) {

        upgrade_plugin_savepoint(
            true,
            2026061706,
            'local',
            'company'
        );
    }

    return true;
}