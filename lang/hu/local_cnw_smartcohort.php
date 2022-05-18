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

$string['pluginname'] = 'Smart Cohort';
$string['description'] = 'A Smart Cohort Modullal különböző szűrési feltételekkel lehet felhasználókat globális csoportokhoz rendelni. A "Név" és a "Globális csoport" mezők kötelezően kitöltendőek. Ha a beállított szűrési feltétel teljesül egy felhasználóra, hozzáadásra kerül a kiválasztott globális csoporthoz. Azok a sorok, amik nem kerülnek teljesen kitöltésre, nem lesznek figyelembe véve a plugin által.';
$string['addfilter'] = 'Új szűrő hozzáadása';
$string['editfilter'] = 'Szűrő szerkesztése';
$string['delfilter'] = 'Szűrő törlése';
$string['delconfirm'] = 'Biztosan törli a \'{$a}\' szűrőt?';
$string['name'] = 'Név';
$string['email'] = 'E-mail';
$string['cohort'] = 'Globális csoport';
$string['user_field_select_default'] = '';
$string['equals'] = 'megegyezik';
$string['not_equals'] = 'nem egyezik meg';
$string['start_with'] = 'eleje';
$string['end_with'] = 'vége';
$string['contains'] = 'tartalmazza';
$string['not_contains'] = 'nem tartalmazza';
$string['filtered_users_on'] = 'Filtered users on \'{$a}\'';
$string['basic_data'] = 'Basic data';
$string['delconfirm_undo'] = 'Globális csoportba helyezés visszavonása';
$string['delconfirm_undo_desc'] = 'azok a felhasználók, akik CSAK emiatt a szűrő miatt kerültek be a globális csoportba, eltávolításra kerülnek belőle';
$string['delconfirm_keep'] = 'Globális csoportba helyezés megtartása';
$string['delconfirm_keep_desc'] = 'a szűrő által globális csoportba került felhasználók nem kerülnek eltávolításra belőle';
$string['delete_confirm_1'] = 'Folytatás (globális csoportba helyezés visszavonása)';
$string['delete_confirm_2'] = 'Folytatás (globális csoportba helyezés megtartása)';
$string['rules'] = 'Feltételek';
$string['userfieldno'] = 'Userfield {no}';
$string['operatorno'] = 'Operator {no}';
$string['ruleno'] = 'Feltétel {no}';
$string['valueno'] = 'Value {no}';
$string['logicaloperatorno'] = 'AND/OR {no}';
$string['affected_users'] = 'Érintett felhasználók';
$string['no_data'] = 'Nincsenek szűrők';
$string['create_filter'] = 'Szűrő létrehozása';
$string['filters'] = 'Szűrők';
$string['edit_if'] = 'Ha';
$string['if'] = 'Ha a(z) <i>\'{$a}\'</i> mező érték(e)';
$string['and_if'] = 'és a(z) <i>\'{$a}\'</i> mező érték(e)';
$string['or_if'] = 'vagy a(z) <i>\'{$a}\'</i> mező érték(e)';
$string['to'] = 'to';
$string['no_filtered_users'] = 'Üres';
$string['all_users'] = 'Összes felhasználó';
$string['initialized'] = 'Inicializálva';
$string['yes'] = 'igen';
$string['no'] = 'folyamatban';
$string['AND'] = 'ÉS';
$string['OR'] = 'VAGY';
$string['initialize_filter_cron'] = 'Szűrők inicializálása';
$string['process_queue_cron'] = 'Létrehozott és szerkesztett felhasználók feldolgozása';
$string['affect_need_initialize'] = '';
$string['deleting'] = 'a szűrő törlésre kerül...';
$string['privacy:metadata'] = 'A Smart Cohort nem tárol semmilyen személyes adatot.';
$string['filter_is_not_exist'] = 'A szűrő nem létezik';
$string['required'] = 'Kötelező';

