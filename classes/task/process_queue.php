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


namespace local_cnw_smartcohort\task;

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../../lib.php';

class process_queue extends \core\task\scheduled_task
{

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name()
    {
        return get_string('process_queue_cron', 'local_cnw_smartcohort');
    }

    /**
     * Execute the task.
     */
    public function execute()
    {
        global $DB;

        $users = $DB->get_records('cnw_sc_queue');

        foreach ($users as $user) {
            smartcohort_run_filters($user->user_id);

            $DB->delete_records('cnw_sc_queue', ['user_id' => $user->user_id]);
        }

    }

}