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

require('../../config.php');
require_once('lib.php');

$id = optional_param('id', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_BOOL);
$show = optional_param('show', 0, PARAM_BOOL);
$hide = optional_param('hide', 0, PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$sort = optional_param('sort', 'name', PARAM_ALPHANUMEXT);
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 30, PARAM_INT);

$context = context_system::instance();
require_login();
require_capability('moodle/cohort:manage', $context);

if ($id) {
    $rule = $DB->get_record('cnw_sc_rule', array('id' => $id), '*', MUST_EXIST);
} else {
    $rule = new stdClass();
    $rule->id = 0;
    $rule->name = '';
}

if ($returnurl) {
    $returnurl = new moodle_url($returnurl);
} else {
    $returnurl = new moodle_url('/local/cnw_smartcohort');
}
$baseurl = new moodle_url('/local/cnw_smartcohort/edit.php', array('id' => $rule->id, 'sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));
$PAGE->set_context($context);
$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('admin');

navigation_node::override_active_url(new moodle_url('/local/cnw_smartcohort/index.php'));

if ($delete && $rule->id) {
    smartcohort_display_delete_form($rule, $confirm, $returnurl);
}

if ($rule->id) {
    // Edit existing.
    $strheading = get_string('editrule', 'local_cnw_smartcohort');

} else {
    // Add new.
    $strheading = get_string('addrule', 'local_cnw_smartcohort');
}

$PAGE->set_title($strheading);
$PAGE->set_heading(get_string('pluginname', 'local_cnw_smartcohort'));
$PAGE->navbar->add($strheading);

// $editform = new smartcohort_basic_form(null, array('data' => $rule, 'returnurl' => $returnurl));
$filterform = new smartcohort_filtering($rule->id, null, $baseurl);

echo $OUTPUT->header();
echo $OUTPUT->heading($strheading);

// $filterform->display_basic();
$filterform->display_add();
$filterform->display_active();
$filterform->display_table($baseurl, $sort, $dir, $page, $perpage, $context);
$filterform->display_action();

echo $OUTPUT->footer();
