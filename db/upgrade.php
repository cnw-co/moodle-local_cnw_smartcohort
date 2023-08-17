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
require_once($CFG->dirroot.'/user/profile/lib.php');

/**
 * Execute local_cnw_smartcohort upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_cnw_smartcohort_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2019022203) {

        $table = new xmldb_table('cnw_sc_filters');

        $field = new xmldb_field('initialized', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Conditionally launch add field initialized.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('cnw_sc_queue');

        $field1 = new xmldb_field('id');
        $field1->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $field2 = new xmldb_field('user_id');
        $field2->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null);

        $key1 = new xmldb_key('primary');
        $key1->set_attributes(XMLDB_KEY_PRIMARY, ['id'], null, null);

        $index1 = new xmldb_index('user');
        $index1->set_attributes(XMLDB_INDEX_NOTUNIQUE, ['user_id']);

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

        // Conditionally launch add field deleted_flag.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2019022205, 'local', 'cnw_smartcohort');

    }

    if ($oldversion < 2023070403) {

        // Get old filters (SmartCohort 1.0 filters).
        $oldfilters = $DB->get_records('cnw_sc_rules');

        // Get custom profile field names and ids for filter conversion to SmartCohort 2.0).
        $cfs = profile_get_custom_fields();
        $customfields = [];
        foreach ($cfs as $cf) {
            $customfields[$cf->shortname] = $cf->id;
        }

        // Convert old filters to new filters.
        foreach ($oldfilters as $oldfilter) {
            $oldfilter->rule_id = $oldfilter->filter_id;
            unset($oldfilter->filter_id);

            if ($oldfilter->operator == 'contains') {
                $oldfilter->operator = 0;
            }
            if ($oldfilter->operator == 'not contains') {
                $oldfilter->operator = 1;
            }
            if ($oldfilter->operator == 'equals') {
                $oldfilter->operator = 2;
            }
            if ($oldfilter->operator == 'not equals') {
                $oldfilter->operator = 1;
            }
            if ($oldfilter->operator == 'start with') {
                $oldfilter->operator = 3;
            }
            if ($oldfilter->operator == 'end with') {
                $oldfilter->operator = 4;
            }

            if ($oldfilter->is_custom_field == 0) {
                $oldfilter->profile = -1;
            }
            if ($oldfilter->is_custom_field == 1) {
                $oldfilter->profile = $customfields[$oldfilter->field];
                $oldfilter->field = 'profile';
            }
            unset($oldfilter->is_custom_field);
            unset($oldfilter->logicaloperator);
        }

        // Truncate old filters from DB because we change field properties.
        $DB->delete_records('cnw_sc_rules');

        // MODIFY TABLE CNW_SC_FILTERS.
        $table = new xmldb_table('cnw_sc_filters');

        if ($dbman->table_exists($table)) {
            // Rename table cnw_sc_filters to cnw_sc_rules.
            $dbman->rename_table($table, 'cnw_sc_rule');
        }

        // MODIFY TABLE CNW_SC_RULES.
        $table = new xmldb_table('cnw_sc_rules');

        $field = new xmldb_field('is_custom_field', XMLDB_TYPE_INTEGER, '10', null, false, null, '-1', 'field');
        if ($dbman->field_exists($table, $field)) {
            // Launch change of precision for field profile.
            $dbman->change_field_precision($table, $field);
            // Launch change of default for field profile.
            $dbman->change_field_default($table, $field);
            // Rename field is_custom_field to profile.
            $dbman->rename_field($table, $field, 'profile');
        }

        // Drop index filter.
        $index = new xmldb_index('filter', XMLDB_INDEX_NOTUNIQUE, ['filter_id']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        $field = new xmldb_field('operator', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'profile');
        if ($dbman->field_exists($table, $field)) {
            // Launch change of nullability for field operator.
            $dbman->change_field_notnull($table, $field);

            // Launch change of type for field operator.
            $dbman->change_field_type($table, $field);

            // Launch change of precision for field operator.
            $dbman->change_field_precision($table, $field);
        }

        // Rename field filter_id to rule_id.
        $field = new xmldb_field('filter_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'rule_id');
        }

        // Drop field logicaloperator.
        $field = new xmldb_field('logicaloperator', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table,  $field);
        }

        // Add index rule.
        $index = new xmldb_index('rule', XMLDB_INDEX_NOTUNIQUE, ['rule_id']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Rename table cnw_sc_filters to cnw_sc_rule.
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'cnw_sc_filter');
        }

        // MODIFY TABLE CNW_SC_USER_COHORT.
        $table = new xmldb_table('cnw_sc_user_cohort');

        // Drop index filter.
        $index = new xmldb_index('filter', XMLDB_INDEX_NOTUNIQUE, ['filter_id']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Rename field filter_id to rule_id.
        $field = new xmldb_field('filter_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
        $dbman->rename_field($table, $field, 'rule_id');

        // Add index rule.
        $index = new xmldb_index('rule', XMLDB_INDEX_NOTUNIQUE, ['rule_id']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Insert converted filters into table cnw_sc_filter.
        foreach ($oldfilters as $newfilter) {
            $DB->insert_record('cnw_sc_filter', $newfilter);
        }

        // Define table cnw_sc_tmp to be created.
        $table = new xmldb_table('cnw_sc_tmp');

        // Adding fields to table cnw_sc_tmp.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('rule_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cohort_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('field', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('operator', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('value', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('profile', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '-1');

        // Adding keys to table cnw_sc_tmp.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for cnw_sc_tmp.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2023070403, 'local', 'cnw_smartcohort');
    }
    return true;
}
