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
 * \file    diagnostic.php
 * \ingroup planningproduction
 * \brief   Script de diagnostic pour vérifier l'installation du module
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

dol_include_once('/planningproduction/class/planningproduction.class.php');

$langs->loadLangs(array("admin", "planningproduction@planningproduction"));

// Disable caches
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

/**
 * Vérifier l'existence d'un fichier et retourner le résultat
 */
function checkFile($filepath, $description) {
    $fullpath = DOL_DOCUMENT_ROOT . '/custom/planningproduction/' . $filepath;
    $exists = file_exists($fullpath);
    $status = $exists ? '✅' : '❌';
    $color = $exists ? 'green' : 'red';
    
    echo "<tr><td>{$description}</td>";
    echo "<td><code>{$filepath}</code></td>";
    echo "<td style='color: {$color}; font-weight: bold;'>{$status} " . ($exists ? 'Présent' : 'MANQUANT') . "</td></tr>";
    
    return $exists;
}

/**
 * Vérifier l'existence d'une table et retourner le résultat
 */
function checkTable($tablename, $description, $db) {
    $sql = "SHOW TABLES LIKE '{$tablename}'";
    $resql = $db->query($sql);
    $exists = ($resql && $db->num_rows($resql) > 0);
    
    $status = $exists ? '✅' : '❌';
    $color = $exists ? 'green' : 'red';
    
    echo "<tr><td>{$description}</td>";
    echo "<td><code>{$tablename}</code></td>";
    echo "<td style='color: {$color}; font-weight: bold;'>{$status} " . ($exists ? 'Existe' : 'MANQUANTE') . "</td></tr>";
    
    // Si la table existe, compter les enregistrements
    if ($exists) {
        $sql_count = "SELECT COUNT(*) as nb FROM {$tablename}";
        $resql_count = $db->query($sql_count);
        if ($resql_count) {
            $obj = $db->fetch_object($resql_count);
            echo "<tr><td colspan='2' style='padding-left: 30px;'>→ Nombre d'enregistrements</td>";
            echo "<td style='color: blue;'>{$obj->nb} ligne(s)</td></tr>";
        }
    }
    
    return $exists;
}

/**
 * Vérifier l'état d'un module Dolibarr
 */
function checkModule($module_name, $description) {
    global $conf;
    $enabled = isModEnabled($module_name);
    $status = $enabled ? '✅' : '❌';
    $color = $enabled ? 'green' : 'red';
    
    echo "<tr><td>{$description}</td>";
    echo "<td><code>{$module_name}</code></td>";
    echo "<td style='color: {$color}; font-weight: bold;'>{$status} " . ($enabled ? 'Activé' : 'DÉSACTIVÉ') . "</td></tr>";
    
    return $enabled;
}

/**
 * Tester la connectivité AJAX
 */
function testAjaxEndpoint($endpoint, $description) {
    $fullpath = DOL_DOCUMENT_ROOT . '/custom/planningproduction/' . $endpoint;
    $accessible = is_readable($fullpath);
    
    $status = $accessible ? '✅' : '❌';
    $color = $accessible ? 'green' : 'red';
    
    echo "<tr><td>{$description}</td>";
    echo "<td><code>{$endpoint}</code></td>";
    echo "<td style='color: {$color}; font-weight: bold;'>{$status} " . ($accessible ? 'Accessible' : 'NON ACCESSIBLE') . "</td></tr>";
    
    return $accessible;
}

/*
 * View
 */

llxHeader('', 'Diagnostic Planning Production', '');

print '<div class="fiche">';
print '<div class="titre">🔍 Diagnostic du Module Planning Production</div>';
print '<div class="tabBar">';

print '<div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #007bff; border-radius: 4px;">';
print '<strong>ℹ️ Information :</strong> Ce script vérifie l\'installation et la configuration du module Planning Production.<br>';
print 'Exécutez-le après chaque mise à jour pour vous assurer que tout fonctionne correctement.';
print '</div>';

