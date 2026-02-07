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
 * \file    test_matieres_order.php
 * \ingroup planningproduction
 * \brief   Test file for materials order management.
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
    accessforbidden('Module de test réservé aux administrateurs');
}

if (!isModEnabled('planningproduction')) {
    accessforbidden('Module Planning Production non activé');
}

$action = GETPOST('action', 'alpha');

/*
 * Actions
 */

$planning = new PlanningProduction($db);
$tests_results = array();

if ($action == 'run_tests') {
    
    // Test 1: Vérifier que la table existe avec la colonne ordre
    $tests_results['table_structure'] = test_table_structure($db);
    
    // Test 2: Vérifier les méthodes de la classe
    $tests_results['class_methods'] = test_class_methods($planning);
    
    // Test 3: Test de récupération des matières avec ordre
    $tests_results['get_matieres_ordered'] = test_get_matieres_ordered($planning);
    
    // Test 4: Test de création d'une matière (si autorisé)
    if ($user->hasRight('planningproduction', 'planning', 'write')) {
        $tests_results['create_test_matiere'] = test_create_matiere($planning);
    }
    
    // Test 5: Test de réorganisation (si des matières existent)
    if ($user->hasRight('planningproduction', 'planning', 'write')) {
        $tests_results['reorder_test'] = test_reorder_matieres($planning);
    }
}

/*
 * View
 */

$title = $langs->trans("TestOrdreMatieres");
llxHeader('', $title, '');

print load_fiche_titre($title, '', 'fa-flask');

print '<div class="info">';
print '<strong>'.$langs->trans("TestDescription").'</strong><br>';
print $langs->trans("UtiliserTestApresInstall");
print '</div><br>';

// Bouton pour lancer les tests
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="run_tests">';
print '<input type="submit" class="button" value="'.$langs->trans("LancerTests").'">';
print '</form><br>';

// Affichage des résultats
if (!empty($tests_results)) {
    print '<h3>'.$langs->trans("ResultatsTests").'</h3>';
    
    foreach ($tests_results as $test_name => $result) {
        $status_class = $result['success'] ? 'success' : 'error';
        $status_icon = $result['success'] ? 'fa-check-circle' : 'fa-times-circle';
        
        print '<div class="'.$status_class.'" style="padding: 10px; margin: 5px 0; border-radius: 4px;">';
        print '<i class="fa '.$status_icon.'"></i> ';
        print '<strong>'.htmlspecialchars($result['name']).'</strong>: ';
        print htmlspecialchars($result['message']);
        
        if (!empty($result['details'])) {
            print '<br><small>'.htmlspecialchars($result['details']).'</small>';
        }
        print '</div>';
    }
}

// Informations actuelles sur les matières premières
print '<br><h3>'.$langs->trans("EtatActuelMatieres").'</h3>';

$matieres = $planning->getAllMatieres(true);

if ($matieres === false) {
    print '<div class="error">'.$langs->trans("ErreurRecuperation").'</div>';
} elseif (empty($matieres)) {
    print '<div class="info">'.$langs->trans("AucuneMatiereConfiguree").'</div>';
} else {
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Ordre").'</td>';
    print '<td>'.$langs->trans("CodeMP").'</td>';
    print '<td>'.$langs->trans("Stock").'</td>';
    print '<td>'.$langs->trans("DateMaj").'</td>';
    print '</tr>';
    
    foreach ($matieres as $matiere) {
        print '<tr class="oddeven">';
        print '<td>'.$matiere['ordre'].'</td>';
        print '<td><strong>'.htmlspecialchars($matiere['code_mp']).'</strong></td>';
        print '<td>'.number_format($matiere['stock'], 2).'</td>';
        print '<td>'.dol_print_date($matiere['date_maj'], 'dayhour').'</td>';
        print '</tr>';
    }
    
    print '</table>';
}

llxFooter();
$db->close();

/**
 * Test de la structure de la table
 */
function test_table_structure($db)
{
    global $langs;
    
    $sql = "DESCRIBE ".MAIN_DB_PREFIX."planningproduction_matieres";
    $resql = $db->query($sql);
    
    if (!$resql) {
        return array(
            'name' => $langs->trans('TestStructureTable'),
            'success' => false,
            'message' => $langs->trans('TableNonTrouvee'),
            'details' => 'Erreur: ' . $db->lasterror()
        );
    }
    
    $columns = array();
    while ($obj = $db->fetch_object($resql)) {
        $columns[] = $obj->Field;
    }
    
    $has_ordre = in_array('ordre', $columns);
    
    return array(
        'name' => $langs->trans('TestStructureTable'),
        'success' => $has_ordre,
        'message' => $has_ordre ? $langs->trans('ColonneOrdrePresente') : $langs->trans('ColonneOrdreManquante'),
        'details' => 'Colonnes: ' . implode(', ', $columns)
    );
}

