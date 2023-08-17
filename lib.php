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

 use local_cnw_smartcohort\form\action_form;
 use local_cnw_smartcohort\form\active_filter_form;
 use local_cnw_smartcohort\form\add_filter_form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/lib/authlib.php');
require_once($CFG->dirroot . '/user/filters/lib.php');

/**
 * Get smart cohort rules.
 *
 * @param boolean $withdeleted
 * @return array
 */
function smartcohort_get_rules($withdeleted = false) {
    global $DB, $CFG;

    if ($withdeleted == false) {
        return $DB->get_records_sql("SELECT * FROM {cnw_sc_rule} WHERE deleted_flag = 0");
    } else {
        return $DB->get_records('cnw_sc_rule');
    }
}

/**
 * Delete rule.
 *
 * @param  stdClass $rule
 * @param int $mode
 * @return void
 * @throws dml_exception
 */
function smartcohort_delete_rule($rule, $mode = 1) {
    global $DB;

    switch ($mode) {
        case 1:
            // UNDO COHORT INSERTIONS.
            $sql = "SELECT *
                    FROM {cnw_sc_user_cohort} t1
                    WHERE rule_id = ?
                    GROUP BY t1.id
                    HAVING (SELECT count(*)
                            FROM {cnw_sc_user_cohort} t2
                            WHERE t2.user_id = t1.user_id
                                AND t2.cohort_id = t1.cohort_id
                                AND t2.rule_id <> t1.rule_id) = 0";

            $shouldremove = $DB->get_records_sql($sql, [$rule->id]);
            foreach ($shouldremove as $scadd) {
                cohort_remove_member($scadd->cohort_id, $scadd->user_id);
            }
            break;
        case 2:
            // KEEP COHORT INSERTIONS.
            // NOTHING TO DO AT THIS POINT.
            break;
    }

    $DB->delete_records('cnw_sc_user_cohort', ['rule_id' => $rule->id]);
    $DB->delete_records('cnw_sc_filter', ['rule_id' => $rule->id]);
    $DB->delete_records('cnw_sc_rule', ['id' => $rule->id]);
}

/**
 * Run rule.
 *
 * @param stdClass $rule
 * @param int|null $userid
 * @throws dml_exception
 * @return void
 */
function smartcohort_run_rule($rule, $userid = null) {
    global $DB;

    $affectedusers = smartcohort_get_users_by_rule($rule->id, $userid);
    if ($userid) {
        $cohortusers = $DB->get_records('cohort_members', ['cohortid' => $rule->cohort_id, 'userid' => $userid]);
    } else {
        $cohortusers = $DB->get_records('cohort_members', ['cohortid' => $rule->cohort_id]);
    }

    $affecteduserids = [];
    foreach ($affectedusers as $affecteduser) {
        $affecteduserids[] = $affecteduser->id;
    }
    $cohortuserids = [];
    foreach ($cohortusers as $cohortuser) {
        $cohortuserids[] = $cohortuser->userid;
    }

    $shouldbeincohort = array_diff($affecteduserids, $cohortuserids);
    $shouldnotbeincohort = array_diff($cohortuserids, $affecteduserids);

    // REMOVE FROM COHORT.
    foreach ($shouldnotbeincohort as $userid) {
        $scadds = $DB->get_records('cnw_sc_user_cohort', ['cohort_id' => $rule->cohort_id, 'user_id' => $userid]);
        if (count($scadds) == 1 && array_values($scadds)[0]->rule_id == $rule->id) {
            cohort_remove_member($rule->cohort_id, $userid);
            $DB->delete_records('cnw_sc_user_cohort', ['cohort_id' => $rule->cohort_id, 'user_id' => $userid]);
        }
    }

    // ADD TO COHORT.
    foreach ($shouldbeincohort as $userid) {
        cohort_add_member($rule->cohort_id, $userid);
        if (!$DB->record_exists('cnw_sc_user_cohort',
                                ['cohort_id' => $rule->cohort_id,
                                'user_id' => $userid,
                                'rule_id' => $rule->id])) {
            $scadd = new stdClass();
            $scadd->cohort_id = $rule->cohort_id;
            $scadd->user_id = $userid;
            $scadd->rule_id = $rule->id;
            $DB->insert_record('cnw_sc_user_cohort', $scadd);
        }
    }

    // UPDATE THE RULE'S RELATIONS IF ANOTHER RULE ADDED USERS TO COHORT PREVIOUSLY.
    $intersect = array_intersect($affecteduserids, $cohortuserids);
    foreach ($intersect as $userid) {
        if (!$DB->record_exists('cnw_sc_user_cohort',
                                ['cohort_id' => $rule->cohort_id,
                                'user_id' => $userid,
                                'rule_id' => $rule->id])) {
            $scadd = new stdClass();
            $scadd->cohort_id = $rule->cohort_id;
            $scadd->user_id = $userid;
            $scadd->rule_id = $rule->id;
            $DB->insert_record('cnw_sc_user_cohort', $scadd);
        }
    }
}

