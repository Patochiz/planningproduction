<?php
/* Copyright (C) 2024 Patrick Delcroix
 *
 * Script de test pour diagnostiquer le problème des matières premières
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

print '<h1>🔍 Test Matières Premières</h1>';

// Test 1: Vérification de la table
print '<h3>1. Vérification de la table</h3>';
$sql_check = "SHOW TABLES LIKE '" . MAIN_DB_PREFIX . "planningproduction_matieres'";
$resql_check = $db->query($sql_check);

if ($resql_check && $db->num_rows($resql_check) > 0) {
    print '✅ <strong style="color: green;">Table existe</strong><br>';
    
    // Vérifier la structure
    $sql_desc = "DESCRIBE " . MAIN_DB_PREFIX . "planningproduction_matieres";
    $resql_desc = $db->query($sql_desc);
    
    if ($resql_desc) {
        print '<strong>Structure de la table :</strong><br>';
        print '<table border="1" style="border-collapse: collapse; margin: 10px 0;">';
        print '<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th></tr>';
        
        while ($obj = $db->fetch_object($resql_desc)) {
            print '<tr>';
            print '<td>' . $obj->Field . '</td>';
            print '<td>' . $obj->Type . '</td>';
            print '<td>' . $obj->Null . '</td>';
            print '<td>' . $obj->Key . '</td>';
            print '</tr>';
        }
        print '</table>';
    }
    
    // Compter les enregistrements
    $sql_count = "SELECT COUNT(*) as nb FROM " . MAIN_DB_PREFIX . "planningproduction_matieres";
    $resql_count = $db->query($sql_count);
    if ($resql_count) {
        $obj_count = $db->fetch_object($resql_count);
        print '<strong>Nombre d\'enregistrements :</strong> ' . $obj_count->nb . '<br>';
    }
    
} else {
    print '❌ <strong style="color: red;">Table n\'existe pas</strong><br>';
    print '<div style="background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 6px;">';
    print '<strong>Solution :</strong><br>';
    print '1. Aller dans Configuration → Modules<br>';
    print '2. Désactiver le module "Planning Production"<br>';
    print '3. Réactiver le module "Planning Production"<br>';
    print '4. Ou exécuter manuellement le script SQL dans phpMyAdmin :<br>';
    print '<code>/custom/planningproduction/sql/llx_planningproduction_matieres.sql</code>';
    print '</div>';
}

// Test 2: Test de la classe PlanningProduction
print '<h3>2. Test de la classe PlanningProduction</h3>';

try {
    $planning = new PlanningProduction($db);
    print '✅ <strong style="color: green;">Classe instanciée avec succès</strong><br>';
    
    // Test de la méthode getAllMatieres
    print '<strong>Test getAllMatieres() :</strong><br>';
    $matieres = $planning->getAllMatieres();
    
    if ($matieres === false) {
        print '❌ <strong style="color: red;">getAllMatieres() a retourné FALSE</strong><br>';
        if (!empty($planning->errors)) {
            print '<strong>Erreurs :</strong><br>';
            foreach ($planning->errors as $error) {
                print '- ' . $error . '<br>';
            }
        }
    } elseif (is_array($matieres)) {
        print '✅ <strong style="color: green;">getAllMatieres() a retourné ' . count($matieres) . ' matière(s)</strong><br>';
        
        if (count($matieres) > 0) {
            print '<strong>Exemple de données :</strong><br>';
            print '<pre style="background: #f5f5f5; padding: 10px; border-radius: 4px;">';
            print htmlspecialchars(print_r($matieres[0], true));
            print '</pre>';
        }
    } else {
        print '⚠️ <strong style="color: orange;">getAllMatieres() a retourné un type inattendu : ' . gettype($matieres) . '</strong><br>';
    }
    
} catch (Exception $e) {
    print '❌ <strong style="color: red;">Erreur lors de l\'instanciation : ' . $e->getMessage() . '</strong><br>';
}

// Test 3: Test SQL direct
print '<h3>3. Test SQL direct</h3>';

if ($resql_check && $db->num_rows($resql_check) > 0) {
    $sql_direct = "SELECT rowid, code_mp, stock, tms FROM " . MAIN_DB_PREFIX . "planningproduction_matieres ORDER BY code_mp ASC LIMIT 5";
    $resql_direct = $db->query($sql_direct);
    
    if ($resql_direct) {
        print '✅ <strong style="color: green;">Requête SQL directe réussie</strong><br>';
        
        if ($db->num_rows($resql_direct) > 0) {
            print '<table border="1" style="border-collapse: collapse; margin: 10px 0;">';
            print '<tr><th>ID</th><th>Code MP</th><th>Stock</th><th>Date MàJ</th></tr>';
            
            while ($obj = $db->fetch_object($resql_direct)) {
                print '<tr>';
                print '<td>' . $obj->rowid . '</td>';
                print '<td>' . $obj->code_mp . '</td>';
                print '<td>' . $obj->stock . '</td>';
                print '<td>' . $obj->tms . '</td>';
                print '</tr>';
            }
            print '</table>';
        } else {
            print '<strong>Aucun enregistrement trouvé</strong><br>';
            print '<div style="background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 6px;">';
            print '<strong>Solution :</strong> Ajouter quelques matières premières pour tester<br>';
            print 'Vous pouvez exécuter ce script SQL dans phpMyAdmin :<br>';
            print '<code>/custom/planningproduction/sql/data_example_matieres.sql</code>';
            print '</div>';
        }
    } else {
        print '❌ <strong style="color: red;">Erreur SQL : ' . $db->lasterror() . '</strong><br>';
    }
} else {
    print '⏩ Table n\'existe pas, test SQL ignoré<br>';
}

// Test 4: Test des permissions
print '<h3>4. Test des permissions</h3>';

if ($user->hasRight('planningproduction', 'planning', 'read')) {
    print '✅ <strong style="color: green;">Permission de lecture OK</strong><br>';
} else {
    print '❌ <strong style="color: red;">Permission de lecture manquante</strong><br>';
}

if ($user->hasRight('planningproduction', 'planning', 'write')) {
    print '✅ <strong style="color: green;">Permission d\'écriture OK</strong><br>';
} else {
    print '❌ <strong style="color: red;">Permission d\'écriture manquante</strong><br>';
}

// Actions recommandées
print '<h3>5. Actions Recommandées</h3>';

print '<div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">';
print '<strong>Si la table n\'existe pas :</strong><br>';
print '1. <a href="/admin/modules.php" class="button">Aller aux Modules</a><br>';
print '2. Désactiver "Planning Production"<br>';
print '3. Réactiver "Planning Production"<br><br>';

print '<strong>Si la table existe mais est vide :</strong><br>';
print '1. <a href="' . dol_buildpath('/planningproduction/admin/setup.php', 1) . '" class="button">Aller à la Configuration</a><br>';
print '2. Ajouter quelques matières premières manuellement<br><br>';

print '<strong>Si tout semble OK :</strong><br>';
print '1. <a href="' . dol_buildpath('/planningproduction/admin/setup.php', 1) . '" class="button">Retourner à la Configuration</a><br>';
print '2. Le tableau devrait maintenant s\'afficher<br>';
print '</div>';

llxFooter();
$db->close();
