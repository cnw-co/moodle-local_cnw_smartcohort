<?php


/**
 * Smart cohort
 *
 * @package     local_cnw_smartcohort
 * @copyright   CNW Rendszerintegrációs Zrt. <moodle@cnw.hu>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__.'/../lib.php');

class local_cnw_smartcohort_observer
{
    public static function user_updated(core\event\base $event)
    {
        global $DB;
        $event_data = $event->get_data();

        $data = new stdClass();
        $data->user_id = $event_data['objectid'];

        $DB->insert_record('cnw_sc_queue', $data);

    }

    public static function user_deleted(core\event\base $event)
    {
        $event_data = $event->get_data();
        smartcohort_delete_insertions($event_data['objectid']);
    }

    public static function cohort_deleted(core\event\base $event) {

        global $DB;
        $event_data = $event->get_data();


        $filters = $DB->get_records('cnw_sc_filters', ['cohort_id' => $event_data['objectid']]);

        foreach($filters as $filter) {
            $DB->delete_records('cnw_sc_rules', ['filter_id' => $filter->id]);
        }

        $DB->delete_records('cnw_sc_filters', ['cohort_id' => $event_data['objectid']]);
        $DB->delete_records('cnw_sc_user_cohort', ['cohort_id' => $event_data['objectid']]);

    }

}