/**
 * Test des méthodes de la classe
 */
function test_class_methods($planning)
{
    global $langs;
    
    $required_methods = array(
        'getAllMatieres',
        'getNextMatiereOrdre', 
        'updateMatiereOrdre',
        'reorderMatieres'
    );
    
    $missing_methods = array();
    
    foreach ($required_methods as $method) {
        if (!method_exists($planning, $method)) {
            $missing_methods[] = $method;
        }
    }
    
    $success = empty($missing_methods);
    
    return array(
        'name' => $langs->trans('TestMethodesClasse'),
        'success' => $success,
        'message' => $success ? $langs->trans('ToutesMethodesPresentes') : $langs->trans('MethodesManquantes'),
        'details' => $success ? '' : 'Manquantes: ' . implode(', ', $missing_methods)
    );
}

/**
 * Test de récupération des matières avec ordre
 */
function test_get_matieres_ordered($planning)
{
    try {
        $matieres = $planning->getAllMatieres(true);
        
        if ($matieres === false) {
            return array(
                'name' => 'Récupération avec ordre',
                'success' => false,
                'message' => 'Erreur lors de la récupération',
                'details' => implode(', ', $planning->errors)
            );
        }
        
        $has_ordre_field = true;
        if (!empty($matieres)) {
            $first_matiere = $matieres[0];
            $has_ordre_field = array_key_exists('ordre', $first_matiere);
        }
        
        return array(
            'name' => 'Récupération avec ordre',
            'success' => $has_ordre_field,
            'message' => $has_ordre_field ? 'Champ ordre présent dans les résultats' : 'Champ ordre manquant',
            'details' => count($matieres) . ' matières récupérées'
        );
        
    } catch (Exception $e) {
        return array(
            'name' => 'Récupération avec ordre',
            'success' => false,
            'message' => 'Exception: ' . $e->getMessage(),
            'details' => ''
        );
    }
}

/**
 * Test de création d'une matière (temporaire pour les tests)
 */
function test_create_matiere($planning)
{
    $test_code = 'TEST_ORDER_' . time();
    
    try {
        $result = $planning->createMatiere($test_code, 100);
        
        if ($result > 0) {
            // Nettoyer immédiatement
            $planning->deleteMatiere($result);
            
            return array(
                'name' => 'Création avec ordre',
                'success' => true,
                'message' => 'Création et suppression réussies',
                'details' => 'ID créé: ' . $result
            );
        } else {
            return array(
                'name' => 'Création avec ordre',
                'success' => false,
                'message' => 'Échec de la création',
                'details' => implode(', ', $planning->errors)
            );
        }
        
    } catch (Exception $e) {
        return array(
            'name' => 'Création avec ordre',
            'success' => false,
            'message' => 'Exception: ' . $e->getMessage(),
            'details' => ''
        );
    }
}

/**
 * Test de réorganisation
 */
function test_reorder_matieres($planning)
{
    try {
        $matieres = $planning->getAllMatieres(true);
        
        if (count($matieres) < 2) {
            return array(
                'name' => 'Test de réorganisation',
                'success' => true,
                'message' => 'Pas assez de matières pour tester',
                'details' => 'Au moins 2 matières nécessaires'
            );
        }
        
        // Créer un ordre inversé
        $ids = array();
        foreach ($matieres as $matiere) {
            $ids[] = $matiere['rowid'];
        }
        $reversed_ids = array_reverse($ids);
        
        // Tester la réorganisation
        $result = $planning->reorderMatieres($reversed_ids);
        
        if ($result > 0) {
            // Restaurer l'ordre original
            $planning->reorderMatieres($ids);
            
            return array(
                'name' => 'Test de réorganisation',
                'success' => true,
                'message' => 'Réorganisation réussie',
                'details' => 'Testée avec ' . count($ids) . ' matières'
            );
        } else {
            return array(
                'name' => 'Test de réorganisation',
                'success' => false,
                'message' => 'Échec de la réorganisation',
                'details' => implode(', ', $planning->errors)
            );
        }
        
    } catch (Exception $e) {
        return array(
            'name' => 'Test de réorganisation',
            'success' => false,
            'message' => 'Exception: ' . $e->getMessage(),
            'details' => ''
        );
    }
}
