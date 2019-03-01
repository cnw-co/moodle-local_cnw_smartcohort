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

$string = [
    'pluginname' => 'Smart Cohort',
    'description' => 'A Smart Cohort modul segítségével, a meghatározott szűrési feltételek szerint a felhasználók bekerülnek a globális csoportokba. A modulban a név és a globális csoport mező kitöltése kötelező. Amennyiben a megadott feltételek igazak egy felhasználóra, akkor hozzáadásra kerülnek a globális csopothoz. Az üresen hagyott mezőket a modul nem veszi figyelembe.',
    'addfilter' => 'Új szűrő létrehozása',
    'editfilter' => 'Szűrő szerkesztése',
    'delfilter' => 'Szűrő Törlése',
    'delconfirm' => 'Tényleg törölni szeretnéd a következő szűrőt \'{$a}\'?',
    'name' => 'Név',
    'cohort' => 'Globális csoport',
    'user_field_select_default' => '',
    'equals' => 'egyenlő',
    'not_equals' => 'nem egyenlő',
    'filtered_users_on' => 'Felhasználók a következő szűrőben \'{$a}\'',
    'basic_data' => 'Alapadatok',
    'delconfirm_undo' => 'Törlés (beiratások törlésével)',
    'delconfirm_undo_desc' => 'Ebben az esetben azoknál a felhasználóknál akire igaz ez a feltétel és csak emiatt a szűrő miatt került hozzáadásra egy csoporthoz, abból a csoportból törlésre kerül.',
    'delconfirm_keep' => 'Törlés (beiratások megtartásával)',
    'delconfirm_keep_desc' => 'Ebben az esetben csak a szűrési feltétel kerül törlése, a szűrő által globális csoporthoz hozzáadott felhasználók nem kerülnek törlése a csoportból',
    'delete_confirm_1' => 'Törlés (beiratások törlésével)',
    'delete_confirm_2' => 'Törlés (beiratások megtartásával)',
    'rules' => 'Szűrési feltételek',
    'affected_users' => 'Érintett felhasználók',
    'no_data' => 'Nincs elérhető szűrő',
    'create_filter' => 'Szűrő készítése',
    'filters' => 'Szűrők',
    'if' => 'Ha a(z) <i>\'{$a}\'</i> mező értéke',
    'and_if' => 'és a(z) <i>\'{$a}\'</i> mező értéke',
    'to' => 'ezzel:',
    'no_filtered_users' => 'Nem található felhasználó a feltételek alapján',
    'all_users' => 'Összes felhasználó',
    'initialized' => 'Inicizalizált',
    'yes' => 'igen',
    'no' => 'nem',
    'initialize_filter_cron' => 'Szűrők inicializálása',
    'process_queue_cron' => 'Létrehozott és frissített felhasználók feldolgozása',
    'affect_need_initialize' => 'ismeretlen (ütemezett feladatra vár az inicializálás)',
    'deleting' => 'törlés alatt',
    'privacy:metadata' => 'A Smart Cohort kiegészítő nem tárol semmilyen személyes adatot.'
];
