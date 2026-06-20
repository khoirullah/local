<?php
/**
 * Upgrade script.
 *
 * Handle database upgrades
 * without reinstalling plugin.
 *
 * @package local_learningproducts
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute plugin upgrades.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_learningproducts_upgrade($oldversion): bool {

    global $DB;

    $dbman = $DB->get_manager();

    /**
     * Version:
     * 2026051201
     *
     * Add category support.
     */
    if ($oldversion < 2026051201) {

        /**
         * Create categories table.
         */
        $table = new xmldb_table('local_lp_categories');

        if (!$dbman->table_exists($table)) {

            $table->add_field(
                'id',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                XMLDB_SEQUENCE,
                null
            );

            $table->add_field(
                'name',
                XMLDB_TYPE_CHAR,
                '255',
                null,
                XMLDB_NOTNULL,
                null,
                null
            );

            $table->add_field(
                'description',
                XMLDB_TYPE_TEXT,
                null,
                null,
                null,
                null,
                null
            );

            $table->add_field(
                'sortorder',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_field(
                'visible',
                XMLDB_TYPE_INTEGER,
                '1',
                null,
                XMLDB_NOTNULL,
                null,
                '1'
            );

            $table->add_field(
                'timecreated',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_key(
                'primary',
                XMLDB_KEY_PRIMARY,
                ['id']
            );

            $dbman->create_table($table);
        }

        /**
         * Add categoryid field.
         */
        $table = new xmldb_table('local_lp_products');

        $field = new xmldb_field(
            'categoryid',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            null,
            null,
            null
        );

        if (!$dbman->field_exists($table, $field)) {

            $dbman->add_field(
                $table,
                $field
            );
        }

        /**
         * Save upgrade point.
         */
        upgrade_plugin_savepoint(
            true,
            2026051201,
            'local',
            'learningproducts'
        );
    }

    return true;
}