/**
 * Run all rules.
 *
 * @param int|null $userid
 * @throws dml_exception
 */
function smartcohort_run_rules($userid = null) {
    global $DB;

    $rules = smartcohort_get_rules();
    foreach ($rules as $rule) {
        smartcohort_run_rule($rule, $userid);
    }
}

/**
 * Delete a user's cohort insertions from the database.
 *
 * @param int|null $userid
 * @throws dml_exception
 */
function smartcohort_delete_insertions($userid) {
    global $DB;

    $DB->delete_records('cnw_sc_user_cohort', ['user_id' => $userid]);
}

/**
 * Get all users who meet a specific smart cohort filtering criteria.
 *
 * @param int $ruleid
 * @param int $userid
 * @return array
 */
function smartcohort_get_users_by_rule($ruleid, $userid = null) {
    global $DB, $CFG;

    $filters = $DB->get_records('cnw_sc_filter', ['rule_id' => $ruleid]);

    foreach ($filters as $filter) {
        $scfilter[$filter->field][] = [
            'operator' => (int) $filter->operator,
            'value' => $filter->value,
            'profile' => (int) $filter->profile
        ];
    }

    $fieldnames = [
        'realname' => 1,
        'lastname' => 1,
        'firstname' => 1,
        'username' => 1,
        'email' => 1,
        'city' => 1,
        'country' => 1,
        'confirmed' => 1,
        'suspended' => 1,
        'profile' => 1,
        'courserole' => 1,
        'anycourses' => 1,
        'systemrole' => 1,
        'firstaccess' => 1,
        'lastaccess' => 1,
        'neveraccessed' => 1,
        'timecreated' => 1,
        'timemodified' => 1,
        'nevermodified' => 1,
        'auth' => 1,
        'mnethostid' => 1,
        'idnumber' => 1,
        'institution' => 1,
        'department' => 1,
        'lastip' => 1
    ];

    $fields = [];

    foreach ($fieldnames as $fieldname => $advanced) {
        if ($field = smartcohort_get_field($fieldname, $advanced)) {
            $fields[$fieldname] = $field;
        }
    }

    list($extrasql, $params) = smartcohort_get_sql_filter($scfilter, $fields);
    $sort = 'name';
    $dir = 'ASC';
    $context = context_system::instance();
    $users = get_users_listing(
        $sort,
        $dir,
        0,
        0,
        '',
        '',
        '',
        $extrasql,
        $params,
        $context
    );

    return $users;
}

/**
 * Get user_filter fields.
 *
 * @param string $fieldname
 * @param bool $advanced
 * @return mixed user filter objects
 */
