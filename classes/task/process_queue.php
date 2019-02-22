<?php
/**
 * Created by PhpStorm.
 * User: szabolcs
 * Date: 2019.02.22.
 * Time: 9:24
 */

namespace local_cnw_smartcohort\task;

require_once __DIR__ . '/../../lib.php';

class process_queue extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('process_queue_cron', 'local_cnw_smartcohort');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;
        //smartcohort_run_filters($event_data['objectid']);
        $users = $DB->get_records('cnw_sc_queue');

        foreach($users as $user) {
            smartcohort_run_filters($user->user_id);

            $DB->delete_records('cnw_sc_queue', ['user_id' => $user->user_id]);
        }

    }

}