// Vérification des fichiers critiques
print '<h3>📁 Vérification des Fichiers</h3>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>Description</td><td>Fichier</td><td>Status</td></tr>';

$files_ok = true;
$files_ok &= checkFile('ajax_matieres.php', 'Endpoint AJAX Matières Premières');
$files_ok &= checkFile('js/matieres.js', 'JavaScript Matières Premières');
$files_ok &= checkFile('class/planningproduction.class.php', 'Classe principale');
$files_ok &= checkFile('planning.php', 'Interface principale');
$files_ok &= checkFile('sql/llx_planningproduction_matieres.sql', 'Script création table matières');
$files_ok &= checkFile('sql/llx_planningproduction_matieres.key.sql', 'Script index matières');

print '</table>';

if (!$files_ok) {
    print '<div class="warning">⚠️ Certains fichiers sont manquants. Vérifiez le déploiement du module.</div>';
}

// Vérification des tables
print '<h3>🗄️ Vérification des Tables</h3>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>Description</td><td>Table</td><td>Status</td></tr>';

$tables_ok = true;
$tables_ok &= checkTable(MAIN_DB_PREFIX.'planningproduction_planning', 'Table principale du planning', $db);
$tables_ok &= checkTable(MAIN_DB_PREFIX.'planningproduction_matieres', 'Table des matières premières', $db);

print '</table>';

if (!$tables_ok) {
    print '<div class="error">❌ Certaines tables sont manquantes. Réactivez le module ou exécutez manuellement les scripts SQL.</div>';
}

// Vérification des modules dépendants
print '<h3>🔧 Vérification des Modules</h3>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>Description</td><td>Module</td><td>Status</td></tr>';

$modules_ok = true;
$modules_ok &= checkModule('planningproduction', 'Planning Production');
$modules_ok &= checkModule('commande', 'Module Commandes (requis)');

print '</table>';

// Vérification des endpoints AJAX
print '<h3>🌐 Vérification des Endpoints AJAX</h3>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>Description</td><td>Endpoint</td><td>Status</td></tr>';

$ajax_ok = true;
$ajax_ok &= testAjaxEndpoint('ajax_planning.php', 'Endpoint Planning');
$ajax_ok &= testAjaxEndpoint('ajax_matieres.php', 'Endpoint Matières Premières');

print '</table>';

// Test de la classe PlanningProduction
print '<h3>⚙️ Test de la Classe PlanningProduction</h3>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>Test</td><td>Résultat</td><td>Status</td></tr>';

try {
    $planning = new PlanningProduction($db);
    print '<tr><td>Instanciation de la classe</td><td>Succès</td><td style="color: green; font-weight: bold;">✅ OK</td></tr>';
    
    // Test des méthodes matières premières
    $matieres = $planning->getAllMatieres();
    if ($matieres !== false) {
        $nb_matieres = is_array($matieres) ? count($matieres) : 0;
        print '<tr><td>Récupération des matières premières</td><td>' . $nb_matieres . ' matière(s) trouvée(s)</td><td style="color: green; font-weight: bold;">✅ OK</td></tr>';
    } else {
        print '<tr><td>Récupération des matières premières</td><td>Échec</td><td style="color: red; font-weight: bold;">❌ ERREUR</td></tr>';
    }
    
    // Test calcul CDE EN COURS (avec code bidon)
    $test_cde = $planning->calculateCdeEnCours('TEST_CODE');
    if ($test_cde !== false) {
        print '<tr><td>Calcul commandes en cours</td><td>Fonction opérationnelle</td><td style="color: green; font-weight: bold;">✅ OK</td></tr>';
    } else {
        print '<tr><td>Calcul commandes en cours</td><td>Fonction en erreur</td><td style="color: red; font-weight: bold;">❌ ERREUR</td></tr>';
    }
    
} catch (Exception $e) {
    print '<tr><td>Instanciation de la classe</td><td>Erreur: ' . $e->getMessage() . '</td><td style="color: red; font-weight: bold;">❌ ERREUR</td></tr>';
}

print '</table>';