function smartcohort_get_field($fieldname, $advanced) {
    global $USER, $CFG, $DB, $SITE;

    switch ($fieldname) {
        case 'username':
            return new user_filter_text('username', get_string('username'), $advanced, 'username');
        case 'realname':
            return new user_filter_text('realname', get_string('fullnameuser'), $advanced, $DB->sql_fullname());
        case 'lastname':
            return new user_filter_text('lastname', get_string('lastname'), $advanced, 'lastname');
        case 'firstname':
            return new user_filter_text('firstname', get_string('firstname'), $advanced, 'firstname');
        case 'email':
            return new user_filter_text('email', get_string('email'), $advanced, 'email');
        case 'city':
            return new user_filter_text('city', get_string('city'), $advanced, 'city');
        case 'country':
            return new user_filter_select('country', get_string('country'), $advanced, 'country',
                                          get_string_manager()->get_list_of_countries());
        case 'confirmed':
            return new user_filter_yesno('confirmed', get_string('confirmed', 'admin'), $advanced, 'confirmed');
        case 'suspended':
            return new user_filter_yesno('suspended', get_string('suspended', 'auth'), $advanced, 'suspended');
        case 'profile':
            return new user_filter_profilefield('profile', get_string('profilefields', 'admin'), $advanced);
        case 'courserole':
            return new user_filter_courserole('courserole', get_string('courserole', 'filters'), $advanced);
        case 'anycourses':
            return new user_filter_anycourses('anycourses', get_string('anycourses', 'filters'), $advanced, 'user_enrolments');
        case 'systemrole':
            return new user_filter_globalrole('systemrole', get_string('globalrole', 'role'), $advanced);
        case 'firstaccess':
            return new user_filter_date('firstaccess', get_string('firstaccess', 'filters'), $advanced, 'firstaccess');
        case 'lastaccess':
            return new user_filter_date('lastaccess', get_string('lastaccess'), $advanced, 'lastaccess');
        case 'neveraccessed':
            return new user_filter_checkbox('neveraccessed',
                                            get_string('neveraccessed', 'filters'),
                                            $advanced,
                                            'firstaccess',
                                            ['lastaccess_sck',
                                            'lastaccess_eck',
                                            'firstaccess_eck',
                                            'firstaccess_sck']
                                            );
        case 'timecreated':
            return new user_filter_date('timecreated', get_string('timecreated'), $advanced, 'timecreated');
        case 'timemodified':
            return new user_filter_date('timemodified', get_string('lastmodified'), $advanced, 'timemodified');
        case 'nevermodified':
            return new user_filter_checkbox('nevermodified',
                                            get_string('nevermodified', 'filters'),
                                            $advanced, ['timemodified', 'timecreated'],
                                            ['timemodified_sck', 'timemodified_eck']
                                            );
        case 'cohort':
            return new user_filter_cohort($advanced);
        case 'idnumber':
            return new user_filter_text('idnumber', get_string('idnumber'), $advanced, 'idnumber');
        case 'institution':
            return new user_filter_text('institution', get_string('institution'), $advanced, 'institution');
        case 'department':
            return new user_filter_text('department', get_string('department'), $advanced, 'department');
        case 'lastip':
            return new user_filter_text('lastip', get_string('lastip'), $advanced, 'lastip');
        case 'auth':
            $plugins = core_component::get_plugin_list('auth');
            $choices = [];
            foreach ($plugins as $auth => $unused) {
                $choices[$auth] = get_string('pluginname', "auth_{$auth}");
            }
            return new user_filter_simpleselect('auth', get_string('authentication'), $advanced, 'auth', $choices);

        case 'mnethostid':
            // Include all hosts even those deleted or otherwise problematic.
            if (!$hosts = $DB->get_records('mnet_host', null, 'id', 'id, wwwroot, name')) {
                $hosts = [];
            }
            $choices = [];
            foreach ($hosts as $host) {
                if ($host->id == $CFG->mnet_localhost_id) {
                    $choices[$host->id] = format_string($SITE->fullname) . ' (' . get_string('local') . ')';
                } else if (empty($host->wwwroot)) {
                    // All hosts.
                    continue;
                } else {
                    $choices[$host->id] = $host->name . ' (' . $host->wwwroot . ')';
                }
            }
            if ($usedhosts = $DB->get_fieldset_sql("SELECT DISTINCT mnethostid FROM {user} WHERE deleted=0")) {
                foreach ($usedhosts as $hostid) {
                    if (empty($hosts[$hostid])) {
                        $choices[$hostid] = 'id: ' . $hostid . ' (' . get_string('error') . ')';
                    }
                }
            }
            if (count($choices) < 2) {
                return null; // Filter not needed.
            }
            return new user_filter_simpleselect('mnethostid', get_string('mnetidprovider', 'mnet'),
                                                $advanced, 'mnethostid', $choices);

        default:
            return null;
    }
}

/**
 * Returns sql where statement based on active user filters.
 *
 * @param array $scfilter
 * @param array $fields
 * @param string $extra
 * @param array|null $params
 * @return array
 */
function smartcohort_get_sql_filter($scfilter = null, $fields = null, $extra = '', array $params = null) {

    $sqls = [];
    if ($extra != '') {
        $sqls[] = $extra;
    }
    $params = (array) $params;

    if (!empty($scfilter)) {
        foreach ($scfilter as $fname => $datas) {
            if (!array_key_exists($fname, $fields)) {
                continue; // Filter not used.
            }
            $field = $fields[$fname];
            foreach ($datas as $i => $data) {
                list($s, $p) = $field->get_sql_filter($data);
                $sqls[] = $s;
                $params = $params + $p;
            }
        }
    }

    if (empty($sqls)) {
        return ['', []];
    } else {
        $sqls = implode(' AND ', $sqls);
        return [$sqls, $params];
    }
}

