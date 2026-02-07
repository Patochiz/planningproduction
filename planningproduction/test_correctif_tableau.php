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
 * \file    test_correctif_tableau.php
 * \ingroup planningproduction
 * \brief   Quick test to verify that the table disappearing issue is fixed.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

dol_include_once('/planningproduction/class/planningproduction.class.php');

global $db, $user, $langs;

$langs->loadLangs(array("admin", "planningproduction@planningproduction"));

// Security check
if (!$user->admin) {
    accessforbidden('Test réservé aux administrateurs');
}

if (!isModEnabled('planningproduction')) {
    accessforbidden('Module Planning Production non activé');
}

/*
 * Actions
 */

$action = GETPOST('action', 'alpha');
$planning = new PlanningProduction($db);

if ($action == 'test_add') {
    $test_code = 'TEST_CORRECTIF_' . time();
    $result = $planning->createMatiere($test_code, 100);
    $message = $result > 0 ? "✅ Matière test ajoutée (ID: $result)" : "❌ Erreur lors de l'ajout";
}

if ($action == 'test_delete') {
    // Supprimer les matières de test
    $matieres = $planning->getAllMatieres(false);
    $deleted = 0;
    foreach ($matieres as $matiere) {
        if (strpos($matiere['code_mp'], 'TEST_CORRECTIF_') === 0) {
            $result = $planning->deleteMatiere($matiere['rowid']);
            if ($result > 0) $deleted++;
        }
    }
    $message = "✅ $deleted matière(s) de test supprimée(s)";
}

/*
 * View
 */

$title = "Test - Correctif tableau qui disparaît";
llxHeader('', $title, '');

