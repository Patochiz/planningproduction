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
 * \file    install_matieres_order.php
 * \ingroup planningproduction
 * \brief   Installation script for materials order management feature.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

dol_include_once('/planningproduction/class/planningproduction.class.php');

global $db, $user, $langs, $conf;

$langs->loadLangs(array("admin", "planningproduction@planningproduction"));

// Security check
if (!$user->admin) {
    accessforbidden('Script réservé aux administrateurs');
}

if (!isModEnabled('planningproduction')) {
    accessforbidden('Module Planning Production non activé');
}

$action = GETPOST('action', 'alpha');
$step = GETPOST('step', 'int');

/*
 * Actions
 */

$installation_result = array();
$current_step = $step ? $step : 1;

if ($action == 'install') {
    switch ($current_step) {
        case 1:
            $installation_result = install_database_structure($db);
            if ($installation_result['success']) {
                $current_step = 2;
            }
            break;
            
        case 2:
            $installation_result = update_existing_records($db);
            if ($installation_result['success']) {
                $current_step = 3;
            }
            break;
            
        case 3:
            $installation_result = verify_installation($db);
            if ($installation_result['success']) {
                $current_step = 4; // Installation terminée
            }
            break;
    }
}

if ($action == 'uninstall') {
    $installation_result = uninstall_database_structure($db);
}

/*
 * View
 */

$title = "Installation - Gestion de l'ordre des Matières Premières";
llxHeader('', $title, '');

print load_fiche_titre($title, '', 'fa-download');

// Afficher les informations sur le module
print '<div class="info">';
print '<h3>Fonctionnalité : Réorganisation des Matières Premières</h3>';
print '<p>Cette installation ajoute la possibilité de réorganiser l\'ordre d\'affichage des matières premières par drag & drop.</p>';
print '<ul>';
print '<li>✅ Ajout de la colonne "ordre" à la table des matières premières</li>';
print '<li>✅ Interface de glisser-déposer intuitive</li>';
print '<li>✅ Sauvegarde automatique de l\'ordre</li>';
print '<li>✅ Compatible avec les installations existantes</li>';
print '</ul>';
print '</div><br>';

// Progress bar
print '<div style="margin: 20px 0;">';
print '<h4>Progression de l\'installation</h4>';
print '<div style="width: 100%; height: 20px; background-color: #f0f0f0; border-radius: 10px; overflow: hidden;">';
$progress = ($current_step - 1) * 25;
if ($current_step >= 4) $progress = 100;
print '<div style="width: '.$progress.'%; height: 100%; background: linear-gradient(45deg, #4caf50, #66bb6a); transition: width 0.3s ease;"></div>';
print '</div>';
print '<p>Étape '.$current_step.' sur 4';
if ($current_step >= 4) {
    print ' - ✅ <strong>Installation terminée</strong>';
} else {
    print ' - En cours...';
}
print '</p>';
print '</div>';

// Affichage du résultat de l'action précédente
if (!empty($installation_result)) {
    $status_class = $installation_result['success'] ? 'ok' : 'error';
    $status_icon = $installation_result['success'] ? 'fa-check-circle' : 'fa-times-circle';
    
    print '<div class="'.$status_class.'" style="padding: 15px; margin: 10px 0; border-radius: 6px;">';
    print '<i class="fa '.$status_icon.'"></i> ';
    print '<strong>'.htmlspecialchars($installation_result['title']).'</strong><br>';
    print htmlspecialchars($installation_result['message']);
    
    if (!empty($installation_result['details'])) {
        print '<br><small>'.nl2br(htmlspecialchars($installation_result['details'])).'</small>';
    }
    print '</div>';
}

