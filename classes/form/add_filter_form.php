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

use user_add_filter_form;

/**
 * Class add_filter_form
 *
 * @package     local_cnw_smartcohort
 * @copyright   CNW Rendszerintegrációs Zrt. <moodle@cnw.hu>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_filter_form extends user_add_filter_form {

    /**
     * Form definition.
     */
    public function definition() {

        $mform =& $this->_form;
        $fields = $this->_customdata['fields'];
        $extraparams = $this->_customdata['extraparams'];
        $scdata = $this->_customdata['scdata'];
        $scfilter = $this->_customdata['scfilter'];

        if ($scdata) {
            $mform->addElement('hidden', 'name', $scdata['name']);
            $mform->addElement('hidden', 'cohort_id', $scdata['cohort_id']);
        } else {
            $mform->addElement('hidden', 'name');
            $mform->addElement('hidden', 'cohort_id');
        }

        $mform->setType('name', PARAM_RAW);
        $mform->setType('cohort_id', PARAM_RAW);

        $mform->addElement('header', 'newfilter', get_string('newfilter', 'filters'));

        foreach ($fields as $ft) {
            $ft->setupForm($mform);
        }

        // In case we want to track some page params.
        if ($extraparams) {
            foreach ($extraparams as $key => $value) {
                $mform->addElement('hidden', $key, $value);
                $mform->setType($key, PARAM_RAW);
            }
        }

        // Add buttons.
        $replacefiltersbutton = $mform->createElement('submit', 'replacefilters', get_string('replacefilters', 'filters'));
        $addfilterbutton = $mform->createElement('submit', 'addfilter', get_string('addfilter', 'filters'));
        $buttons = array_filter([
            empty($scfilter) ? null : $replacefiltersbutton,
            $addfilterbutton,
        ]);

        $mform->addGroup($buttons);

    }
}
