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

require_once(__DIR__ . '/../lib.php');

/**
 * Class event observer for smart cohort.
 */
class local_cnw_smartcohort_observer {

    /**
     * Insert users into smart cohort queue upon user update event.
     *
     * @param \core\event\base $event
     */
    public static function user_updated(core\event\base $event) {
        global $DB;
        $eventdata = $event->get_data();

        $data = new stdClass();
        $data->user_id = $eventdata['objectid'];

        $DB->insert_record('cnw_sc_queue', $data);
    }

    /**
     * Delete smart cohort insertions upon user delete event.
     *
     * @param \core\event\base $event
     */
    public static function user_deleted(core\event\base $event) {
        $eventdata = $event->get_data();
        smartcohort_delete_insertions($eventdata['objectid']);
    }

    /**
     * Delete smart cohort rule upon cohort delete event.
     *
     * @param \core\event\base $event
     */
    public static function cohort_deleted(core\event\base $event) {
        global $DB;
        $eventdata = $event->get_data();

        $rules = $DB->get_records('cnw_sc_rule', ['cohort_id' => $eventdata['objectid']]);

        foreach ($rules as $rule) {
            $DB->delete_records('cnw_sc_filter', ['rule_id' => $rule->id]);
        }

        $DB->delete_records('cnw_sc_rule', ['cohort_id' => $eventdata['objectid']]);
        $DB->delete_records('cnw_sc_user_cohort', ['cohort_id' => $eventdata['objectid']]);
    }

}
