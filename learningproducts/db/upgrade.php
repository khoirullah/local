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

    /**
     * Version:
     * 2026060804 
     *
     * Add product pricing table and
     * purchase pricing snapshot fields.
     */
    if ($oldversion < 2026060804) {

        /*
        * Create pricing table.
        */
        $table = new xmldb_table('local_lp_product_pricing');

        if (!$dbman->table_exists($table)) {

            $table->add_field(
                'id',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                XMLDB_SEQUENCE
            );

            $table->add_field(
                'productid',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL
            );

            $table->add_field(
                'name',
                XMLDB_TYPE_CHAR,
                '100',
                null,
                XMLDB_NOTNULL
            );

            $table->add_field(
                'durationmonths',
                XMLDB_TYPE_INTEGER,
                '3',
                null,
                XMLDB_NOTNULL,
                null,
                1
            );

            $table->add_field(
                'price',
                XMLDB_TYPE_NUMBER,
                '12,2',
                null,
                XMLDB_NOTNULL,
                null,
                0
            );

            $table->add_field(
                'discountpercent',
                XMLDB_TYPE_NUMBER,
                '5,2',
                null,
                XMLDB_NOTNULL,
                null,
                0
            );

            $table->add_field(
                'visible',
                XMLDB_TYPE_INTEGER,
                '1',
                null,
                XMLDB_NOTNULL,
                null,
                1
            );

            $table->add_field(
                'sortorder',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                0
            );

            $table->add_field(
                'timecreated',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL
            );

            $table->add_field(
                'timemodified',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL
            );

            $table->add_key(
                'primary',
                XMLDB_KEY_PRIMARY,
                ['id']
            );

            $table->add_key(
                'product_fk',
                XMLDB_KEY_FOREIGN,
                ['productid'],
                'local_lp_products',
                ['id']
            );

            $table->add_index(
                'product_duration_uix',
                XMLDB_INDEX_UNIQUE,
                ['productid', 'durationmonths']
            );

            $table->add_index(
                'product_visible_idx',
                XMLDB_INDEX_NOTUNIQUE,
                ['productid', 'visible']
            );

            $dbman->create_table($table);
        }

        /*
        * Add purchase snapshot fields.
        */
        $table = new xmldb_table('local_lp_purchase');

        $fields = [

            new xmldb_field(
                'pricingid',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                null,
                null,
                null,
                'productid'
            ),

            new xmldb_field(
                'durationmonths',
                XMLDB_TYPE_INTEGER,
                '3',
                null,
                XMLDB_NOTNULL,
                null,
                1,
                'pricingid'
            ),

            new xmldb_field(
                'unitprice',
                XMLDB_TYPE_NUMBER,
                '12,2',
                null,
                XMLDB_NOTNULL,
                null,
                0,
                'durationmonths'
            ),

            new xmldb_field(
                'discountpercent',
                XMLDB_TYPE_NUMBER,
                '5,2',
                null,
                XMLDB_NOTNULL,
                null,
                0,
                'unitprice'
            ),

            new xmldb_field(
                'totalprice',
                XMLDB_TYPE_NUMBER,
                '12,2',
                null,
                XMLDB_NOTNULL,
                null,
                0,
                'discountpercent'
            )

        ];

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_plugin_savepoint(
            true,
            2026060804,
            'local',
            'learningproducts'
        );
    }

    if ($oldversion < 2026060805) {

        // Add priceperuser field to products.
        $table = new xmldb_table('local_lp_products');

        $field = new xmldb_field(
            'priceperuser',
            XMLDB_TYPE_NUMBER,
            '10',
            '2',
            XMLDB_NOTNULL,
            null,
            '0',
            'price'
        );

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(
            true,
            2026060805,
            'local',
            'learningproducts'
        );
    }

    if ($oldversion < 2026060806) {

        // Create local_company_provision table.
        $table = new xmldb_table('local_company_provision');

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
                'purchaseid',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_field(
                'companyid',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_field(
                'productid',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_field(
                'templatecourseid',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_field(
                'courseid',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                null,
                null,
                '0'
            );

            $table->add_field(
                'subscriptionid',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                null,
                null,
                '0'
            );

            $table->add_field(
                'quota',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '1'
            );

            $table->add_field(
                'startdate',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_field(
                'enddate',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_field(
                'status',
                XMLDB_TYPE_CHAR,
                '20',
                null,
                XMLDB_NOTNULL,
                null,
                'pending'
            );

            $table->add_field(
                'requestedby',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_field(
                'processedby',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                null,
                null,
                '0'
            );

            $table->add_field(
                'notes',
                XMLDB_TYPE_TEXT,
                null,
                null,
                null,
                null,
                null
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

            $table->add_field(
                'timemodified',
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

            $table->add_index(
                'purchaseid',
                XMLDB_INDEX_NOTUNIQUE,
                ['purchaseid']
            );

            $table->add_index(
                'companyid',
                XMLDB_INDEX_NOTUNIQUE,
                ['companyid']
            );

            $table->add_index(
                'productid',
                XMLDB_INDEX_NOTUNIQUE,
                ['productid']
            );

            $table->add_index(
                'templatecourseid',
                XMLDB_INDEX_NOTUNIQUE,
                ['templatecourseid']
            );

            $table->add_index(
                'status',
                XMLDB_INDEX_NOTUNIQUE,
                ['status']
            );

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(
            true,
            2026060806,
            'local',
            'learningproducts'
        );
    }
    
    return true;
}