// Vérification des permissions utilisateur
print '<h3>👤 Permissions de l\'Utilisateur Actuel</h3>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>Permission</td><td>Status</td></tr>';

$has_read = $user->hasRight('planningproduction', 'planning', 'read');
$has_write = $user->hasRight('planningproduction', 'planning', 'write');

$read_status = $has_read ? '✅ Accordée' : '❌ Refusée';
$write_status = $has_write ? '✅ Accordée' : '❌ Refusée';
$read_color = $has_read ? 'green' : 'red';
$write_color = $has_write ? 'green' : 'red';

print '<tr><td>Lecture planning et matières premières</td><td style="color: ' . $read_color . '; font-weight: bold;">' . $read_status . '</td></tr>';
print '<tr><td>Écriture planning et modification stocks</td><td style="color: ' . $write_color . '; font-weight: bold;">' . $write_status . '</td></tr>';

print '</table>';

// Résumé global
$all_ok = $files_ok && $tables_ok && $modules_ok && $ajax_ok;

print '<h3>📋 Résumé Global</h3>';
if ($all_ok) {
    print '<div class="ok" style="padding: 20px; text-align: center; font-size: 18px;">';
    print '🎉 <strong>Installation Complète et Fonctionnelle !</strong><br><br>';
    print 'Tous les composants sont correctement installés et configurés.<br>';
    print 'Vous pouvez utiliser le module Planning Production avec la gestion des matières premières.';
    print '</div>';
} else {
    print '<div class="error" style="padding: 20px; text-align: center; font-size: 18px;">';
    print '⚠️ <strong>Problèmes Détectés</strong><br><br>';
    print 'Certains composants sont manquants ou mal configurés.<br>';
    print 'Consultez les détails ci-dessus et corrigez les problèmes signalés.';
    print '</div>';
}

// Informations système
print '<h3>ℹ️ Informations Système</h3>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>Information</td><td>Valeur</td></tr>';

print '<tr><td>Version Dolibarr</td><td>' . DOL_VERSION . '</td></tr>';
print '<tr><td>Version PHP</td><td>' . PHP_VERSION . '</td></tr>';
print '<tr><td>Module Planning Production</td><td>';
if (class_exists('modPlanningproduction')) {
    try {
        $mod = new modPlanningproduction($db);
        print $mod->version;
    } catch (Exception $e) {
        print 'Erreur lecture version';
    }
} else {
    print 'Module non chargé';
}
print '</td></tr>';

print '<tr><td>Répertoire du module</td><td>' . dol_buildpath('/planningproduction', 0) . '</td></tr>';
print '<tr><td>URL d\'accès</td><td><a href="' . dol_buildpath('/planningproduction/planning.php', 1) . '" target="_blank">' . dol_buildpath('/planningproduction/planning.php', 1) . '</a></td></tr>';

print '</table>';

// Actions suggérées
print '<h3>🔧 Actions Suggérées</h3>';
print '<div style="background: #f0f8ff; padding: 15px; border-radius: 8px; border-left: 4px solid #007bff;">';
print '<ul>';
print '<li><strong>Si tout est OK :</strong> <a href="' . dol_buildpath('/planningproduction/planning.php', 1) . '" class="button">Accéder au Planning Production</a></li>';
if (!$tables_ok) {
    print '<li><strong>Si tables manquantes :</strong> Désactiver puis réactiver le module dans Configuration → Modules</li>';
}
if (!$files_ok) {
    print '<li><strong>Si fichiers manquants :</strong> Vérifier le déploiement des fichiers du module</li>';
}
if (!$has_read || !$has_write) {
    print '<li><strong>Si permissions manquantes :</strong> Configurer les droits dans Configuration → Utilisateurs & Groupes → Groupes</li>';
}
print '<li><strong>Configuration matières :</strong> <a href="' . dol_buildpath('/planningproduction/admin/setup.php', 1) . '" class="button">Configurer les Matières Premières</a></li>';
print '</ul>';
print '</div>';

print '</div>';
print '</div>';

llxFooter();
$db->close();
