<?php

/**
 * Smart cohort
 *
 * @package     local_cnw_smartcohort
 * @copyright   CNW Rendszerintegrációs Zrt. <moodle@cnw.hu>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


$observers = array(

    array(
        'eventname'   => '\core\event\user_updated',
        'callback'    => 'local_cnw_smartcohort_observer::user_updated',
    ),

    array(
        'eventname'   => '\core\event\user_created',
        'callback'    => 'local_cnw_smartcohort_observer::user_updated',
    ),

    array(
        'eventname'   => '\core\event\user_deleted',
        'callback'    => 'local_cnw_smartcohort_observer::user_deleted',
    ),
    array(
        'eventname'   => '\core\event\cohort_deleted',
        'callback'    => 'local_cnw_smartcohort_observer::cohort_deleted'
    )

);