/**
 * Save smart cohort rule and filters
 *
 * @param array $scdata
 * @param array $scfilter
 * @return int
 */
function smartcohort_save($scdata, $scfilter) {
    global $DB;

    $transaction = $DB->start_delegated_transaction();

    $ruleobj = new stdClass;
    $ruleobj->name = $scdata['name'];
    $ruleobj->cohort_id = $scdata['cohort_id'];
    $ruleobj->initialized = 0;
    $ruleid = $DB->insert_record('cnw_sc_rule', $ruleobj);

    foreach ($scfilter as $field => $values) {
        foreach ($values as $value) {
            $filterobj = new stdClass;
            $filterobj->rule_id = $ruleid;
            $filterobj->field = $field;
            $filterobj->operator = $value['operator'];
            $filterobj->value = $value['value'];
            if ($field == 'profile') {
                $filterobj->profile = $value['profile'];
            }
            $DB->insert_record('cnw_sc_filter', $filterobj);
        }
    }
    $transaction->allow_commit();

    return $ruleid;
}

/**
 * Update smart cohort rule and filters.
 *
 * @param array $scdata
 * @param array $scfilter
 * @return void
 */
function smartcohort_update($scdata, $scfilter) {
    global $DB;

    $transaction = $DB->start_delegated_transaction();

    $ruleobj = new stdClass;
    $ruleobj->id = $scdata['rule_id'];
    $ruleobj->name = $scdata['name'];
    $ruleobj->cohort_id = $scdata['cohort_id'];
    $ruleobj->initialized = 0;
    $DB->update_record('cnw_sc_rule', $ruleobj);

    $DB->delete_records('cnw_sc_filter', ['rule_id' => $scdata['rule_id']]);
    foreach ($scfilter as $field => $values) {
        foreach ($values as $value) {
            $filterobj = new stdClass;
            $filterobj->rule_id = $scdata['rule_id'];
            $filterobj->field = $field;
            $filterobj->operator = $value['operator'];
            $filterobj->value = $value['value'];
            if ($field == 'profile') {
                $filterobj->profile = $value['profile'];
            }
            $DB->insert_record('cnw_sc_filter', $filterobj);
        }
    }
    $transaction->allow_commit();
}

/**
 * Form to display when deleting smart cohort rule.
 *
 * @param stdClass $rule
 * @param int $confirm
 * @param moodle_url $returnurl
 * @return void
 */
