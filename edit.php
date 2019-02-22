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
require_once('edit_form.php');

$id = optional_param('id', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_BOOL);
$show = optional_param('show', 0, PARAM_BOOL);
$hide = optional_param('hide', 0, PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

$context = context_system::instance();

require_login();
require_capability('moodle/cohort:manage', $context);

$category = null;
if ($id) {
    $filter = $DB->get_record('cnw_sc_filters', array('id' => $id), '*', MUST_EXIST);
} else {
    $filter = new stdClass();
    $filter->id = 0;
    $filter->name = '';
}

if ($returnurl) {
    $returnurl = new moodle_url($returnurl);
} else {
    $returnurl = new moodle_url('/local/cnw_smartcohort');
}

$PAGE->set_context($context);
$baseurl = new moodle_url('/local/cnw_smartcohort/edit.php', array('id' => $filter->id));
$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('admin');

navigation_node::override_active_url(new moodle_url('/local/cnw_smartcohort/index.php'));


if ($delete and $filter->id) {
    $PAGE->url->param('delete', 1);
    if ($confirm and confirm_sesskey()) {
        $filter->deleted_flag = $confirm;
        $DB->update_record('cnw_sc_filters', $filter);
        redirect($returnurl);
    }
    $strheading = get_string('delfilter', 'local_cnw_smartcohort');
    $PAGE->navbar->add($strheading);
    $PAGE->set_title($strheading);
    $PAGE->set_heading($COURSE->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($strheading);

    // CONFIRM
    $message = get_string('delconfirm', 'local_cnw_smartcohort', format_string($filter->name)) . '<br/><br/>';
    $message .= '<b>' . get_string('delconfirm_undo', 'local_cnw_smartcohort') . ':</b> ' . get_string('delconfirm_undo_desc', 'local_cnw_smartcohort') . '<br/><br/>';
    $message .= '<b>' . get_string('delconfirm_keep', 'local_cnw_smartcohort') . ':</b> ' . get_string('delconfirm_keep_desc', 'local_cnw_smartcohort') . '<br/>';

    $continue1 = new single_button(
        new moodle_url('/local/cnw_smartcohort/edit.php', array(
                'id' => $filter->id,
                'delete' => 1,
                'confirm' => 1,
                'sesskey' => sesskey(),
                'returnurl' => $returnurl->out_as_local_url())
        ),
        get_string('delete_confirm_1', 'local_cnw_smartcohort'), 'post', true
    );
    $continue2 = new single_button(
        new moodle_url('/local/cnw_smartcohort/edit.php', array(
                'id' => $filter->id,
                'delete' => 1,
                'confirm' => 2,
                'sesskey' => sesskey(),
                'returnurl' => $returnurl->out_as_local_url())
        ),
        get_string('delete_confirm_2', 'local_cnw_smartcohort'), 'post', true
    );
    $cancel = new single_button(new moodle_url($returnurl), get_string('cancel'), 'get');

    $attributes = [
        'role' => 'alertdialog',
        'aria-labelledby' => 'modal-header',
        'aria-describedby' => 'modal-body',
        'aria-modal' => 'true'
    ];
    $confirmOutput = $OUTPUT->box_start('generalbox modal modal-dialog modal-in-page show', 'notice2', $attributes);
    $confirmOutput .= $OUTPUT->box_start('modal-content', 'modal-content');
    $confirmOutput .= $OUTPUT->box_start('modal-header p-x-1', 'modal-header');
    $confirmOutput .= html_writer::tag('h4', get_string('confirm'));
    $confirmOutput .= $OUTPUT->box_end();
    $attributes = [
        'role' => 'alert',
        'data-aria-autofocus' => 'true'
    ];
    $confirmOutput .= $OUTPUT->box_start('modal-body', 'modal-body', $attributes);
    $confirmOutput .= html_writer::tag('p', $message);
    $confirmOutput .= $OUTPUT->box_end();
    $confirmOutput .= $OUTPUT->box_start('modal-footer', 'modal-footer');
    $confirmOutput .= html_writer::tag('div', $OUTPUT->render($continue1) . $OUTPUT->render($continue2) . $OUTPUT->render($cancel), array('class' => 'buttons'));
    $confirmOutput .= $OUTPUT->box_end();
    $confirmOutput .= $OUTPUT->box_end();
    $confirmOutput .= $OUTPUT->box_end();
    echo $confirmOutput;

    echo '<style>.modal.modal-in-page{position: static;z-index: 0;margin: 0 auto 0 auto;}</style>';

    echo $OUTPUT->footer();
    die;
}

if ($filter->id) {
    // Edit existing.
    $strheading = get_string('editfilter', 'local_cnw_smartcohort');

} else {
    // Add new.
    $strheading = get_string('addfilter', 'local_cnw_smartcohort');
}

$PAGE->set_title($strheading);
$PAGE->set_heading(get_string('pluginname', 'local_cnw_smartcohort'));
$PAGE->navbar->add($strheading);

$editform = new filter_edit_form(null, array('data' => $filter, 'returnurl' => $returnurl));

if ($editform->is_cancelled()) {
    redirect($returnurl);

} else if ($data = $editform->get_data()) {
    $oldcontextid = $context->id;

    if ($data->id) {
        smartcohort_update_filter($data);
    } else {
        $data->id = smartcohort_store_filter($data);
    }
    $data->initialized = 0;

    $DB->update_record('cnw_sc_filters', $data);


    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($strheading);

$editform->display();

echo $OUTPUT->footer();