// Affichage des étapes d'installation
if ($current_step < 4) {
    print '<div style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin: 20px 0;">';
    
    switch ($current_step) {
        case 1:
            print '<h4>Étape 1 : Structure de la base de données</h4>';
            print '<p>Cette étape ajoute la colonne "ordre" à la table des matières premières et crée l\'index nécessaire.</p>';
            print '<p><strong>Actions :</strong></p>';
            print '<ul>';
            print '<li>ALTER TABLE llx_planningproduction_matieres ADD COLUMN ordre integer DEFAULT 0 NOT NULL</li>';
            print '<li>Création de l\'index sur la colonne ordre</li>';
            print '</ul>';
            break;
            
        case 2:
            print '<h4>Étape 2 : Mise à jour des données existantes</h4>';
            print '<p>Cette étape met à jour les enregistrements existants avec des valeurs d\'ordre par défaut.</p>';
            print '<p><strong>Actions :</strong></p>';
            print '<ul>';
            print '<li>Attribution d\'un ordre séquentiel aux matières existantes</li>';
            print '<li>Vérification de la cohérence des données</li>';
            print '</ul>';
            break;
            
        case 3:
            print '<h4>Étape 3 : Vérification de l\'installation</h4>';
            print '<p>Cette étape vérifie que l\'installation s\'est correctement déroulée.</p>';
            print '<p><strong>Vérifications :</strong></p>';
            print '<ul>';
            print '<li>Structure de la table mise à jour</li>';
            print '<li>Données cohérentes</li>';
            print '<li>Méthodes PHP disponibles</li>';
            print '</ul>';
            break;
    }
    
    print '</div>';
    
    // Bouton pour continuer l'installation
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="install">';
    print '<input type="hidden" name="step" value="'.$current_step.'">';
    print '<input type="submit" class="button" value="Étape '.$current_step.' : Continuer l\'installation" style="font-size: 16px; padding: 10px 20px;">';
    print '</form>';
    
} else {
    // Installation terminée
    print '<div class="ok" style="padding: 20px; margin: 20px 0; border-radius: 6px; text-align: center;">';
    print '<i class="fa fa-check-circle" style="font-size: 24px; color: #4caf50;"></i>';
    print '<h3 style="color: #4caf50; margin: 10px 0;">Installation terminée avec succès !</h3>';
    print '<p>La fonctionnalité de réorganisation des matières premières est maintenant disponible.</p>';
    print '</div>';
    
    // Actions post-installation
    print '<div style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin: 20px 0;">';
    print '<h4>Étapes suivantes</h4>';
    print '<ol>';
    print '<li><strong>Tester la fonctionnalité</strong> : <a href="test_matieres_order.php" class="button">Lancer les tests</a></li>';
    print '<li><strong>Utiliser la fonctionnalité</strong> : Aller dans Configuration > Modules > Planning Production > Configuration</li>';
    print '<li><strong>Documentation</strong> : Consulter le fichier docs/MATIERES_ORDER.md</li>';
    print '</ol>';
    print '</div>';
}

// Section de désinstallation (pour les développeurs)
if ($user->admin) {
    print '<hr style="margin: 40px 0;">';
    print '<h3 style="color: #f44336;">Zone de développement</h3>';
    print '<div class="warning" style="padding: 15px; border-radius: 6px;">';
    print '<strong>⚠️ Attention :</strong> Cette section est réservée aux développeurs pour désinstaller la fonctionnalité.';
    print '</div>';
    
    if ($action != 'uninstall') {
        print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" onsubmit="return confirm(\'Êtes-vous sûr de vouloir désinstaller cette fonctionnalité ? Cela supprimera la colonne ordre de la base de données.\');">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="action" value="uninstall">';
        print '<input type="submit" class="button" value="Désinstaller la fonctionnalité" style="background-color: #f44336; color: white;">';
        print '</form>';
    }
}

llxFooter();
$db->close();

/**
 * Install database structure
 */
function install_database_structure($db)
{
    global $conf;
    
    try {
        // Vérifier si la colonne existe déjà
        $sql_check = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."planningproduction_matieres LIKE 'ordre'";
        $resql = $db->query($sql_check);
        
        if ($resql && $db->num_rows($resql) > 0) {
            return array(
                'success' => true,
                'title' => 'Structure de la base de données',
                'message' => 'La colonne "ordre" existe déjà',
                'details' => 'Aucune modification nécessaire'
            );
        }
        
        // Ajouter la colonne ordre
        $sql = "ALTER TABLE ".MAIN_DB_PREFIX."planningproduction_matieres ADD COLUMN ordre integer DEFAULT 0 NOT NULL";
        $resql = $db->query($sql);
        
        if (!$resql) {
            throw new Exception('Erreur lors de l\'ajout de la colonne : ' . $db->lasterror());
        }
        
        // Ajouter l'index
        $sql_index = "ALTER TABLE ".MAIN_DB_PREFIX."planningproduction_matieres ADD INDEX idx_planningproduction_matieres_ordre (ordre)";
        $resql_index = $db->query($sql_index);
        
        if (!$resql_index) {
            // L'index n'est pas critique, on continue
            $index_warning = 'Attention : Index non créé (' . $db->lasterror() . ')';
        }
        
        $details = 'Colonne "ordre" ajoutée avec succès';
        if (isset($index_warning)) {
            $details .= "\n" . $index_warning;
        } else {
            $details .= "\nIndex créé avec succès";
        }
        
        return array(
            'success' => true,
            'title' => 'Structure de la base de données',
            'message' => 'Colonne "ordre" ajoutée avec succès',
            'details' => $details
        );
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'title' => 'Erreur - Structure de la base de données',
            'message' => $e->getMessage(),
            'details' => ''
        );
    }
}

