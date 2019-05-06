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
$string['description'] = 'A Smart Cohort modul segítségével, a meghatározott szűrési feltételek szerint a felhasználók bekerülnek a globális csoportokba. A modulban a név és a globális csoport mező kitöltése kötelező. Amennyiben a megadott feltételek igazak egy felhasználóra, akkor hozzáadásra kerülnek a globális csopothoz. Az üresen hagyott mezőket a modul nem veszi figyelembe.';
$string['addfilter'] = 'Új szűrő létrehozása';
$string['editfilter'] = 'Szűrő szerkesztése';
$string['delfilter'] = 'Szűrő Törlése';
$string['delconfirm'] = 'Tényleg törölni szeretnéd a következő szűrőt \'{$a}\'?';
$string['name'] = 'Név';
$string['email'] = 'E-mail';
$string['cohort'] = 'Globális csoport';
$string['user_field_select_default'] = '';
$string['equals'] = 'egyenlő';
$string['not_equals'] = 'nem egyenlő';
$string['start_with'] = 'kezdődik';
$string['end_with'] = 'végződik';
$string['filtered_users_on'] = 'Felhasználók a következő szűrőben \'{$a}\'';
$string['basic_data'] = 'Alapadatok';
$string['delconfirm_undo'] = 'Törlés (beiratások törlésével)';
$string['delconfirm_undo_desc'] = 'Ebben az esetben azoknál a felhasználóknál akire igaz ez a feltétel és csak emiatt a szűrő miatt került hozzáadásra egy csoporthoz, abból a csoportból törlésre kerül.';
$string['delconfirm_keep'] = 'Törlés (beiratások megtartásával)';
$string['delconfirm_keep_desc'] = 'Ebben az esetben csak a szűrési feltétel kerül törlése, a szűrő által globális csoporthoz hozzáadott felhasználók nem kerülnek törlése a csoportból';
$string['delete_confirm_1'] = 'Törlés (beiratások törlésével)';
$string['delete_confirm_2'] = 'Törlés (beiratások megtartásával)';
$string['rules'] = 'Szűrési feltételek';
$string['affected_users'] = 'Érintett felhasználók';
$string['no_data'] = 'Nincs elérhető szűrő';
$string['create_filter'] = 'Szűrő készítése';
$string['filters'] = 'Szűrők';
$string['if'] = 'Ha a(z) <i>\'{$a}\'</i> mező értéke';
$string['and_if'] = 'és a(z) <i>\'{$a}\'</i> mező értéke';
$string['to'] = 'ezzel:';
$string['no_filtered_users'] = 'Nem található felhasználó a feltételek alapján';
$string['all_users'] = 'Összes felhasználó';
$string['initialized'] = 'Inicizalizált';
$string['yes'] = 'igen';
$string['no'] = 'nem';
$string['initialize_filter_cron'] = 'Szűrők inicializálása';
$string['process_queue_cron'] = 'Létrehozott és frissített felhasználók feldolgozása';
$string['affect_need_initialize'] = 'ismeretlen (ütemezett feladatra vár az inicializálás)';
$string['deleting'] = 'törlés alatt';
$string['privacy:metadata'] = 'A Smart Cohort kiegészítő nem tárol semmilyen személyes adatot.';
$string['filter_is_not_exist'] = 'A szűrő nem létezik';
