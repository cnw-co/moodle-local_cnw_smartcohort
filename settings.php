<?php

/**
 * Smart cohort
 *
 * @package     local_cnw_smartcohort
 * @copyright   CNW Rendszerintegrációs Zrt. <moodle@cnw.hu>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


if ($hassiteconfig) {
    $button = new admin_externalpage('local_cnw_smartcohort_list', get_string('pluginname', 'local_cnw_smartcohort'), '/local/cnw_smartcohort/index.php');
    $ADMIN->add('accounts', $button);
}
