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

if ($hassiteconfig
    or has_capability('moodle/cohort:manage', context_system::instance())
    or has_capability('moodle/cohort:view', context_system::instance())) {

        $button = new admin_externalpage('local_cnw_smartcohort_list',
            get_string('pluginname', 'local_cnw_smartcohort'), $CFG->wwwroot . '/local/cnw_smartcohort/index.php', array('moodle/cohort:manage', 'moodle/cohort:view'));
        $ADMIN->add('accounts', $button);
}
