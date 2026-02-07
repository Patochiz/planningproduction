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
 * \file    test_permissions.php
 * \ingroup planningproduction
 * \brief   Script de test des permissions du module
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

// Security check
if (!$user->admin) {
    accessforbidden('Accès réservé aux administrateurs');
}

$langs->loadLangs(array("admin", "planningproduction@planningproduction"));

print '<h1>🔐 Test des Permissions - Planning Production</h1>';

print '<div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0;">';
print '<h3>📋 Résultats des Tests</h3>';

// Test 1: Vérification de l'existence du module
print '<h4>1. Module Planning Production</h4>';
if (isModEnabled('planningproduction')) {
    print '✅ <strong style="color: green;">Module activé</strong><br>';
} else {
    print '❌ <strong style="color: red;">Module NON activé</strong><br>';
}

// Test 2: Vérification des permissions utilisateur actuel
print '<h4>2. Permissions de l\'utilisateur actuel (' . $user->login . ')</h4>';

// Test avec l'ancienne méthode (qui causait le problème)
print '<strong>Ancienne méthode (défectueuse) :</strong><br>';
if (isset($user->rights->planningproduction->planning->read)) {
    print '✅ Lecture (ancienne méthode) : OUI<br>';
} else {
    print '❌ Lecture (ancienne méthode) : NON<br>';
}

if (isset($user->rights->planningproduction->planning->write)) {
    print '✅ Écriture (ancienne méthode) : OUI<br>';
} else {
    print '❌ Écriture (ancienne méthode) : NON<br>';
}

print '<br><strong>Nouvelle méthode (corrigée) :</strong><br>';
if ($user->hasRight('planningproduction', 'planning', 'read')) {
    print '✅ <strong style="color: green;">Lecture (nouvelle méthode) : OUI</strong><br>';
} else {
    print '❌ <strong style="color: red;">Lecture (nouvelle méthode) : NON</strong><br>';
}

if ($user->hasRight('planningproduction', 'planning', 'write')) {
    print '✅ <strong style="color: green;">Écriture (nouvelle méthode) : OUI</strong><br>';
} else {
    print '❌ <strong style="color: red;">Écriture (nouvelle méthode) : NON</strong><br>';
}

// Test 3: Vérification de la structure des droits
print '<h4>3. Structure des Droits</h4>';
print '<strong>Droits complets de l\'utilisateur :</strong><br>';
print '<pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; font-size: 12px;">';

// Afficher seulement les droits planningproduction
if (isset($user->rights->planningproduction)) {
    print "user->rights->planningproduction existe :\n";
    var_dump($user->rights->planningproduction);
} else {
    print "user->rights->planningproduction N'EXISTE PAS\n";
}

print '</pre>';

// Test 4: Test avec les fonctions Dolibarr
print '<h4>4. Tests Fonctionnels</h4>';

// Tester la méthode hasRight directement
$test_results = array();
$test_results['read'] = $user->hasRight('planningproduction', 'planning', 'read');
$test_results['write'] = $user->hasRight('planningproduction', 'planning', 'write');

foreach ($test_results as $type => $result) {
    if ($result) {
        print "✅ <strong style=\"color: green;\">hasRight('planningproduction', 'planning', '{$type}') = TRUE</strong><br>";
    } else {
        print "❌ <strong style=\"color: red;\">hasRight('planningproduction', 'planning', '{$type}') = FALSE</strong><br>";
    }
}

// Test 5: Informations de debug
print '<h4>5. Informations de Debug</h4>';
print '<strong>Version Dolibarr :</strong> ' . DOL_VERSION . '<br>';
print '<strong>Utilisateur :</strong> ' . $user->login . ' (ID: ' . $user->id . ')<br>';
print '<strong>Admin :</strong> ' . ($user->admin ? 'OUI' : 'NON') . '<br>';

// Test 6: Recommandations
print '<h4>6. Recommandations</h4>';

if (!$test_results['read'] && !$test_results['write']) {
    print '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 6px;">';
    print '<strong>⚠️ PROBLÈME DÉTECTÉ :</strong><br>';
    print 'Aucun droit trouvé pour ce module. Actions à effectuer :<br>';
    print '<ol>';
    print '<li>Aller dans <strong>Configuration → Utilisateurs & Groupes → Groupes</strong></li>';
    print '<li>Modifier le groupe de cet utilisateur</li>';
    print '<li>Cocher les permissions "<strong>Planning Production</strong>"</li>';
    print '<li>Sauvegarder</li>';
    print '</ol>';
    print '</div>';
} elseif ($test_results['read'] && !$test_results['write']) {
    print '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 6px;">';
    print '<strong>✅ LECTURE OK</strong> - Droits de lecture accordés<br>';
    print '<strong>ℹ️ INFO :</strong> Pour modifier les matières premières, accordez aussi les droits d\'écriture.';
    print '</div>';
} else {
    print '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 6px;">';
    print '<strong>✅ TOUS LES DROITS OK</strong> - Lecture et écriture accordées<br>';
    print 'Vous pouvez maintenant utiliser toutes les fonctionnalités du module.';
    print '</div>';
}

print '</div>';

// Liens utiles
print '<div style="margin: 20px 0;">';
print '<h3>🔗 Liens Utiles</h3>';
print '<a href="' . dol_buildpath('/planningproduction/admin/setup.php', 1) . '" class="button">Configuration du Module</a> ';
print '<a href="' . dol_buildpath('/planningproduction/planning.php', 1) . '" class="button">Planning Production</a> ';
print '<a href="' . dol_buildpath('/planningproduction/diagnostic.php', 1) . '" class="button">Diagnostic Complet</a>';
print '</div>';

print '<div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0; font-size: 12px; color: #666;">';
print '<strong>Note technique :</strong> Le problème était lié à la méthode de vérification des permissions. ';
print 'L\'ancienne méthode <code>$user->rights->planningproduction->planning->write</code> ne fonctionne pas correctement dans certaines versions de Dolibarr. ';
print 'La nouvelle méthode <code>$user->hasRight(\'planningproduction\', \'planning\', \'write\')</code> est plus fiable et compatible.';
print '</div>';

llxFooter();
$db->close();