/**
 * Update existing records
 */
function update_existing_records($db)
{
    try {
        // Compter les enregistrements sans ordre (ordre = 0)
        $sql_count = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."planningproduction_matieres WHERE ordre = 0";
        $resql = $db->query($sql_count);
        
        if (!$resql) {
            throw new Exception('Erreur lors du comptage : ' . $db->lasterror());
        }
        
        $obj = $db->fetch_object($resql);
        $nb_to_update = (int) $obj->nb;
        
        if ($nb_to_update == 0) {
            return array(
                'success' => true,
                'title' => 'Mise à jour des données existantes',
                'message' => 'Aucun enregistrement à mettre à jour',
                'details' => 'Tous les enregistrements ont déjà un ordre défini'
            );
        }
        
        // Récupérer le prochain ordre disponible
        $sql_max = "SELECT MAX(ordre) as max_ordre FROM ".MAIN_DB_PREFIX."planningproduction_matieres";
        $resql_max = $db->query($sql_max);
        
        $next_ordre = 1;
        if ($resql_max && $db->num_rows($resql_max)) {
            $obj_max = $db->fetch_object($resql_max);
            $next_ordre = ((int) $obj_max->max_ordre) + 1;
        }
        
        // Mettre à jour les enregistrements un par un pour éviter les conflits
        $sql_records = "SELECT rowid FROM ".MAIN_DB_PREFIX."planningproduction_matieres WHERE ordre = 0 ORDER BY rowid ASC";
        $resql_records = $db->query($sql_records);
        
        if (!$resql_records) {
            throw new Exception('Erreur lors de la récupération des enregistrements : ' . $db->lasterror());
        }
        
        $updated = 0;
        while ($obj_record = $db->fetch_object($resql_records)) {
            $sql_update = "UPDATE ".MAIN_DB_PREFIX."planningproduction_matieres ";
            $sql_update .= "SET ordre = ".$next_ordre." ";
            $sql_update .= "WHERE rowid = ".$obj_record->rowid;
            
            $resql_update = $db->query($sql_update);
            if ($resql_update) {
                $updated++;
                $next_ordre++;
            }
        }
        
        return array(
            'success' => true,
            'title' => 'Mise à jour des données existantes',
            'message' => $updated . ' enregistrement(s) mis à jour',
            'details' => 'Ordre séquentiel attribué aux matières existantes'
        );
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'title' => 'Erreur - Mise à jour des données',
            'message' => $e->getMessage(),
            'details' => ''
        );
    }
}

/**
 * Verify installation
 */
