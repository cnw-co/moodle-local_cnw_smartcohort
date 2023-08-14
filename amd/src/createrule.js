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
 * TODO
 *
 * @module      TODO
 * @copyright   TODO
 * @license     TODO
 */

import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';

const SELECTORS = {
    actions: {
        ruleCreate: '[data-action="rule-create"]'
    }
};

export const init = () => {
    document.addEventListener('click', event => {
        const ruleCreate = event.target.closest(SELECTORS.actions.ruleCreate);
        if (ruleCreate) {
            event.preventDefault();
            const ruleModal = createRuleModal(event.target, getString('addrule', 'local_cnw_smartcohort'));
            ruleModal.addEventListener(ruleModal.events.FORM_SUBMITTED, event => {
                window.location.href = event.detail;
            });
            ruleModal.show();
        }

    });
};

/**
 * Return modal instance
 *
 * @param {EventTarget} triggerElement
 * @param {Promise} modalTitle
 * @param {String} formClass
 * @param {Object} formArgs
 * @return {ModalForm}
 */
const createModalForm = (triggerElement, modalTitle, formClass, formArgs) => {
    return new ModalForm({
        modalConfig: {
            title: modalTitle,
        },
        formClass: formClass,
        args: formArgs,
        saveButtonText: getString('save', 'moodle'),
        returnFocus: triggerElement,
    });
};

/**
 * Return report modal instance
 *
 * @param {EventTarget} triggerElement
 * @param {Promise} modalTitle
 * @param {Number} reportId
 * @return {ModalForm}
 */
export const createRuleModal = (triggerElement, modalTitle, reportId = 0) => {
    return createModalForm(triggerElement, modalTitle, 'local_cnw_smartcohort\\form\\add_rule_form', {
        id: reportId,
    });
};
