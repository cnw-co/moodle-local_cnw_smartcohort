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

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$contextid = optional_param('contextid', 0, PARAM_INT);

if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}

require_login();
require_capability('moodle/cohort:manage', $context);

$title = get_string('pluginname', 'local_cnw_smartcohort');
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/cnw_smartcohort/index.php');
$PAGE->requires->js_call_amd('local_cnw_smartcohort/createrule', 'init');

echo $OUTPUT->header();

$action = optional_param('action', false, PARAM_ALPHA);

$profilefields = [
    'username' => get_string('username'),
    'realname' => get_string('fullnameuser'),
    'lastname' => get_string('lastname'),
    'firstname' => get_string('firstname'),
    'email' => get_string('email'),
    'city' => get_string('city'),
    'country' => get_string('country'),
    'confirmed' => get_string('confirmed', 'admin'),
    'suspended' => get_string('suspended', 'auth'),
    'auth' => get_string('authentication'),
    'courserole' => get_string('courserole', 'filters'),
    'anycourses' => get_string('anycourses', 'filters'),
    'systemrole' => get_string('globalrole', 'role'),
    'firstaccess' => get_string('firstaccess', 'filters'),
    'lastaccess' => get_string('lastaccess'),
    'neveraccessed' => get_string('neveraccessed', 'filters'),
    'timecreated' => get_string('timecreated'),
    'timemodified' => get_string('lastmodified'),
    'nevermodified' => get_string('nevermodified', 'filters'),
    'idnumber' => get_string('idnumber'),
    'institution' => get_string('institution'),
    'department' => get_string('department'),
    'lastip' => get_string('lastip'),
    0 => get_string('anyfield', 'local_cnw_smartcohort')
];

$customfields = profile_get_custom_fields();
foreach ($customfields as $customfield) {
    $profilefields[$customfield->id] = $customfield->name;
}
$operators = [
    0 => get_string('contains', 'filters'),
    1 => get_string('doesnotcontain', 'filters'),
    2 => get_string('isequalto', 'filters'),
    3 => get_string('startswith', 'filters'),
    4 => get_string('endswith', 'filters'),
    5 => get_string('isempty', 'filters'),
    6 => get_string('isnotdefined', 'filters'),
    7 => get_string('isdefined', 'filters')
];

switch ($action) {
    // List.
    case false:

        echo $OUTPUT->heading(get_string('rules', 'local_cnw_smartcohort'));
        echo $OUTPUT->render(
            new single_button(
                new moodle_url(
                    '/local/cnw_smartcohort/index.php'
                ),
                get_string(
                    'create_rule',
                    'local_cnw_smartcohort'
                ),
                'get',
                true,
                ['data-action' => 'rule-create']
            )
        );

        $rules = smartcohort_get_rules(true);

        $data = [];
        foreach ($rules as $rule) {
            // BUTTONS.
            $buttons = [
                html_writer::link(
                    new moodle_url(
                        '/local/cnw_smartcohort/edit.php',
                        ['id' => $rule->id, 'delete' => 1]
                    ),
                    $OUTPUT->pix_icon('t/delete', get_string('delete')),
                    ['title' => get_string('delete')]
                ),
                html_writer::link(
                    new moodle_url(
                        '/local/cnw_smartcohort/edit.php',
                        ['id' => $rule->id]
                    ),
                    $OUTPUT->pix_icon(
                        't/edit',
                        get_string('edit')
                    ),
                    ['title' => get_string('edit')]
                ),
                html_writer::link(
                    new moodle_url(
                        '/local/cnw_smartcohort/view.php',
                        ['id' => $rule->id]
                    ),
                    $OUTPUT->pix_icon(
                        'i/users',
                        get_string('users')
                    ),
                    ['title' => get_string('users')]
                ),
            ];
            if (!$rule->initialized) {
                unset($buttons[1]);
            }

            // FILTERS.
            $filters = $DB->get_records('cnw_sc_filter', ['rule_id' => $rule->id]);
            $filtersstring = "<ul>";
            if (empty($filters)) {
                $filtersstring .= "<li>" . get_string('all_users', 'local_cnw_smartcohort') . "</li>";
            } else {
                $i = 0;
                foreach ($filters as $filter) {
                    if ($filter->field == 'profile') {
                        if ($i == 0) {
                            $filtersstring .= '<li>' . get_string('if', 'local_cnw_smartcohort',
                                                                  $profilefields[$filter->profile]);
                        } else {
                            $filtersstring .= '<li>' . get_string('and_if', 'local_cnw_smartcohort',
                                                                  $profilefields[$filter->profile]);
                        }
                    } else {
                        if ($i == 0) {
                            $filtersstring .= '<li>' . get_string('if', 'local_cnw_smartcohort',
                                                                  $profilefields[$filter->field]);
                        } else {
                            $filtersstring .= '<li>' . get_string('and_if', 'local_cnw_smartcohort',
                                                                  $profilefields[$filter->field]);
                        }
                    }

                    $filtersstring .= ' ' .
                        $operators[$filter->operator] .
                        ' ' .
                        ' <i>\'' .
                        $filter->value . '\'</i></li>';
                    $i++;
                }
            }
            $filtersstring .= "</ul>";

            // AFFECTED USERS COUNT.
            $affectedusers = $DB->count_records('cnw_sc_user_cohort', ['rule_id' => $rule->id]);

            // COHORT.
            $cohort = $DB->get_record('cohort', array('id' => $rule->cohort_id));

            $data[] = [
                $rule->name,
                $cohort->name,
                $filtersstring,
                ($rule->initialized == 0) ? get_string('no', 'local_cnw_smartcohort') : get_string('yes', 'local_cnw_smartcohort'),
                ($rule->deleted_flag != "0") ? get_string('deleting', 'local_cnw_smartcohort') :
                (($rule->initialized == 1) ? $affectedusers : get_string('affect_need_initialize', 'local_cnw_smartcohort')),
                ($rule->deleted_flag == "0") ? implode(' ', $buttons) : ''
            ];
        }

        $table = new html_table();
        $table->id = 'rules';
        $table->attributes['class'] = 'admintable generaltable';
        $table->head = array(
            get_string('name', 'local_cnw_smartcohort'),
            get_string('cohort', 'local_cnw_smartcohort'),
            get_string('filters', 'local_cnw_smartcohort'),
            get_string('initialized', 'local_cnw_smartcohort'),
            get_string('affected_users', 'local_cnw_smartcohort'),
            get_string('edit')
        );
        $table->colclasses = array('', '', '', '', '', 'action');
        if (!empty($data)) {
            $table->data = $data;
        } else {
            $table->data = [
                [
                    '<i>' . get_string('no_data', 'local_cnw_smartcohort') . '</i>',
                    '',
                    '',
                    '',
                    '',
                    ''
                ]
            ];
        }

        echo html_writer::table($table);

        break;
}


echo $OUTPUT->footer();
