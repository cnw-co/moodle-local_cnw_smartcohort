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

require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/lib/authlib.php');

function smartcohort_get_filters($with_deleted = false)
{
    global $DB, $CFG;

    if ($with_deleted == false) {
        return $DB->get_records_sql("SELECT * FROM {cnw_sc_filters} WHERE deleted_flag = 0");
    } else {
        return $DB->get_records('cnw_sc_filters');
    }
}

function smartcohort_get_users_by_filter($filter, $userid = null)
{
    global $DB, $CFG;

    $rules = $DB->get_records('cnw_sc_rules', ['filter_id' => $filter]);

    $sql = "SELECT u.*";
    $queryWhere = [];
    $queryParams = [];
    foreach ($rules as $rule) {
        switch ($rule->operator) {
            case 'equals':
                $operator = '=';
                break;
            case 'not equals':
                $operator = '<>';
                break;
            case 'start with':
                $operator = 'LIKE';
                break;
            case 'end with':
                $operator = 'LIKE';
                break;
            default:
                $operator = '=';
                break;
        }

        if (($operator == '=' && $rule->value == '') || ($operator == '<>' && $rule->value != '')) {
            $queryWhere[] = "({$rule->field} {$operator} ? OR {$rule->field} IS NULL)";
        } elseif ($operator == 'LIKE' && $rule->value != '') {
            $queryWhere[] = "{$rule->field} {$operator} ?";
        } else {
            $queryWhere[] = "{$rule->field} {$operator} ?";
        }

        if ($operator == 'LIKE') {
            switch ($rule->operator) {
                case 'start with':
                    $queryParams[] = $rule->value . '%';
                    break;
                case 'end with':
                    $queryParams[] = '%' . $rule->value;
                    break;
            }
        } else {
            $queryParams[] = $rule->value;
        }

        if ($rule->is_custom_field) {
            $field = str_replace('profile_field_', '', $rule->field);
            $sql .= ", (SELECT UID.data FROM {user_info_field} UIF, {user_info_data} UID WHERE UID.fieldid = UIF.id AND UIF.shortname = '{$field}' AND UID.userid = u.id) as {$rule->field} ";
        }
    }

    $sql .= " FROM {user} u WHERE (u.deleted = 0 and u.id <> 1) ";
    if ($userid) {
        $sql .= "AND u.id = ? ";
        array_unshift($queryParams, $userid);
    }
    if (!empty($queryWhere)) {
        $sql .= 'HAVING ' . implode(' AND ', $queryWhere);
    }

    $users = $DB->get_records_sql($sql, $queryParams);

    return $users;
}


/**
 * Add new filter.
 *
 * @param  stdClass $filter
 * @return int new cohort id
 * @throws dml_exception
 */
function smartcohort_store_filter($filter)
{
    global $DB, $CFG;

    // STORE FILTER
    $filter->id = $DB->insert_record('cnw_sc_filters', $filter);

    // STORE RULES
    $auth = new auth_plugin_base();
    $customfields = $auth->get_custom_user_profile_fields();
    $userfields = array_merge($auth->userfields, $customfields);
    foreach ($userfields as $field) {

        $operatorKey = "userfield_{$field}_operator";
        if ($filter->$operatorKey != '') {
            $valueKey = "userfield_{$field}_value";

            $rule = new stdClass();
            $rule->filter_id = $filter->id;
            $rule->field = $field;
            $rule->operator = $filter->$operatorKey;
            $rule->value = $filter->$valueKey;
            if (empty($customfields) || !in_array($field, $customfields)) {
                $rule->is_custom_field = 0;
            } else {
                $rule->is_custom_field = 1;
            }

            $rule->id = $DB->insert_record('cnw_sc_rules', $rule);
        }

    }

    return $filter->id;
}

/**
 * Update existing filter.
 * @param  stdClass $filter
 * @return void
 * @throws dml_exception
 */
function smartcohort_update_filter($filter)
{
    global $DB, $CFG;

    // UPDATE FILTER
    $DB->update_record('cnw_sc_filters', $filter);

    // DELETE PREVIOUS RULES
    $DB->delete_records('cnw_sc_rules', ['filter_id' => $filter->id]);

    //STORE NEW RULES
    $auth = new auth_plugin_base();
    $customfields = $auth->get_custom_user_profile_fields();
    $userfields = array_merge($auth->userfields, $customfields);
    foreach ($userfields as $field) {

        $operatorKey = "userfield_{$field}_operator";
        if ($filter->$operatorKey != '') {
            $valueKey = "userfield_{$field}_value";

            $rule = new stdClass();
            $rule->filter_id = $filter->id;
            $rule->field = $field;
            $rule->operator = $filter->$operatorKey;
            $rule->value = $filter->$valueKey;
            if (empty($customfields) || !in_array($field, $customfields)) {
                $rule->is_custom_field = 0;
            } else {
                $rule->is_custom_field = 1;
            }

            $rule->id = $DB->insert_record('cnw_sc_rules', $rule);
        }

    }
}

