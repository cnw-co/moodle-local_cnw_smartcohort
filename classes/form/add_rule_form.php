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

namespace local_cnw_smartcohort\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/cohort/lib.php');

use context_system;
use core_form\dynamic_form;
use moodle_url;
use stdClass;

/**
 * Class add_rule_form
 *
 * @package     local_cnw_smartcohort
 * @copyright   CNW Rendszerintegrációs Zrt. <moodle@cnw.hu>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_rule_form extends dynamic_form {

    /**
     * Form definition
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Name field
        $mform->addElement('text', 'name', get_string('name', 'local_cnw_smartcohort'), 'maxlength="254" size="50"');
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        // Cohort field
        $options = [];
        foreach (cohort_get_all_cohorts()['cohorts'] as $cohort) {
            $options[$cohort->id] = $cohort->name;
        }

        $mform->addElement('select', "cohort_id", get_string('cohort', 'local_cnw_smartcohort'), $options);
        $mform->addRule('cohort_id', get_string('required'), 'required', null, 'client');

    }

    protected function get_context_for_dynamic_submission(): \context {
        return context_system::instance();
    }
    protected function check_access_for_dynamic_submission(): void {
        require_capability('local/cnw_smartcohort:manage',  context_system::instance());
    }
    public function process_dynamic_submission() {
        global $DB;
        $data = $this->get_data();
        $id = $this->optional_param('id', 0, PARAM_INT);

        $scdata = new stdClass;
        $scdata->rule_id = -1;
        $scdata->name = $data->name;
        $scdata->cohort_id = $data->cohort_id;
        $DB->insert_record('cnw_sc_tmp', $scdata);

        $url = new moodle_url('/local/cnw_smartcohort/edit.php', ['id' => $id]);
        return $url->out(false);
    }
    public function set_data_for_dynamic_submission(): void {

    }
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/local/cnw_smartcohort/index.php');
    }
}
