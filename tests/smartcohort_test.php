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

namespace local_cnw_smartcohort;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->dirroot/cohort/lib.php");
require_once($CFG->dirroot . '/lib/authlib.php');
require_once(__DIR__ . "/../lib.php");
require_once("$CFG->dirroot/user/lib.php");

use advanced_testcase;

/**
 * Class smartcohort_test
 *
 * @package     local_cnw_smartcohort
 * @copyright   CNW Rendszerintegrációs Zrt. <moodle@cnw.hu>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class smartcohort_test extends advanced_testcase {


    /**
     * Helper function for array is similar check
     *
     * @param array $a
     * @param array $b
     * @return bool
     */
    public function arrays_are_similar($a, $b) {
        // If the indexes don't match, return immediately.
        if (count(array_diff_assoc($a, $b))) {
            return false;
        }
        // We know that the indexes, but maybe not values, match.
        // Compare the values between the two arrays.
        foreach ($a as $k => $v) {
            if ($v !== $b[$k]) {
                return false;
            }
        }
        // We have identical indexes, and no unequal values.
        return true;
    }

    /**
     * Helper for create new rule for lastname default.
     *
     * @param string $name
     * @param int $cohortid
     * @param string $getfield
     * @return array
     */
    private function create_rule($name = null, $cohortid = null, $getfield = 'lastname') {
        global $DB;

        if (is_null($cohortid)) {
            $cohort = $this->getDataGenerator()->create_cohort();
        }

        $rule = [];
        $rule['name'] = (is_null($name)) ? 'CNW Co.' : $name;
        $rule['cohort_id'] = (is_null($cohortid)) ? $cohort->id : $cohortid;

        $filter = [];
        $filter[$getfield][0]['operator'] = 0;
        $filter[$getfield][0]['value'] = (is_null($name)) ? 'CNW Co.' : $name;

        $rule['rule_id'] = smartcohort_save($rule, $filter);

        $ruleobj = new stdClass();
        $ruleobj->id = $rule['rule_id'];
        $ruleobj->name = $rule['name'];
        $ruleobj->cohort_id = $rule['cohort_id'];

        return [
            'rule' => $ruleobj,
            'filter' => $filter
        ];

    }

    /**
     * Cohort lib has all function test.
     *
     * @return void
     */
    public function testcohortlibhasallfunction() {
        global $DB;
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();
        $user = $this->getDataGenerator()->create_user();

        $this->assertFalse(cohort_is_member($cohort->id, $user->id));
        cohort_add_member($cohort->id, $user->id);
        $this->assertTrue(cohort_is_member($cohort->id, $user->id));
        cohort_remove_member($cohort->id, $user->id);
        $this->assertFalse(cohort_is_member($cohort->id, $user->id));
    }

    /**
     * Cohort table schema test.
     *
     * @return void
     */
    public function testcohorttableschema() {
        global $DB;
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();
        $user = $this->getDataGenerator()->create_user();

        cohort_add_member($cohort->id, $user->id);

        $table = $DB->get_record('cohort_members', ['userid' => $user->id]);

        $this->assertTrue(property_exists($table, 'userid'));
        $this->assertTrue(property_exists($table, 'cohortid'));
    }

    /**
     * Get rules test.
     *
     * @return void
     */
    public function test_smartcohort_get_rules() {
        global $DB;

        $empty = $DB->get_records('cnw_sc_rule');
        $libget = smartcohort_get_rules();

        $this->assertTrue($this->arrays_are_similar($empty, $libget));

    }

    /**
     * Save rule test.
     *
     * @return void
     */
    public function test_smartcohort_store_rule() {
        global $DB;
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();

        $rule = [];
        $rule['name'] = 'CNW Co.';
        $rule['cohort_id'] = $cohort->id;

        $filter = [];
        $filter['lastname'][0]['operator'] = 0;
        $filter['lastname'][0]['value'] = 'CNW Co.';

        $this->assertEquals(count($DB->get_records('cnw_sc_rule')), 0);
        smartcohort_save($rule, $filter);
        $this->assertEquals(count($DB->get_records('cnw_sc_rule')), 1);

    }

    /**
     * Update rule test.
     *
     * @return void
     */
    public function test_smartcohort_update_rule() {
        global $DB;
        $this->resetAfterTest();

        $rule = $this->create_rule();

        $this->assertEquals($rule['filter']['lastname'][0]['value'], 'CNW Co.');

        $rule['filter']['lastname'][0]['value'] = 'CNW Co. ' . date('Y');

        $rulearr['rule_id'] = $rule['rule']->id;
        $rulearr['name'] = $rule['rule']->name;
        $rulearr['cohort_id'] = $rule['rule']->cohort_id;

        smartcohort_update($rulearr, $rule['filter']);

        $search = $DB->get_record('cnw_sc_filter',
                                  ['rule_id' => $rule['rule']->id,
                                  'operator' => 0,
                                  'field' => 'lastname',
                                  'value' => 'CNW Co. ' . date('Y')]);

        $this->assertEquals($search->operator, 0);
        $this->assertEquals($search->value, 'CNW Co. ' . date('Y'));

    }

    /**
     * Test if user create event triggers smart cohort insertion if the
     * user does not meet any filter criteria.
     *
     * @return void
     */
    public function test_smartcohort_check_user_create_event_with_no_filters() {
        global $DB;
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();

        $usertoadd = new stdClass();
        $usertoadd->username = 'cnw';
        $usertoadd->email = 'moodle@cnw.hu';
        $usertoadd->firstname = 'CNW';
        $usertoadd->lastname = 'CNW Zrt.';

        $user = user_create_user($usertoadd);

        $this->assertFalse(cohort_is_member($cohort->id, $user));
        $rule = $this->create_rule('CNW Co.', $cohort->id);
        $this->assertFalse(cohort_is_member($cohort->id, $user));

        $query = $DB->count_records('cnw_sc_user_cohort',
                                    ['user_id' => $user,
                                    'cohort_id' => $cohort->id,
                                    'rule_id' => $rule['rule']->id]);
        $this->assertEquals($query, 0);
    }

    /**
     * Test if user create event triggers smart cohort insertion if the
     * user meets any filter criteria.
     *
     * @return void
     */
    public function test_smartcohort_check_user_create_event_with_filters() {
        global $DB;
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();

        $this->create_rule('CNW', $cohort->id);

        $usertoadd = new stdClass();
        $usertoadd->username = 'cnw';
        $usertoadd->email = 'moodle@cnw.hu';
        $usertoadd->firstname = 'CNW';
        $usertoadd->lastname = 'CNW';

        $user = user_create_user($usertoadd);

        $search = $DB->count_records('cnw_sc_queue', ['user_id' => $user]);

        $this->assertTrue(($search != 0));
    }

    /**
     * Test smart cohort insertions based on rules for all users.
     *
     * @return void
     */
    public function test_smartcohort_run_rules_for_all_users() {
        global $DB;
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();
        $user = $this->getDataGenerator()->create_user(array('lastname' => 'CNW Co.'));
        $user2 = $this->getDataGenerator()->create_user();
        $rule = $this->create_rule('CNW Co.', $cohort->id);

        $this->assertFalse(cohort_is_member($cohort->id, $user->id));
        $query = $DB->count_records('cnw_sc_user_cohort',
                                    ['user_id' => $user->id,
                                    'cohort_id' => $cohort->id,
                                    'rule_id' => $rule['rule']->id]);
        $this->assertEquals($query, 0);

        $this->assertFalse(cohort_is_member($cohort->id, $user2->id));
        $query = $DB->count_records('cnw_sc_user_cohort',
                                    ['user_id' => $user2->id,
                                    'cohort_id' => $cohort->id,
                                    'rule_id' => $rule['rule']->id]);
        $this->assertEquals($query, 0);

        smartcohort_run_rules();

        $this->assertTrue(cohort_is_member($cohort->id, $user->id));
        $query = $DB->count_records('cnw_sc_user_cohort',
                                    ['user_id' => $user->id,
                                    'cohort_id' => $cohort->id,
                                    'rule_id' => $rule['rule']->id]);
        $this->assertEquals($query, 1);

        $this->assertFalse(cohort_is_member($cohort->id, $user2->id));
        $query = $DB->count_records('cnw_sc_user_cohort',
                                    ['user_id' => $user2->id,
                                    'cohort_id' => $cohort->id,
                                    'rule_id' => $rule['rule']->id]);
        $this->assertEquals($query, 0);

    }

    /**
     * Test smart cohort insertions based on rules for one user.
     *
     * @return void
     */
    public function test_smartcohort_run_rules_for_one_user() {
        global $DB;
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();
        $user = $this->getDataGenerator()->create_user(array('lastname' => 'CNW Co.'));
        $user2 = $this->getDataGenerator()->create_user();
        $rule = $this->create_rule('CNW Co.', $cohort->id);

        $this->assertFalse(cohort_is_member($cohort->id, $user->id));
        $query = $DB->count_records('cnw_sc_user_cohort',
                                    ['user_id' => $user->id,
                                    'cohort_id' => $cohort->id,
                                    'rule_id' => $rule['rule']->id]);
        $this->assertEquals($query, 0);

        $this->assertFalse(cohort_is_member($cohort->id, $user2->id));
        $query = $DB->count_records('cnw_sc_user_cohort',
                                    ['user_id' => $user2->id,
                                    'cohort_id' => $cohort->id,
                                    'rule_id' => $rule['rule']->id]);
        $this->assertEquals($query, 0);

        smartcohort_run_rules($user->id);

        $this->assertTrue(cohort_is_member($cohort->id, $user->id));
        $query = $DB->count_records('cnw_sc_user_cohort',
                                    ['user_id' => $user->id,
                                    'cohort_id' => $cohort->id,
                                    'rule_id' => $rule['rule']->id]);
        $this->assertEquals($query, 1);

        $this->assertFalse(cohort_is_member($cohort->id, $user2->id));
        $query = $DB->count_records('cnw_sc_user_cohort',
                                    ['user_id' => $user2->id,
                                    'cohort_id' => $cohort->id,
                                    'rule_id' => $rule['rule']->id]);
        $this->assertEquals($query, 0);

    }

    /**
     * Test if smart cohort rule deletion removes users from cohorts.
     *
     * @return void
     */
    public function test_smartcohort_delete_rule_with_undo() {
        global $DB;
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();
        $user = $this->getDataGenerator()->create_user(array('lastname' => 'CNW Co.'));
        $user2 = $this->getDataGenerator()->create_user(array('firstname' => 'CNW Co.'));
        $rule = $this->create_rule('CNW Co.', $cohort->id);
        $rule2 = $this->create_rule('CNW Co.', $cohort->id, 'firstname');
        smartcohort_run_rules();

        $this->assertTrue(cohort_is_member($cohort->id, $user->id));
        $this->assertTrue(cohort_is_member($cohort->id, $user2->id));

        smartcohort_delete_rule($rule['rule'], 1);

        $this->assertFalse(cohort_is_member($cohort->id, $user->id));
        $query = $DB->count_records('cnw_sc_user_cohort',
                                    ['user_id' => $user->id,
                                    'cohort_id' => $cohort->id,
                                    'rule_id' => $rule['rule']->id]);
        $this->assertEquals($query, 0);

        $this->assertTrue(cohort_is_member($cohort->id, $user2->id));
        $query = $DB->count_records('cnw_sc_user_cohort',
                                    ['user_id' => $user2->id,
                                    'cohort_id' => $cohort->id,
                                    'rule_id' => $rule2['rule']->id]);
        $this->assertEquals($query, 1);

    }

    /**
     * Test if smart cohort rule deletion keeps users in cohorts.
     *
     * @return void
     */
    public function test_smartcohort_delete_with_keep() {
        global $DB;
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();
        $user = $this->getDataGenerator()->create_user(array('lastname' => 'CNW Co.'));
        $user2 = $this->getDataGenerator()->create_user(array('firstname' => 'CNW Co.'));
        $rule = $this->create_rule('CNW Co.', $cohort->id);
        $rule2 = $this->create_rule('CNW Co.', $cohort->id, 'firstname');
        smartcohort_run_rules();

        $this->assertTrue(cohort_is_member($cohort->id, $user->id));
        $this->assertTrue(cohort_is_member($cohort->id, $user2->id));

        smartcohort_delete_rule($rule['rule'], 0);

        $this->assertTrue(cohort_is_member($cohort->id, $user->id));
        $query = $DB->count_records('cnw_sc_user_cohort',
                                    ['user_id' => $user->id,
                                    'cohort_id' => $cohort->id,
                                    'rule_id' => $rule['rule']->id]);
        $this->assertEquals($query, 0);

        $this->assertTrue(cohort_is_member($cohort->id, $user2->id));
        $query = $DB->count_records('cnw_sc_user_cohort',
                                    ['user_id' => $user2->id,
                                    'cohort_id' => $cohort->id,
                                    'rule_id' => $rule2['rule']->id]);
        $this->assertEquals($query, 1);
    }


}
