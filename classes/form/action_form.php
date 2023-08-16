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

use moodleform;

/**
 * Class action_form
 *
 * @package     local_cnw_smartcohort
 * @copyright   CNW Rendszerintegrációs Zrt. <moodle@cnw.hu>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class action_form extends moodleform {

    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {

        $mform =& $this->_form;

        $this->add_action_buttons();
    }

    /**
     * Form validation.
     *
     * @param mixed $data
     * @param mixed $files
     * @return array
     */
    public function validation($data, $files) {

        $scdata = $this->_customdata['scdata'];

        if (empty($scdata)) {
            return ['name' => 'required', 'cohort_id' => 'required'];
        } else {
            return [];
        }
    }
}