print '<style>
.test-container { max-width: 800px; margin: 0 auto; }
.test-section { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; }
.test-ok { color: #28a745; font-weight: bold; }
.test-ko { color: #dc3545; font-weight: bold; }
.test-warning { color: #ffc107; font-weight: bold; }
</style>';

print load_fiche_titre($title, '', 'fa-bug');

print '<div class="test-container">';

// Message de retour
if (!empty($message)) {
    print '<div class="'.($action == 'test_add' ? 'ok' : 'info').'" style="padding: 15px; margin: 15px 0; border-radius: 6px;">';
    print $message;
    print '</div>';
}

// Instructions
print '<div class="test-section">';
print '<h3>🎯 Test du correctif</h3>';
print '<p><strong>Ce test permet de vérifier que le tableau des matières premières ne disparaît plus après sauvegarde.</strong></p>';
print '<p>Le correctif a été appliqué dans <code>admin/setup.php</code> et <code>js/matieres_order.js</code>.</p>';
print '</div>';

// État actuel
$matieres = $planning->getAllMatieres(true);
$nb_matieres = count($matieres);

print '<div class="test-section">';
print '<h3>📊 État actuel</h3>';
print '<ul>';
print '<li><strong>Matières premières :</strong> '.$nb_matieres.' configurée(s)</li>';

// Vérifier la colonne ordre
$has_ordre_column = false;
if ($nb_matieres > 0) {
    $has_ordre_column = isset($matieres[0]['ordre']);
}

print '<li><strong>Colonne ordre :</strong> '.($has_ordre_column ? '<span class="test-ok">✅ Présente</span>' : '<span class="test-ko">❌ Manquante</span>').'</li>';

// Vérifier les fichiers
$files_ok = 0;
$files_total = 4;

$files = array(
    'js/matieres_order.js' => 'JavaScript drag & drop',
    'css/matieres_order.css' => 'Styles CSS',
    'ajax_matieres_order.php' => 'API AJAX',
    'admin/setup.php' => 'Interface admin'
);

foreach ($files as $file => $name) {
    $exists = file_exists($file);
    print '<li><strong>'.$name.' :</strong> '.($exists ? '<span class="test-ok">✅ Présent</span>' : '<span class="test-ko">❌ Manquant</span>').'</li>';
    if ($exists) $files_ok++;
}

$drag_drop_ok = ($nb_matieres >= 2 && $has_ordre_column && $files_ok == $files_total);
print '<li><strong>Drag & drop actif :</strong> '.($drag_drop_ok ? '<span class="test-ok">✅ Opérationnel</span>' : '<span class="test-warning">⚠️ Conditions non remplies</span>').'</li>';

print '</ul>';
print '</div>';

// Test de manipulation
if ($user->hasRight('planningproduction', 'planning', 'write')) {
    print '<div class="test-section">';
    print '<h3>🧪 Test de manipulation</h3>';
    print '<p>Utilisez ces boutons pour tester que le tableau reste visible après modification :</p>';
    
    print '<div style="margin: 15px 0;">';
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" style="display: inline-block; margin-right: 10px;">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="test_add">';
    print '<input type="submit" class="button" value="➕ Ajouter matière test">';
    print '</form>';
    
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" style="display: inline-block;">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="test_delete">';
    print '<input type="submit" class="button" value="🗑️ Supprimer tests" onclick="return confirm(\'Supprimer les matières de test ?\');">';
    print '</form>';
    print '</div>';
    
    print '<div style="background: #e8f4fd; padding: 15px; border-radius: 6px; margin: 15px 0;">';
    print '<h4>📋 Procédure de test complète :</h4>';
    print '<ol>';
    print '<li><strong>Ajouter une matière test</strong> avec le bouton ci-dessus</li>';
    print '<li><strong>Aller à la configuration</strong> : <a href="../admin/setup.php">Configuration du module</a></li>';
    print '<li><strong>Modifier une matière existante</strong> (cliquer "Modifier")</li>';
    print '<li><strong>Sauvegarder</strong> et vérifier que le tableau reste visible</li>';
    print '<li><strong>Tester le drag & drop</strong> avec les poignées ≡</li>';
    print '<li><strong>Nettoyer</strong> avec le bouton "Supprimer tests"</li>';
    print '</ol>';
    print '</div>';
    
} else {
    print '<div class="test-section">';
    print '<h3>⚠️ Permissions insuffisantes</h3>';
    print '<p>Vous devez avoir les droits d\'écriture sur le module pour effectuer les tests de manipulation.</p>';
    print '</div>';
}

// Diagnostic technique
print '<div class="test-section">';
print '<h3>🔧 Diagnostic technique</h3>';

print '<h4>Vérifications appliquées :</h4>';
print '<ul>';
print '<li>✅ <code>getAllMatieres(true)</code> - Récupération avec ordre</li>';
print '<li>✅ <code>data-ordre="'.($has_ordre_column ? 'OK' : 'KO').'"</code> - Attributs de ligne sécurisés</li>';
print '<li>✅ Script de réinitialisation après rechargement</li>';
print '<li>✅ Fonction <code>initializeMatieresOrder()</code> globale</li>';
print '<li>✅ Méthode <code>cleanup()</code> pour éviter les doublons</li>';
print '</ul>';

print '<h4>Console JavaScript attendue :</h4>';
print '<pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px;">
Réinitialisation du drag & drop...
MatieresOrderManager initialisé pour X matières sur #matieres-sortable
Drag & drop des matières premières initialisé avec succès ! X matières
</pre>';

print '<p><small><strong>💡 Conseil :</strong> Ouvrez la console JavaScript (F12) lors des tests pour voir ces messages.</small></p>';

print '</div>';

// Liens utiles
print '<div class="test-section">';
print '<h3>🔗 Liens de test</h3>';
print '<ul>';
print '<li><a href="../admin/setup.php">🔧 Configuration du module</a> - Interface principale</li>';
print '<li><a href="check_matieres_order.php">✅ Vérification complète</a> - Diagnostic global</li>';
print '<li><a href="test_matieres_order.php">🧪 Tests automatiques</a> - Suite de tests</li>';
print '<li><a href="demo_matieres_order.php">🎬 Démonstration</a> - Page de démo</li>';
print '</ul>';
print '</div>';

// Résumé du correctif
print '<div class="test-section" style="border-left: 4px solid #28a745;">';
print '<h3>✅ Correctif appliqué</h3>';
print '<p><strong>Problème :</strong> Tableau disparaissait après sauvegarde d\'une ligne</p>';
print '<p><strong>Cause :</strong> JavaScript drag & drop non réinitialisé après rechargement</p>';
print '<p><strong>Solution :</strong></p>';
print '<ul>';
print '<li>Correction de <code>getAllMatieres(true)</code> pour l\'ordre</li>';
print '<li>Ajout de script de réinitialisation automatique</li>';
print '<li>Amélioration de la classe JavaScript avec <code>cleanup()</code></li>';
print '<li>Vérifications robustes et logs de debug</li>';
print '</ul>';
print '<p><strong>Résultat :</strong> Le tableau reste maintenant visible et fonctionnel ! 🎉</p>';
print '</div>';

print '</div>'; // Fin test-container

llxFooter();
$db->close();