function verify_installation($db)
{
    try {
        $checks = array();
        $all_success = true;
        
        // Vérifier la structure de la table
        $sql_structure = "DESCRIBE ".MAIN_DB_PREFIX."planningproduction_matieres";
        $resql_structure = $db->query($sql_structure);
        
        if (!$resql_structure) {
            throw new Exception('Table non trouvée');
        }
        
        $columns = array();
        while ($obj = $db->fetch_object($resql_structure)) {
            $columns[] = $obj->Field;
        }
        
        $has_ordre = in_array('ordre', $columns);
        $checks[] = array(
            'name' => 'Colonne "ordre"',
            'success' => $has_ordre,
            'message' => $has_ordre ? 'Présente' : 'Manquante'
        );
        
        if (!$has_ordre) $all_success = false;
        
        // Vérifier les données
        $sql_data = "SELECT COUNT(*) as nb, MIN(ordre) as min_ordre, MAX(ordre) as max_ordre FROM ".MAIN_DB_PREFIX."planningproduction_matieres";
        $resql_data = $db->query($sql_data);
        
        if ($resql_data && $db->num_rows($resql_data)) {
            $obj_data = $db->fetch_object($resql_data);
            $nb_records = (int) $obj_data->nb;
            $min_ordre = (int) $obj_data->min_ordre;
            $max_ordre = (int) $obj_data->max_ordre;
            
            $data_ok = ($nb_records == 0 || ($min_ordre > 0 && $max_ordre >= $min_ordre));
            $checks[] = array(
                'name' => 'Données cohérentes',
                'success' => $data_ok,
                'message' => $data_ok ? $nb_records . ' enregistrement(s), ordre de ' . $min_ordre . ' à ' . $max_ordre : 'Données incohérentes'
            );
            
            if (!$data_ok) $all_success = false;
        }
        
        // Vérifier les méthodes PHP
        $planning = new PlanningProduction($db);
        $required_methods = array('getAllMatieres', 'reorderMatieres', 'getNextMatiereOrdre');
        
        $methods_ok = true;
        $missing_methods = array();
        foreach ($required_methods as $method) {
            if (!method_exists($planning, $method)) {
                $methods_ok = false;
                $missing_methods[] = $method;
            }
        }
        
        $checks[] = array(
            'name' => 'Méthodes PHP',
            'success' => $methods_ok,
            'message' => $methods_ok ? 'Toutes présentes' : 'Manquantes : ' . implode(', ', $missing_methods)
        );
        
        if (!$methods_ok) $all_success = false;
        
        // Construire le message de résultat
        $details = '';
        foreach ($checks as $check) {
            $icon = $check['success'] ? '✅' : '❌';
            $details .= $icon . ' ' . $check['name'] . ' : ' . $check['message'] . "\n";
        }
        
        return array(
            'success' => $all_success,
            'title' => 'Vérification de l\'installation',
            'message' => $all_success ? 'Toutes les vérifications sont réussies' : 'Certaines vérifications ont échoué',
            'details' => trim($details)
        );
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'title' => 'Erreur - Vérification',
            'message' => $e->getMessage(),
            'details' => ''
        );
    }
}

/**
 * Uninstall database structure
 */
function uninstall_database_structure($db)
{
    try {
        // Supprimer l'index d'abord (si il existe)
        $sql_drop_index = "ALTER TABLE ".MAIN_DB_PREFIX."planningproduction_matieres DROP INDEX idx_planningproduction_matieres_ordre";
        $resql_index = $db->query($sql_drop_index);
        // On ignore les erreurs d'index (peut ne pas exister)
        
        // Supprimer la colonne
        $sql_drop_column = "ALTER TABLE ".MAIN_DB_PREFIX."planningproduction_matieres DROP COLUMN ordre";
        $resql_column = $db->query($sql_drop_column);
        
        if (!$resql_column) {
            throw new Exception('Erreur lors de la suppression de la colonne : ' . $db->lasterror());
        }
        
        return array(
            'success' => true,
            'title' => 'Désinstallation',
            'message' => 'Fonctionnalité désinstallée avec succès',
            'details' => 'La colonne "ordre" et son index ont été supprimés de la base de données'
        );
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'title' => 'Erreur - Désinstallation',
            'message' => $e->getMessage(),
            'details' => ''
        );
    }
}
