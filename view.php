<?php

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

$context = context_system::instance();

require_login();
require_capability('moodle/cohort:manage', $context);


$PAGE->set_context($context);
$baseurl = new moodle_url('/local/cnw_smartcohort/view.php', array('id' => $id));
$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('admin');

navigation_node::override_active_url(new moodle_url('/local/cnw_smartcohort/index.php'));


$filter = $DB->get_record('cnw_sc_filters', ['id' => $id]);
$strheading = get_string('filtered_users_on', 'local_cnw_smartcohort', format_string($filter->name));
$PAGE->set_title($strheading);
$PAGE->set_heading("Smart Cohort");

echo $OUTPUT->header();
echo $OUTPUT->heading($strheading);

$users = smartcohort_get_users_by_filter($id);

$data = [];
foreach ($users as $user) {
    $data[] = [
        $user->firstname . ' ' . $user->lastname,
        $user->email
    ];
}

$table = new html_table();
$table->head = array('Name', 'Email');
$table->colclasses = array('', '');
$table->id = 'users';
$table->attributes['class'] = 'admintable generaltable';
if(count($data) > 0) {
    $table->data = $data;
} else {
    $table->data = [
        [
            get_string('no_filtered_users', 'local_cnw_smartcohort'), ''
        ]
    ];
}

echo html_writer::table($table);


echo $OUTPUT->footer();

