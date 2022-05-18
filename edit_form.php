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

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/lib/authlib.php');
require_once($CFG->dirroot . '/cohort/lib.php');

class filter_edit_form extends moodleform
{

    /**
     * Define the filter edit form
     * @throws HTML_QuickForm_Error
     * @throws coding_exception
     * @throws dml_exception
     */
    public function definition()
    {
        global $DB, $CFG;

        $mform = $this->_form;
        $filter = $this->_customdata['data'];

        $mform->addElement('html', '<div class="box informationbox">' . get_string('description', 'local_cnw_smartcohort') . '</div>');

        // Basic data
        $mform->addElement('header', 'basic_data', get_string('basic_data', 'local_cnw_smartcohort'));

        // Name field
        $mform->addElement('text', 'name', get_string('name', 'local_cnw_smartcohort'), 'maxlength="254" size="50"');
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        // Cohort field
        $options = [];
        foreach (cohort_get_all_cohorts(0, 10000)['cohorts'] as $cohort) {
            $options[$cohort->id] = $cohort->name;
        }

        $attributes = [];
        if (isset($filter->cohort_id) && $filter->cohort_id > 0) {
            $attributes['disabled'] = 'disabled';
        }

        $mform->addElement('select', "cohort_id", get_string('cohort', 'local_cnw_smartcohort'), $options, $attributes);
        if (!isset($filter->cohort_id) || $filter->cohort_id == 0) {
            $mform->addRule('cohort_id', get_string('required'), 'required', null, 'client');
        }

        // User fields
        $mform->addElement('header', 'rules', get_string('rules', 'local_cnw_smartcohort'));

        $options = [
            '' => get_string('user_field_select_default', 'local_cnw_smartcohort'),
            'equals' => get_string('equals', 'local_cnw_smartcohort'),
            'not equals' => get_string('not_equals', 'local_cnw_smartcohort'),
            'start with' => get_string('start_with', 'local_cnw_smartcohort'),
            'end with' => get_string('end_with', 'local_cnw_smartcohort'),
            'contains' => get_string('contains', 'local_cnw_smartcohort'),
            'not contains' => get_string('not_contains', 'local_cnw_smartcohort')
        ];

        $logicaloperator = [
            '' => get_string('user_field_select_default', 'local_cnw_smartcohort'),
            'AND' => get_string('AND', 'local_cnw_smartcohort'),
            'OR' => get_string('OR', 'local_cnw_smartcohort')
        ];

        $auth = new auth_plugin_base();
        $customfields = $auth->get_custom_user_profile_fields();

        if (!empty($customfields)) {
            foreach ($customfields as $k => $v) {
                $customfields[$k] = str_replace('profile_field_', '', $v);
            }
        }

        $userfields = array_merge($auth->userfields, $customfields);

        $repeatarray = array();
        $repeateloptions = array();
        $groupitems = array(
            'label' => $mform->createElement('static', 'label', 'label', get_string('edit_if', 'local_cnw_smartcohort')),
            'userfield' => $mform->createElement('select', 'userfield', get_string('userfieldno', 'local_cnw_smartcohort'), $userfields),
            'operator' => $mform->createElement('select', 'operator', get_string('operatorno', 'local_cnw_smartcohort'), $options),
            'value' => $mform->createElement('text', 'value', get_string('valueno', 'local_cnw_smartcohort')),
            'logicaloperator' => $mform->createElement('select', 'logicaloperator', '', $logicaloperator)
        );

        $group = $mform->createElement('group', 'rules', get_string('ruleno', 'local_cnw_smartcohort'), $groupitems);
        $repeatarray[] = $group;
        $repeateloptions['rules']['type'] = PARAM_RAW;


        if ($filter->id) {
            $numberofrepeats = $DB->count_records('cnw_sc_rules', ['filter_id' => $filter->id]);
            $this->repeat_elements($repeatarray, $numberofrepeats, $repeateloptions, 'rule_repeats', 'rule_add', 1, null, true);
        } else {
            $this->repeat_elements($repeatarray, 1, $repeateloptions, 'rule_repeats', 'rule_add', 1, null, true);
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        if (isset($this->_customdata['returnurl'])) {
            $mform->addElement('hidden', 'returnurl', $this->_customdata['returnurl']->out_as_local_url());
            $mform->setType('returnurl', PARAM_LOCALURL);
        }

        $rule_repeats = $mform->getElementValue('rule_repeats');
        $last_rule_index = $rule_repeats - 1;
        $mform->hideIf("rules[$last_rule_index][logicaloperator]", 'rule_repeats', 'eq', $rule_repeats);

        for ($i=0; $i < $rule_repeats; $i++){
            $mform->setDefault("rules[$i][logicaloperator]", '');
        }
        $this->add_action_buttons();

        // RULE MUTATOR
        if ($filter->id) {
            $rules = $DB->get_records('cnw_sc_rules', ['filter_id' => $filter->id]);

            $i = 0;
            foreach ($rules as $rule) {
                $userfieldKey = "rules[$i][userfield]";
                $operatorKey = "rules[$i][operator]";
                $valueKey = "rules[$i][value]";
                $logicaloperatorKey = "rules[$i][logicaloperator]";
                $filter->$userfieldKey = array_search($rule->field, $userfields);
                $filter->$operatorKey = $rule->operator;
                $filter->$valueKey = $rule->value;
                $filter->$logicaloperatorKey = $rule->logicaloperator;
                $i++;
            }
        }

        $this->set_data($filter);
    }

    public function validation($data, $files)
    {
        global $DB;

        $errors = parent::validation($data, $files);

        if ($data['id'] && !$DB->record_exists('cnw_sc_filters', array('id' => $data['id']))) {
            $errors['id'] = get_string('filter_is_not_exist', 'local_cnw_smartcohort');
        }

        return $errors;
    }

}