/**
 * Delete filter.
 * @param  stdClass $filter
 * @param int $mode
 * @return void
 * @throws dml_exception
 */
function smartcohort_delete_filter($filter, $mode = 1)
{
    global $DB;

    switch ($mode) {
        case 1:
            // UNDO COHORT INSERTIONS
            $shouldRemove = $DB->get_records_sql('select *, (select count(*) from {cnw_sc_user_cohort} t2 where t2.user_id = t1.user_id and t2.cohort_id = t1.cohort_id and t2.filter_id <> t1.filter_id) as other_filters from {cnw_sc_user_cohort} t1 where filter_id = ?  having other_filters = 0', [
                $filter->id
            ]);
            foreach ($shouldRemove as $scAdd) {
                cohort_remove_member($scAdd->cohort_id, $scAdd->user_id);
            }
            break;
        case 2:
            // KEEP COHORT INSERTIONS
            // NOTHING TO DO AT THIS POINT
            break;
    }

    $DB->delete_records('cnw_sc_user_cohort', ['filter_id' => $filter->id]);
    $DB->delete_records('cnw_sc_rules', array('filter_id' => $filter->id));
    $DB->delete_records('cnw_sc_filters', array('id' => $filter->id));
}

/**
 * Run filter.
 * @param $filter
 * @param null $userid
 * @throws dml_exception
 */
function smartcohort_run_filter($filter, $userid = null)
{
    global $DB;

    $affectedUsers = smartcohort_get_users_by_filter($filter->id, $userid);
    if ($userid) {
        $cohortUsers = $DB->get_records('cohort_members', ['cohortid' => $filter->cohort_id, 'userid' => $userid]);
    } else {
        $cohortUsers = $DB->get_records('cohort_members', ['cohortid' => $filter->cohort_id]);
    }

    $affectedUserIds = [];
    foreach ($affectedUsers as $affectedUser) {
        $affectedUserIds[] = $affectedUser->id;
    }
    $cohortUserIds = [];
    foreach ($cohortUsers as $cohortUser) {
        $cohortUserIds[] = $cohortUser->userid;
    }

    $shouldBeInCohort = array_diff($affectedUserIds, $cohortUserIds);
    $shouldNotBeInCohort = array_diff($cohortUserIds, $affectedUserIds);

    // REMOVE FROM COHORT
    foreach ($shouldNotBeInCohort as $userId) {
        $scAdds = $DB->get_records('cnw_sc_user_cohort', ['cohort_id' => $filter->cohort_id, 'user_id' => $userId]);
        if (count($scAdds) == 1 && array_values($scAdds)[0]->filter_id == $filter->id) {
            cohort_remove_member($filter->cohort_id, $userId);
            $DB->delete_records('cnw_sc_user_cohort', ['cohort_id' => $filter->cohort_id, 'user_id' => $userId]);
        }
    }

    // ADD TO COHORT
    foreach ($shouldBeInCohort as $userId) {
        cohort_add_member($filter->cohort_id, $userId);
        if (!$DB->record_exists('cnw_sc_user_cohort', ['cohort_id' => $filter->cohort_id, 'user_id' => $userId, 'filter_id' => $filter->id])) {
            $scAdd = new stdClass();
            $scAdd->cohort_id = $filter->cohort_id;
            $scAdd->user_id = $userId;
            $scAdd->filter_id = $filter->id;
            $DB->insert_record('cnw_sc_user_cohort', $scAdd);
        }
    }

    // UPDATE THE FILTER'S RELATIONS IF ANOTHER FILTER ADDED USERS TO COHORT PREVIOUSLY
    $intersect = array_intersect($affectedUserIds, $cohortUserIds);
    foreach ($intersect as $userId) {
        if (!$DB->record_exists('cnw_sc_user_cohort', ['cohort_id' => $filter->cohort_id, 'user_id' => $userId, 'filter_id' => $filter->id])) {
            $scAdd = new stdClass();
            $scAdd->cohort_id = $filter->cohort_id;
            $scAdd->user_id = $userId;
            $scAdd->filter_id = $filter->id;
            $DB->insert_record('cnw_sc_user_cohort', $scAdd);
        }
    }
}

/**
 * Run all filters.
 * @param null $userid
 * @throws dml_exception
 */
function smartcohort_run_filters($userid = null)
{
    global $DB;

    $filters = smartcohort_get_filters();
    foreach ($filters as $filter) {
        smartcohort_run_filter($filter, $userid);
    }
}

/**
 * Delete a user's cohort insertions from the database.
 * @param null $userid
 * @throws dml_exception
 */
function smartcohort_delete_insertions($userid)
{
    global $DB;

    $DB->delete_records('cnw_sc_user_cohort', ['user_id' => $userid]);
}