<?php
/* Copyright (C) 2024 Patrick Delcroix
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    admin/about.php
 * \ingroup planningproduction
 * \brief   About page for PlanningProduction module.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

// Libraries
dol_include_once('/planningproduction/admin/setup.php');
dol_include_once('/planningproduction/core/modules/modPlanningproduction.class.php');

// Translations
$langs->loadLangs(array("admin", "planningproduction@planningproduction"));

// Security check
if (!$user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

/*
 * View
 */

$form = new Form($db);

$title = "PlanningProductionSetup";
llxHeader('', $langs->trans($title));

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

// Configuration header
$head = planningproductionAdminPrepareHead();
print dol_get_fiche_head($head, 'about', $langs->trans($title), -1, 'planningproduction@planningproduction');

// About
$module = new modPlanningproduction($db);

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="titlefield">' . $langs->trans("Parameter") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>' . $langs->trans("Version") . '</td>';
print '<td>' . $module->version . '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>' . $langs->trans("Author") . '</td>';
print '<td>' . $module->editor_name . '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td>' . $module->description . '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>' . $langs->trans("Licence") . '</td>';
print '<td>GPL v3+</td>';
print '</tr>';

print '</table>';
print '</div>';

print '<br>';

print '<div class="clearboth"></div>';

print dol_get_fiche_end();

print '<div class="tabsAction">';
print '<div class="inline-block divButAction">';
print '<a class="butAction" href="' . dol_buildpath('/planningproduction/planning.php', 1) . '">' . $langs->trans('PlanningProduction') . '</a>';
print '</div>';
print '</div>';

llxFooter();
$db->close();
