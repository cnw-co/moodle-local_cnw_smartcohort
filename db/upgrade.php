<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Smart cohort
 *
 * @package     local_cnw_smartcohort
 * @copyright   CNW Rendszerintegrációs Zrt. <moodle@cnw.hu>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/upgradelib.php');

/**
 * Execute local_cnw_smartcohort upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_cnw_smartcohort_upgrade($oldversion)
{
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2019022203) {

        $table = new xmldb_table('cnw_sc_filters');

        $field = new xmldb_field('initialized', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Conditionally launch add field questioncategoryid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('cnw_sc_queue');

        $field1 = new xmldb_field('id');
        $field1->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $field2 = new xmldb_field('user_id');
        $field2->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null);

        $key1 = new xmldb_key('primary');
        $key1->set_attributes(XMLDB_KEY_PRIMARY, array('id'), null, null);

        $index1 = new xmldb_index('user');
        $index1->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('user_id'));

        $table->addField($field1);
        $table->addField($field2);
        $table->addKey($key1);
        $table->addIndex($index1);

        $dbman->create_table($table);

        upgrade_plugin_savepoint(true, 2019022203, 'local', 'cnw_smartcohort');

    }

    if ($oldversion < 2019022205) {

        $table = new xmldb_table('cnw_sc_filters');

        $field = new xmldb_field('deleted_flag', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Conditionally launch add field questioncategoryid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2019022205, 'local', 'cnw_smartcohort');

    }

    // For further information please read the Upgrade API documentation:
    // https://docs.moodle.org/dev/Upgrade_API
    //
    // You will also have to create the db/install.xml file by using the XMLDB Editor.
    // Documentation for the XMLDB Editor can be found at:
    // https://docs.moodle.org/dev/XMLDB_editor

    return true;
}
