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

global $CFG;
require_once("$CFG->dirroot/cohort/lib.php");
require_once($CFG->dirroot . '/lib/authlib.php');
require_once(__DIR__ . "/../lib.php");
require_once("$CFG->dirroot/user/lib.php");

class smartcohort_test extends advanced_testcase
{

    /**
     * Helper function for array is similar check
     *
     * @param $a
     * @param $b
     * @return bool
     */
    function arrays_are_similar($a, $b)
    {
        // if the indexes don't match, return immediately
        if (count(array_diff_assoc($a, $b))) {
            return false;
        }
        // we know that the indexes, but maybe not values, match.
        // compare the values between the two arrays
        foreach ($a as $k => $v) {
            if ($v !== $b[$k]) {
                return false;
            }
        }
        // we have identical indexes, and no unequal values
        return true;
    }

    /**
     * Helper for create new filter for lastname default
     *
     * @param null $name
     * @param null $cohortid
     * @return array
     */
    private function create_filter($name = null, $cohortid = null, $getField = 'lastname')
    {
        global $DB;

        if (is_null($cohortid)) {
            $cohort = $this->getDataGenerator()->create_cohort();
        }

        $filter = new stdClass();
        $filter->name = (is_null($name)) ? 'CNW Co.' : $name;
        $filter->cohort_id = (is_null($cohortid)) ? $cohort->id : $cohortid;

        $auth = new auth_plugin_base();
        $customfields = $auth->get_custom_user_profile_fields();
        $userfields = array_merge($auth->userfields, $customfields);
        foreach ($userfields as $field) {
            $operator = 'userfield_' . $field . '_operator';
            $value = 'userfield_' . $field . '_value';
            if ($field != $getField) {
                $filter->$operator = '';
                $filter->$value = '';
            } else {
                $filter->$operator = 'equals';
                $filter->$value = (is_null($name)) ? 'CNW Co.' : $name;
            }
        }

        $filterid = smartcohort_store_filter($filter);

        //var_dump($filterid);

        return array(
            'filter' => $filter,
            'filterid' => $filterid
        );

    }

