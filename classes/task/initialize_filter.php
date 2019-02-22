<?php
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

class initialize_filter extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('initialize_filter_cron', 'local_cnw_smartcohort');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;
        $filters = $DB->get_records('cnw_sc_filters', ['initialized' => 0]);

        foreach($filters as $filter) {
            smartcohort_run_filter($filter);
            $filter->initialized = 1;
            $DB->update_record('cnw_sc_filters', $filter);
        }

        $filters = smartcohort_get_filters(true);

        foreach($filters as $filter) {
            if($filter->deleted_flag != "0") {
                smartcohort_delete_filter($filter, $filter->deleted_flag);
            }
        }

    }

}