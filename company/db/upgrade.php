<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_company_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026061707) {

        // Add durationmonths to subscriptions.
        $table = new xmldb_table('local_company_subscription');

        $field = new xmldb_field(
            'durationmonths',
            XMLDB_TYPE_INTEGER,
            '3',
            null,
            XMLDB_NOTNULL,
            null,
            1,
            'quota'
        );

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(
            true,
            2026061707,
            'local',
            'company'
        );
    }

    return true;
}