    public function testCohortLibHasAllFunction()
    {
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

    public function testCohortTableSchema()
    {
        global $DB;
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();
        $user = $this->getDataGenerator()->create_user();

        cohort_add_member($cohort->id, $user->id);

        $table = $DB->get_record('cohort_members', ['userid' => $user->id]);

        $this->assertTrue(property_exists($table, 'userid'));
        $this->assertTrue(property_exists($table, 'cohortid'));
    }

    public function test_smartcohort_get_filters()
    {
        global $DB;

        $empty = $DB->get_records('cnw_sc_filters');
        $lib_get = smartcohort_get_filters();

        $this->assertTrue($this->arrays_are_similar($empty, $lib_get));

    }

    public function test_smartcohort_store_filter()
    {
        global $DB;
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();

        $filter = new stdClass();
        $filter->name = 'CNW Co.';
        $filter->cohort_id = $cohort->id;

        $auth = new auth_plugin_base();
        $customfields = $auth->get_custom_user_profile_fields();
        $userfields = array_merge($auth->userfields, $customfields);
        foreach ($userfields as $field) {
            $operator = 'userfield_' . $field . '_operator';
            $value = 'userfield_' . $field . '_value';
            if ($field != 'lastname') {
                $filter->$operator = '';
                $filter->$value = '';
            } else {
                $filter->$operator = 'equals';
                $filter->$value = 'CNW Co.';
            }
        }


        $this->assertEquals(count($DB->get_records('cnw_sc_filters')), 0);
        smartcohort_store_filter($filter);
        $this->assertEquals(count($DB->get_records('cnw_sc_filters')), 1);

    }

    public function test_smartcohort_update_filter()
    {
        global $DB;
        $this->resetAfterTest();

        $filter = $this->create_filter();

        $this->assertEquals($filter['filter']->userfield_lastname_value, 'CNW Co.');

        $filter['filter']->id = $filter['filterid'];
        $filter['filter']->userfield_lastname_value = 'CNW Co. ' . date('Y');

        smartcohort_update_filter($filter['filter']);

        $search = $DB->get_record('cnw_sc_rules', ['filter_id' => $filter['filterid'], 'operator' => 'equals', 'field' => 'lastname', 'value' => 'CNW Co. ' . date('Y')]);

        $this->assertEquals($search->operator, 'equals');
        $this->assertEquals($search->value, 'CNW Co. ' . date('Y'));

    }

    public function test_smartcohort_check_user_create_event_with_no_rules()
    {
        global $DB;
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();

        $usertoAdd = new stdClass();
        $usertoAdd->username = 'cnw';
        $usertoAdd->email = 'moodle@cnw.hu';
        $usertoAdd->firstname = 'CNW';
        $usertoAdd->lastname = 'CNW Zrt.';

        $user = user_create_user($usertoAdd);


        $this->assertFalse(cohort_is_member($cohort->id, $user));
        $filter = $filter = $this->create_filter('CNW Co.', $cohort->id);
        $this->assertFalse(cohort_is_member($cohort->id, $user));

        $query = $DB->count_records('cnw_sc_user_cohort', ['user_id' => $user, 'cohort_id' => $cohort->id, 'filter_id' => $filter['filterid']]);
        $this->assertEquals($query, 0);
    }

    public function test_smartcohort_check_user_create_event_with_rules()
    {
        global $DB;
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();

        $filter = $filter = $this->create_filter('CNW', $cohort->id);

        $usertoAdd = new stdClass();
        $usertoAdd->username = 'cnw';
        $usertoAdd->email = 'moodle@cnw.hu';
        $usertoAdd->firstname = 'CNW';
        $usertoAdd->lastname = 'CNW';

        $user = user_create_user($usertoAdd);

        $search = $DB->count_records('cnw_sc_queue', ['user_id' => $user]);

        $this->assertTrue(($search != 0));
    }

    public function test_smartcohort_run_filters_for_all_users()
    {
        global $DB;
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();
        $user = $this->getDataGenerator()->create_user(array('lastname' => 'CNW Co.'));
        $user2 = $this->getDataGenerator()->create_user();
        $filter = $filter = $this->create_filter('CNW Co.', $cohort->id);

        $this->assertFalse(cohort_is_member($cohort->id, $user->id));
        $query = $DB->count_records('cnw_sc_user_cohort', ['user_id' => $user->id, 'cohort_id' => $cohort->id, 'filter_id' => $filter['filterid']]);
        $this->assertEquals($query, 0);

        $this->assertFalse(cohort_is_member($cohort->id, $user2->id));
        $query = $DB->count_records('cnw_sc_user_cohort', ['user_id' => $user2->id, 'cohort_id' => $cohort->id, 'filter_id' => $filter['filterid']]);
        $this->assertEquals($query, 0);

        smartcohort_run_filters();

        $this->assertTrue(cohort_is_member($cohort->id, $user->id));
        $query = $DB->count_records('cnw_sc_user_cohort', ['user_id' => $user->id, 'cohort_id' => $cohort->id, 'filter_id' => $filter['filterid']]);
        $this->assertEquals($query, 1);

        $this->assertFalse(cohort_is_member($cohort->id, $user2->id));
        $query = $DB->count_records('cnw_sc_user_cohort', ['user_id' => $user2->id, 'cohort_id' => $cohort->id, 'filter_id' => $filter['filterid']]);
        $this->assertEquals($query, 0);


    }

    public function test_smartcohort_run_filters_for_one_user()
    {
        global $DB;
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();
        $user = $this->getDataGenerator()->create_user(array('lastname' => 'CNW Co.'));
        $user2 = $this->getDataGenerator()->create_user();
        $filter = $filter = $this->create_filter('CNW Co.', $cohort->id);

        $this->assertFalse(cohort_is_member($cohort->id, $user->id));
        $query = $DB->count_records('cnw_sc_user_cohort', ['user_id' => $user->id, 'cohort_id' => $cohort->id, 'filter_id' => $filter['filterid']]);
        $this->assertEquals($query, 0);

        $this->assertFalse(cohort_is_member($cohort->id, $user2->id));
        $query = $DB->count_records('cnw_sc_user_cohort', ['user_id' => $user2->id, 'cohort_id' => $cohort->id, 'filter_id' => $filter['filterid']]);
        $this->assertEquals($query, 0);

        smartcohort_run_filters($user->id);

        $this->assertTrue(cohort_is_member($cohort->id, $user->id));
        $query = $DB->count_records('cnw_sc_user_cohort', ['user_id' => $user->id, 'cohort_id' => $cohort->id, 'filter_id' => $filter['filterid']]);
        $this->assertEquals($query, 1);

        $this->assertFalse(cohort_is_member($cohort->id, $user2->id));
        $query = $DB->count_records('cnw_sc_user_cohort', ['user_id' => $user2->id, 'cohort_id' => $cohort->id, 'filter_id' => $filter['filterid']]);
        $this->assertEquals($query, 0);


    }

    public function test_smartcohort_delete_filter_with_undo()
    {
        global $DB;
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();
        $user = $this->getDataGenerator()->create_user(array('lastname' => 'CNW Co.'));
        $user2 = $this->getDataGenerator()->create_user(array('firstname' => 'CNW Co.'));
        $filter = $this->create_filter('CNW Co.', $cohort->id);
        $filter2 = $this->create_filter('CNW Co.', $cohort->id, 'firstname');
        smartcohort_run_filters();

        $this->assertTrue(cohort_is_member($cohort->id, $user->id));
        $this->assertTrue(cohort_is_member($cohort->id, $user2->id));

        smartcohort_delete_filter($filter['filter'], 1);

        $this->assertFalse(cohort_is_member($cohort->id, $user->id));
        $query = $DB->count_records('cnw_sc_user_cohort', ['user_id' => $user->id, 'cohort_id' => $cohort->id, 'filter_id' => $filter['filterid']]);
        $this->assertEquals($query, 0);

        $this->assertTrue(cohort_is_member($cohort->id, $user2->id));
        $query = $DB->count_records('cnw_sc_user_cohort', ['user_id' => $user2->id, 'cohort_id' => $cohort->id, 'filter_id' => $filter2['filterid']]);
        $this->assertEquals($query, 1);

    }

    public function test_smartcohort_delete_with_keep()
    {
        global $DB;
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();
        $user = $this->getDataGenerator()->create_user(array('lastname' => 'CNW Co.'));
        $user2 = $this->getDataGenerator()->create_user(array('firstname' => 'CNW Co.'));
        $filter = $this->create_filter('CNW Co.', $cohort->id);
        $filter2 = $this->create_filter('CNW Co.', $cohort->id, 'firstname');
        smartcohort_run_filters();

        $this->assertTrue(cohort_is_member($cohort->id, $user->id));
        $this->assertTrue(cohort_is_member($cohort->id, $user2->id));

        smartcohort_delete_filter($filter['filter'], 0);

        $this->assertTrue(cohort_is_member($cohort->id, $user->id));
        $query = $DB->count_records('cnw_sc_user_cohort', ['user_id' => $user->id, 'cohort_id' => $cohort->id, 'filter_id' => $filter['filterid']]);
        $this->assertEquals($query, 0);

        $this->assertTrue(cohort_is_member($cohort->id, $user2->id));
        $query = $DB->count_records('cnw_sc_user_cohort', ['user_id' => $user2->id, 'cohort_id' => $cohort->id, 'filter_id' => $filter2['filterid']]);
        $this->assertEquals($query, 1);
    }


}