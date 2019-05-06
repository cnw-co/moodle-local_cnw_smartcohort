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

$string['pluginname'] = 'Smart Cohort';
$string['description'] = 'The Smart Cohort Module allows authorized users to define filtering criteria and to specify which cohort the filtered users would be added to. Fields "Name" and "Cohort" are required in the module. If the set criteria are true for the user, they get added to the cohort. Fields left blank are ignored by the plugin.';
$string['addfilter'] = 'Create new filter';
$string['editfilter'] = 'Edit filter';
$string['delfilter'] = 'Delete filter';
$string['delconfirm'] = 'Do you really want to delete filter \'{$a}\'?';
$string['name'] = 'Name';
$string['email'] = 'E-mail';
$string['cohort'] = 'Cohort';
$string['user_field_select_default'] = '';
$string['equals'] = 'equal';
$string['not_equals'] = 'not equal';
$string['start_with'] = 'begins with';
$string['end_with'] = 'ends with';
$string['filtered_users_on'] = 'Filtered users on \'{$a}\'';
$string['basic_data'] = 'Basic data';
$string['delconfirm_undo'] = 'Undo cohort insertions';
$string['delconfirm_undo_desc'] = 'pressing this button removes users who have been added to a cohort exclusively because of this filter (and no other filters apply to them) from the cohort.';
$string['delconfirm_keep'] = 'Keep cohort insertions';
$string['delconfirm_keep_desc'] = 'pressing this button keeps users who have been added to a cohort by the filter in the cohort.';
$string['delete_confirm_1'] = 'Continue (undo cohort insertions)';
$string['delete_confirm_2'] = 'Continue (keep cohort insertions)';
$string['rules'] = 'Rules';
$string['affected_users'] = 'Affected users';
$string['no_data'] = 'No filters available';
$string['create_filter'] = 'Create filter';
$string['filters'] = 'Filters';
$string['if'] = 'If the <i>\'{$a}\'</i> field\'s value is';
$string['and_if'] = 'and the <i>\'{$a}\'</i> field\'s value is';
$string['to'] = 'to';
$string['no_filtered_users'] = 'Empty';
$string['all_users'] = 'All users';
$string['initialized'] = 'Initialized';
$string['yes'] = 'yes';
$string['no'] = 'in progress';
$string['initialize_filter_cron'] = 'Initialize filters';
$string['process_queue_cron'] = 'Process created&edited users';
$string['affect_need_initialize'] = '';
$string['deleting'] = 'filter is being deleted...';
$string['privacy:metadata'] = 'Smart Cohort doesn\'t store any personal data.';
$string['filter_is_not_exist'] = 'Filter is not exist';