function smartcohort_display_delete_form($rule, $confirm, $returnurl) {
    global $DB, $PAGE, $OUTPUT, $COURSE;
    $PAGE->url->param('delete', 1);
    if ($confirm && confirm_sesskey()) {
        $rule->deleted_flag = $confirm;
        $DB->update_record('cnw_sc_rule', $rule);
        redirect($returnurl);
    }
    $strheading = get_string('delrule', 'local_cnw_smartcohort');
    $PAGE->navbar->add($strheading);
    $PAGE->set_title($strheading);
    $PAGE->set_heading($COURSE->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($strheading);

    // CONFIRM.
    $message = get_string('delconfirm', 'local_cnw_smartcohort', format_string($rule->name)) . '<br/><br/>';
    $message .= '<b>' . get_string('delconfirm_undo', 'local_cnw_smartcohort') . ':</b> ' .
                        get_string('delconfirm_undo_desc', 'local_cnw_smartcohort') . '<br/><br/>';
    $message .= '<b>' . get_string('delconfirm_keep', 'local_cnw_smartcohort') . ':</b> ' .
                        get_string('delconfirm_keep_desc', 'local_cnw_smartcohort') . '<br/>';

    $continue1 = new single_button(new moodle_url('/local/cnw_smartcohort/edit.php',
                                                  [
                                                  'id' => $rule->id,
                                                  'delete' => 1,
                                                  'sesskey' => sesskey(),
                                                  'confirm' => 1,
                                                  'returnurl' => $returnurl->out_as_local_url()
                                                  ]
                                                ),
                                   get_string('delete_confirm_1', 'local_cnw_smartcohort'),
                                   'post',
                                   true);

    $continue2 = new single_button(new moodle_url('/local/cnw_smartcohort/edit.php',
                                                  [
                                                  'id' => $rule->id,
                                                  'delete' => 1,
                                                  'confirm' => 2,
                                                  'sesskey' => sesskey(),
                                                  'returnurl' => $returnurl->out_as_local_url()
                                                  ]
                                                ),
                                   get_string('delete_confirm_2', 'local_cnw_smartcohort'),
                                   'post',
                                   true);

    $cancel = new single_button(new moodle_url($returnurl), get_string('cancel'), 'get');

    $attributes = [
        'role' => 'alertdialog',
        'aria-labelledby' => 'modal-header',
        'aria-describedby' => 'modal-body',
        'aria-modal' => 'true'
    ];
    $confirmoutput = $OUTPUT->box_start('generalbox modal modal-dialog modal-in-page show', 'notice2', $attributes);
    $confirmoutput .= $OUTPUT->box_start('modal-content', 'modal-content');
    $confirmoutput .= $OUTPUT->box_start('modal-header p-x-1', 'modal-header');
    $confirmoutput .= html_writer::tag('h4', get_string('confirm'));
    $confirmoutput .= $OUTPUT->box_end();
    $attributes = [
        'role' => 'alert',
        'data-aria-autofocus' => 'true'
    ];
    $confirmoutput .= $OUTPUT->box_start('modal-body', 'modal-body', $attributes);
    $confirmoutput .= html_writer::tag('p', $message);
    $confirmoutput .= $OUTPUT->box_end();
    $confirmoutput .= $OUTPUT->box_start('modal-footer', 'modal-footer');
    $confirmoutput .= html_writer::tag('div',
                                       $OUTPUT->render($continue1) .
                                       $OUTPUT->render($continue2) .
                                       $OUTPUT->render($cancel), ['class' => 'buttons']);
    $confirmoutput .= $OUTPUT->box_end();
    $confirmoutput .= $OUTPUT->box_end();
    $confirmoutput .= $OUTPUT->box_end();
    echo $confirmoutput;

    echo '<style>.modal.modal-in-page{position: static;z-index: 0;margin: 0 auto 0 auto;}</style>';

    echo $OUTPUT->footer();
    die;
}

/**
 * Class smartcohort_filtering
 *
 * @package     local_cnw_smartcohort
 * @copyright   CNW Rendszerintegrációs Zrt. <moodle@cnw.hu>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class smartcohort_filtering extends user_filtering {

    /** @var add_filter_form */
    public $_addform;

    /** @var active_filter_form */
    public $_activeform;

    /** @var action_form */
    public $_actionform;

    /** @var array */
    public $scfilter;

    /** @var array */
    public $scdata;

    /**
     * Contructor
     *
     * @param int $ruleid
     * @param array $fieldnames array of visible user fields
     * @param string $baseurl base url used for submission/return, null if the same of current page
     * @param array $extraparams extra page parameters
     */
    public function __construct($ruleid, $fieldnames = null, $baseurl = null, $extraparams = null) {
        global $DB;

        $this->scfilter = [];
        $this->scdata = [];
        $gotemptyonedit = false;

        $tmp = $DB->get_record('cnw_sc_tmp', ['rule_id' => -1]);
        if ($tmp) {
            $this->scdata['rule_id'] = $ruleid;
            $this->scdata['name'] = $tmp->name;
            $this->scdata['cohort_id'] = $tmp->cohort_id;
            $DB->delete_records('cnw_sc_tmp', ['rule_id' => -1]);
        }

        $tmp = $DB->get_records('cnw_sc_tmp', ['rule_id' => $ruleid]);

        foreach ($tmp as $key => $tmpfilter) {
            if ($tmpfilter->field == 'got_empty_on_edit') {
                $gotemptyonedit = true;
                unset($tmp[$key]);
                $DB->delete_records('cnw_sc_tmp', ['field' => 'got_empty_on_edit']);
            }
        }

        if ($ruleid) {
            $rule = $DB->get_record('cnw_sc_rule', ['id' => $ruleid]);
            if (!$tmp && !$gotemptyonedit) {
                $filters = $DB->get_records('cnw_sc_filter', ['rule_id' => $ruleid]);
                $this->scdata['rule_id'] = $ruleid;
                $this->scdata['name'] = $rule->name;
                $this->scdata['cohort_id'] = $rule->cohort_id;
                foreach ($filters as $filter) {
                    // Store rule&filter data from db into class property.
                    if ($filter->field != 'got_empty_on_edit') {
                        $this->scfilter[$filter->field][] = [
                            'operator' => (int) $filter->operator,
                            'value' => $filter->value,
                            'profile' => (int) $filter->profile
                        ];
                    }
                    // Store rule&filter data into tmp db table.
                    $filter->rule_id = $rule->id;
                    $filter->cohort_id = $rule->cohort_id;
                    $filter->name = $rule->name;
                    $DB->insert_record('cnw_sc_tmp', $filter);
                }

            } else {
                // Store rule&filter data from temporary db into class properties.
                foreach ($tmp as $tmpfilter) {
                    if ($tmpfilter->field != 'got_empty_on_edit') {
                        $this->scfilter[$tmpfilter->field][] = [
                            'operator' => (int) $tmpfilter->operator,
                            'value' => $tmpfilter->value,
                            'profile' => (int) $tmpfilter->profile
                        ];
                    }
                }
                $this->scdata['rule_id'] = $ruleid;
                $this->scdata['name'] = $rule->name;
                $this->scdata['cohort_id'] = $rule->cohort_id;
            }
        } else {
            if (!$tmp) {
                $this->scfilter = [];
            } else {
                // Store rule&filter data from temporary db into class properties.
                foreach ($tmp as $tmpfilter) {
                    if ($tmpfilter->field != 'got_empty_on_edit') {
                        $this->scfilter[$tmpfilter->field][] = [
                            'operator' => (int) $tmpfilter->operator,
                            'value' => $tmpfilter->value,
                            'profile' => (int) $tmpfilter->profile
                        ];
                    }
                }
                $this->scdata['rule_id'] = $ruleid;
                $this->scdata['name'] = reset($tmp)->name;
                $this->scdata['cohort_id'] = reset($tmp)->cohort_id;
            }
        }

        if (empty($fieldnames)) {
            // As a start, add all fields as advanced fields (which are only available after clicking on "Show more").
            $fieldnames = [
                'realname' => 0,
                'lastname' => 0,
                'firstname' => 0,
                'username' => 0,
                'email' => 0,
                'city' => 0,
                'country' => 0,
                'confirmed' => 0,
                'suspended' => 0,
                'profile' => 0,
                /* 'courserole' => 0,
                // 'anycourses' => 0,
                // 'systemrole' => 0,
                // 'cohort' => 0,
                // 'firstaccess' => 0,
                // 'lastaccess' => 0,
                // 'neveraccessed' => 0,
                // 'timecreated' => 0,
                // 'timemodified' => 0,
                // 'nevermodified' => 0,
                 'mnethostid' => 0, */
                'auth' => 0,
                'idnumber' => 0,
                'institution' => 0,
                'department' => 0,
                'lastip' => 0
            ];

            // Get the config which filters the admin wanted to show by default.
            $userfiltersdefault = get_config('core', 'userfiltersdefault');

            // If the admin did not enable any filter, the form will not make much sense if all fields are hidden behind
            // "Show more". Thus, we enable the 'realname' filter automatically.
            if ($userfiltersdefault == '') {
                $userfiltersdefault = ['realname'];

                // Otherwise, we split the enabled filters into an array.
            } else {
                $userfiltersdefault = explode(',', $userfiltersdefault);
            }

            // Show these fields by default which the admin has enabled in the config.
            foreach ($userfiltersdefault as $key) {
                $fieldnames[$key] = 0;
            }
        }

        $this->_fields = [];

        foreach ($fieldnames as $fieldname => $advanced) {
            if ($field = $this->get_field($fieldname, $advanced)) {
                $this->_fields[$fieldname] = $field;
            }
        }

        // First the new filter form.
        $this->_addform = new add_filter_form($baseurl, [
            'fields' => $this->_fields,
            'extraparams' => $extraparams,
            'scfilter' => $this->scfilter,
            'scdata' => $this->scdata
        ]);

        if (!empty($this->scdata)) {
            $defaultvalues = new stdClass;
            $defaultvalues->name = $this->scdata['name'];
            $defaultvalues->cohort_id = $this->scdata['cohort_id'];
            $this->_addform->set_data($defaultvalues);
        }

        if ($adddata = $this->_addform->get_data()) {
            // Clear previous filters.
            if (!empty($adddata->replacefilters)) {
                $this->scfilter = [];
            }

            // Add new filters.
            foreach ($this->_fields as $fname => $field) {
                $data = $field->check_data($adddata);
                if ($data === false) {
                    continue; // Nothing new.
                }
                if (!array_key_exists($fname, $this->scfilter)) {
                    $this->scfilter[$fname] = [];
                }
                $this->scfilter[$fname][] = $data;
            }
            $this->scdata['rule_id'] = $ruleid;
            $this->scdata['name'] = $adddata->name;
            $this->scdata['cohort_id'] = $adddata->cohort_id;
        }

        // Now the active filters.
        $this->_activeform = new active_filter_form($baseurl, [
            'fields' => $this->_fields,
            'extraparams' => $extraparams,
            'scfilter' => $this->scfilter
        ]);

        if ($activedata = $this->_activeform->get_data()) {
            if (!empty($activedata->removeall)) {
                $this->scfilter = [];

            } else if (!empty($activedata->removeselected) && !empty($activedata->filter)) {
                foreach ($activedata->filter as $fname => $instances) {
                    foreach ($instances as $i => $val) {
                        if (empty($val)) {
                            continue;
                        }
                        unset($this->scfilter[$fname][$i]);
                    }
                    if (empty($this->scfilter[$fname])) {
                        unset($this->scfilter[$fname]);
                    }
                }
            }
        }

        // Now the action buttons.
        $this->_actionform = new action_form($baseurl, ['scdata' => $this->scdata]);
        $returnurl = new moodle_url('/local/cnw_smartcohort');
        if ($actiondata = $this->_actionform->is_cancelled()) {
            $DB->delete_records('cnw_sc_tmp', ['rule_id' => $ruleid]);
            redirect($returnurl);
        } else if ($actiondata = $this->_actionform->get_data()) {
            $DB->delete_records('cnw_sc_tmp', ['rule_id' => $ruleid]);
            if ($ruleid) {
                smartcohort_update($this->scdata, $this->scfilter);

            } else {
                smartcohort_save($this->scdata, $this->scfilter);

            }
            redirect($returnurl);
        }

        // Rebuild the forms if filters data was processed.
        if ($adddata || $activedata || $actiondata) {

            $_POST = []; // Reset submitted data.
            $this->_addform = new add_filter_form($baseurl, [
                'fields' => $this->_fields,
                'extraparams' => $extraparams,
                'scfilter' => $this->scfilter,
                'scdata' => $this->scdata
            ]);

            $this->_activeform = new active_filter_form($baseurl, [
                'fields' => $this->_fields,
                'extraparams' => $extraparams,
                'scfilter' => $this->scfilter
            ]);

            $this->_actionform = new action_form($baseurl, ['scdata' => $this->scdata]);

            // Store rule&filter data into tmp db table.
            $transaction = $DB->start_delegated_transaction();
            $DB->delete_records('cnw_sc_tmp', ['rule_id' => $ruleid]);
            foreach ($this->scfilter as $field => $values) {
                foreach ($values as $value) {
                    $record = new stdClass;
                    $record->rule_id = $ruleid;
                    $record->name = $this->scdata['name'];
                    $record->cohort_id = $this->scdata['cohort_id'];
                    $record->field = $field;
                    if ($field != 'confirmed' && $field != 'suspended' && $field != 'auth') {
                        $record->operator = $value['operator'];
                    }
                    $record->value = $value['value'];
                    if ($field == 'profile') {
                        $record->profile = $value['profile'];
                    }
                    $DB->insert_record('cnw_sc_tmp', $record);
                }
            }

            // If all active filters were deleted when editing a rule we
            // insert a record in the tmp table, so we can check it.
            if ($ruleid && empty($this->scfilter)) {
                $record = new stdClass;
                $record->rule_id = $this->scdata['rule_id'];
                $record->name = $this->scdata['name'];
                $record->cohort_id = $this->scdata['cohort_id'];
                $record->field = 'got_empty_on_edit';
                $DB->insert_record('cnw_sc_tmp', $record);
            }
            $transaction->allow_commit();

            if (!empty($this->scdata)) {
                $defaultvalues = new stdClass;
                $defaultvalues->name = $this->scdata['name'];
                $defaultvalues->cohort_id = $this->scdata['cohort_id'];
                $this->_addform->set_data($defaultvalues);
            }
        }
    }

    /**
     * Returns sql where statement based on active user filters
     * @param string $extra sql
     * @param array $params named params (recommended prefix ex)
     * @return array sql string and $params
     */
    public function get_sql_filter($extra = '', array $params = null) {

        $sqls = [];
        if ($extra != '') {
            $sqls[] = $extra;
        }
        $params = (array) $params;

        if (!empty($this->scfilter)) {
            foreach ($this->scfilter as $fname => $datas) {
                if (!array_key_exists($fname, $this->_fields)) {
                    continue; // Filter not used.
                }
                $field = $this->_fields[$fname];
                foreach ($datas as $i => $data) {
                    list($s, $p) = $field->get_sql_filter($data);
                    $sqls[] = $s;
                    $params = $params + $p;
                }
            }
        }

        if (empty($sqls)) {
            return ['', []];
        } else {
            $sqls = implode(' AND ', $sqls);
            return [$sqls, $params];
        }
    }

    /**
     * Display table of filtered users.
     *
     * @param string $baseurl
     * @param string $sort
     * @param string $dir
     * @param int $page
     * @param int $perpage
     * @param context $context
     * @return void
     */
    public function display_table($baseurl, $sort, $dir, $page, $perpage, $context) {
        global $CFG, $OUTPUT;

        $site = get_site();
        $extracolumns = ['email'];
        $allusernamefields = \core_user\fields::get_name_fields(true);
        $columns = array_merge($allusernamefields, $extracolumns);

        foreach ($columns as $column) {
            $string[$column] = \core_user\fields::get_display_name($column);
            if ($sort != $column) {
                $columndir = "ASC";
                $columnicon = "";
            } else {
                $columndir = ($dir == "ASC") ? "DESC" : "ASC";
                $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
                $columnicon = $OUTPUT->pix_icon(
                    't/' . $columnicon,
                    get_string(strtolower($columndir)),
                    'core',
                    ['class' => 'iconsort']
                );
            }
            $$column = "<a href=\"edit.php?sort=$column&amp;dir=$columndir\">" . $string[$column] . "</a>$columnicon";
        }

        // We need to check that alternativefullnameformat is not set to '' or language.
        // We don't need to check the fullnamedisplay setting here as the fullname function call further down has
        // the override parameter set to true.
        $fullnamesetting = $CFG->alternativefullnameformat;
        // If we are using language or it is empty, then retrieve the default user names of just 'firstname' and 'lastname'.
        if ($fullnamesetting == 'language' || empty($fullnamesetting)) {
            // Set $a variables to return 'firstname' and 'lastname'.
            $a = new stdClass();
            $a->firstname = 'firstname';
            $a->lastname = 'lastname';
            // Getting the fullname display will ensure that the order in the language file is maintained.
            $fullnamesetting = get_string('fullnamedisplay', null, $a);
        }

        // Order in string will ensure that the name columns are in the correct order.
        $usernames = order_in_string($allusernamefields, $fullnamesetting);
        $fullnamedisplay = [];
        foreach ($usernames as $name) {
            // Use the link from $$column for sorting on the user's name.
            $fullnamedisplay[] = ${$name};
        }
        // All of the names are in one column. Put them into a string and separate them with a /.
        $fullnamedisplay = implode(' / ', $fullnamedisplay);
        // If $sort = name then it is the default for the setting and we should use the first name to sort by.
        if ($sort == "name") {
            // Use the first item in the array.
            $sort = reset($usernames);
        }

        list($extrasql, $params) = $this->get_sql_filter();
        $users = get_users_listing(
            $sort,
            $dir, $page * $perpage,
            $perpage,
            '',
            '',
            '',
            $extrasql,
            $params,
            $context
        );
        $usercount = get_users(false);
        $usersearchcount = get_users(false, '', false, null, "", '', '', '', '', '*', $extrasql, $params);

        if ($extrasql !== '') {
            echo $OUTPUT->heading("$usersearchcount / $usercount " . get_string('users'));
            $usercount = $usersearchcount;
        } else {
            echo $OUTPUT->heading("$usercount " . get_string('users'));
        }

        flush();

        $table = new html_table();
        $table->head = [];
        $table->colclasses = [];
        $table->head[] = $fullnamedisplay;
        $table->attributes['class'] = 'admintable generaltable table-sm';
        foreach ($extracolumns as $field) {
            $table->head[] = ${$field};
        }

        $table->colclasses[] = 'centeralign';
        $table->head[] = "";
        $table->colclasses[] = 'centeralign';

        $table->id = "users";

        foreach ($users as $user) {
            $fullname = fullname($user, true);

            $row = [];
            $row[] = "<a href=\"/user/view.php?id=$user->id&amp;course=$site->id\">$fullname</a>";
            foreach ($extracolumns as $field) {
                $row[] = s($user->{$field});
            }
            $table->data[] = $row;
        }

        if (!empty($table)) {
            echo html_writer::start_tag('div', ['class' => 'no-overflow']);
            echo html_writer::table($table);
            echo html_writer::end_tag('div');
            echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);
        }
    }

    /**
     * Print the action form.
     */
    public function display_action() {
        $this->_actionform->display();
    }


}
