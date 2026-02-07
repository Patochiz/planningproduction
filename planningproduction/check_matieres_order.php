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
 * \file    check_matieres_order.php
 * \ingroup planningproduction
 * \brief   Final verification script for materials order management feature.
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

// Security check - Accessible à tous les utilisateurs du module
if (!isModEnabled('planningproduction')) {
    accessforbidden('Module Planning Production non activé');
}

/*
 * View
 */

$title = "Vérification - Gestion de l'ordre des Matières Premières";
llxHeader('', $title, '');

print '<style>
.check-container { max-width: 1000px; margin: 0 auto; }
.check-section { 
    background: #f8f9fa; 
    padding: 20px; 
    margin: 20px 0; 
    border-radius: 8px; 
    border-left: 4px solid #007bff; 
}
.check-item { 
    display: flex; 
    align-items: center; 
    padding: 8px 0; 
    border-bottom: 1px solid #e9ecef; 
}
.check-item:last-child { border-bottom: none; }
.check-status { 
    width: 30px; 
    text-align: center; 
    font-size: 16px; 
    margin-right: 15px; 
}
.check-success { color: #28a745; }
.check-warning { color: #ffc107; }
.check-error { color: #dc3545; }
.check-info { color: #17a2b8; }
.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 20px 0;
}
.summary-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.summary-number {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}
</style>';

print load_fiche_titre($title, '', 'fa-check-circle');

print '<div class="check-container">';

// Résumé de la vérification
$checks = performAllChecks($db, $user);
$total_checks = count($checks);
$passed_checks = 0;
$failed_checks = 0;
$warning_checks = 0;

foreach ($checks as $check) {
    switch ($check['status']) {
        case 'success': $passed_checks++; break;
        case 'error': $failed_checks++; break;
        case 'warning': $warning_checks++; break;
    }
}

$success_rate = round(($passed_checks / $total_checks) * 100);

// Résumé global
print '<div class="check-section">';
print '<h2 style="margin-top: 0;">📊 Résumé de la vérification</h2>';

print '<div class="summary-grid">';

print '<div class="summary-card">';
print '<div class="summary-number" style="color: #007bff;">'.$total_checks.'</div>';
print '<div>Tests effectués</div>';
print '</div>';

print '<div class="summary-card">';
print '<div class="summary-number" style="color: #28a745;">'.$passed_checks.'</div>';
print '<div>Tests réussis</div>';
print '</div>';

print '<div class="summary-card">';
print '<div class="summary-number" style="color: #ffc107;">'.$warning_checks.'</div>';
print '<div>Avertissements</div>';
print '</div>';

print '<div class="summary-card">';
print '<div class="summary-number" style="color: #dc3545;">'.$failed_checks.'</div>';
print '<div>Tests échoués</div>';
print '</div>';

print '<div class="summary-card">';
print '<div class="summary-number" style="color: '.($success_rate >= 80 ? '#28a745' : ($success_rate >= 60 ? '#ffc107' : '#dc3545')).'">'.$success_rate.'%</div>';
print '<div>Taux de réussite</div>';
print '</div>';

print '</div>';

// Statut global
$global_status = 'Excellent';
$status_color = '#28a745';
$status_icon = '🎉';

if ($failed_checks > 0) {
    $global_status = 'Problèmes détectés';
    $status_color = '#dc3545';
    $status_icon = '⚠️';
} elseif ($warning_checks > 2) {
    $global_status = 'À surveiller';
    $status_color = '#ffc107';
    $status_icon = '🔍';
} elseif ($success_rate < 90) {
    $global_status = 'Correct';
    $status_color = '#17a2b8';
    $status_icon = '👍';
}

print '<div style="text-align: center; padding: 20px; background: '.$status_color.'20; border-radius: 8px; margin: 20px 0;">';
print '<div style="font-size: 48px; margin-bottom: 10px;">'.$status_icon.'</div>';
print '<div style="font-size: 24px; font-weight: bold; color: '.$status_color.';">'.$global_status.'</div>';
print '</div>';

print '</div>';

// Détail des vérifications par catégorie
$categories = array();
foreach ($checks as $check) {
    $categories[$check['category']][] = $check;
}

foreach ($categories as $category => $category_checks) {
    print '<div class="check-section">';
    print '<h3 style="margin-top: 0;">'.$category.'</h3>';
    
    foreach ($category_checks as $check) {
        $icon = '';
        $class = '';
        
        switch ($check['status']) {
            case 'success':
                $icon = '✅';
                $class = 'check-success';
                break;
            case 'error':
                $icon = '❌';
                $class = 'check-error';
                break;
            case 'warning':
                $icon = '⚠️';
                $class = 'check-warning';
                break;
            case 'info':
                $icon = 'ℹ️';
                $class = 'check-info';
                break;
        }
        
        print '<div class="check-item">';
        print '<div class="check-status '.$class.'">'.$icon.'</div>';
        print '<div style="flex: 1;">';
        print '<div style="font-weight: bold;">'.$check['name'].'</div>';
        print '<div style="color: #666; font-size: 14px;">'.$check['description'].'</div>';
        if (!empty($check['details'])) {
            print '<div style="color: #999; font-size: 12px; margin-top: 5px;">'.$check['details'].'</div>';
        }
        print '</div>';
        print '</div>';
    }
    
    print '</div>';
}

// Actions recommandées
if ($failed_checks > 0 || $warning_checks > 2) {
    print '<div class="check-section" style="border-left-color: #ffc107; background: #fff3cd;">';
    print '<h3 style="margin-top: 0;">🔧 Actions recommandées</h3>';
    
    $actions = array();
    
    if ($failed_checks > 0) {
        $actions[] = "Corriger les erreurs critiques avant d'utiliser la fonctionnalité";
        $actions[] = "Exécuter le script d'installation : <code>install_matieres_order.php</code>";
        $actions[] = "Vérifier les permissions utilisateur sur le module";
    }
    
    if ($warning_checks > 0) {
        $actions[] = "Consulter la documentation : <code>docs/README_MATIERES_ORDER.md</code>";
        $actions[] = "Tester la fonctionnalité avec des données de test";
    }
    
    foreach ($actions as $action) {
        print '<div style="margin: 8px 0;">• '.$action.'</div>';
    }
    
    print '</div>';
}

// Liens utiles
print '<div class="check-section">';
print '<h3 style="margin-top: 0;">🔗 Liens utiles</h3>';

$links = array(
    array('file' => 'install_matieres_order.php', 'name' => 'Installation guidée', 'desc' => 'Assistant d\'installation en 4 étapes'),
    array('file' => 'test_matieres_order.php', 'name' => 'Tests automatiques', 'desc' => 'Suite complète de tests unitaires'),
    array('file' => 'demo_matieres_order.php', 'name' => 'Démonstration', 'desc' => 'Page de démonstration interactive'),
    array('file' => '../admin/setup.php', 'name' => 'Configuration', 'desc' => 'Interface de configuration du module'),
    array('file' => 'docs/README_MATIERES_ORDER.md', 'name' => 'Documentation', 'desc' => 'Guide complet d\'installation'),
    array('file' => 'RECAP_MATIERES_ORDER.md', 'name' => 'Récapitulatif', 'desc' => 'Vue d\'ensemble de la fonctionnalité')
);

foreach ($links as $link) {
    $exists = file_exists($link['file']);
    $status = $exists ? '✅' : '❌';
    $color = $exists ? '#28a745' : '#dc3545';
    
    print '<div class="check-item">';
    print '<div class="check-status" style="color: '.$color.';">'.$status.'</div>';
    print '<div style="flex: 1;">';
    if ($exists) {
        print '<a href="'.$link['file'].'" style="font-weight: bold; text-decoration: none;">'.$link['name'].'</a>';
    } else {
        print '<span style="font-weight: bold; color: #999;">'.$link['name'].' (manquant)</span>';
    }
    print '<div style="color: #666; font-size: 14px;">'.$link['desc'].'</div>';
    print '</div>';
    print '</div>';
}

print '</div>';

// Footer
print '<div style="text-align: center; padding: 20px; border-top: 1px solid #dee2e6; margin-top: 20px; color: #666;">';
print '<p><strong>Vérification terminée</strong> - '.date('Y-m-d H:i:s').'</p>';
print '<p><small>Module Planning Production - Gestion de l\'ordre des Matières Premières v1.1.0</small></p>';
print '</div>';

print '</div>'; // Fin check-container

llxFooter();
$db->close();

/**
 * Effectuer toutes les vérifications
 */
function performAllChecks($db, $user)
{
    $checks = array();
    
    // ========== STRUCTURE DE BASE ==========
    
    // Vérifier l'existence de la table principale
    $sql = "SHOW TABLES LIKE '".MAIN_DB_PREFIX."planningproduction_matieres'";
    $resql = $db->query($sql);
    $table_exists = ($resql && $db->num_rows($resql) > 0);
    
    $checks[] = array(
        'category' => '🗃️ Structure de base de données',
        'name' => 'Table des matières premières',
        'description' => 'Vérification de l\'existence de llx_planningproduction_matieres',
        'status' => $table_exists ? 'success' : 'error',
        'details' => $table_exists ? 'Table trouvée' : 'Table manquante - Exécuter la migration SQL'
    );
    
    if ($table_exists) {
        // Vérifier la colonne ordre
        $sql_columns = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."planningproduction_matieres";
        $resql_columns = $db->query($sql_columns);
        $columns = array();
        while ($obj = $db->fetch_object($resql_columns)) {
            $columns[] = $obj->Field;
        }
        
        $has_ordre = in_array('ordre', $columns);
        $checks[] = array(
            'category' => '🗃️ Structure de base de données',
            'name' => 'Colonne "ordre"',
            'description' => 'Vérification de l\'existence de la colonne ordre',
            'status' => $has_ordre ? 'success' : 'error',
            'details' => $has_ordre ? 'Colonne présente' : 'Colonne manquante - Exécuter la migration SQL'
        );
        
        // Vérifier l'index sur la colonne ordre
        $sql_indexes = "SHOW INDEX FROM ".MAIN_DB_PREFIX."planningproduction_matieres WHERE Column_name = 'ordre'";
        $resql_indexes = $db->query($sql_indexes);
        $has_index = ($resql_indexes && $db->num_rows($resql_indexes) > 0);
        
        $checks[] = array(
            'category' => '🗃️ Structure de base de données',
            'name' => 'Index sur "ordre"',
            'description' => 'Vérification de l\'index pour optimiser les performances',
            'status' => $has_index ? 'success' : 'warning',
            'details' => $has_index ? 'Index trouvé' : 'Index manquant - Performance réduite'
        );
    }
    
    // ========== FICHIERS SYSTÈME ==========
    
    $files_to_check = array(
        array('file' => 'ajax_matieres_order.php', 'name' => 'Endpoint AJAX', 'critical' => true),
        array('file' => 'js/matieres_order.js', 'name' => 'JavaScript drag & drop', 'critical' => true),
        array('file' => 'css/matieres_order.css', 'name' => 'Styles CSS', 'critical' => false),
        array('file' => 'sql/llx_planningproduction_matieres_ordre.sql', 'name' => 'Script de migration', 'critical' => false),
        array('file' => 'install_matieres_order.php', 'name' => 'Script d\'installation', 'critical' => false),
        array('file' => 'test_matieres_order.php', 'name' => 'Tests automatiques', 'critical' => false)
    );
    
    foreach ($files_to_check as $file_check) {
        $exists = file_exists($file_check['file']);
        $checks[] = array(
            'category' => '📁 Fichiers système',
            'name' => $file_check['name'],
            'description' => 'Vérification de l\'existence de '.$file_check['file'],
            'status' => $exists ? 'success' : ($file_check['critical'] ? 'error' : 'warning'),
            'details' => $exists ? 'Fichier présent' : 'Fichier manquant'
        );
    }
    
    // ========== CLASSE PHP ==========
    
    if (class_exists('PlanningProduction')) {
        $planning = new PlanningProduction($db);
        
        $methods_to_check = array(
            'getAllMatieres' => 'Récupération des matières avec ordre',
            'getNextMatiereOrdre' => 'Génération automatique d\'ordre',
            'updateMatiereOrdre' => 'Modification d\'ordre unitaire',
            'reorderMatieres' => 'Réorganisation en lot'
        );
        
        foreach ($methods_to_check as $method => $description) {
            $exists = method_exists($planning, $method);
            $checks[] = array(
                'category' => '🔧 Classe PHP',
                'name' => 'Méthode '.$method,
                'description' => $description,
                'status' => $exists ? 'success' : 'error',
                'details' => $exists ? 'Méthode disponible' : 'Méthode manquante'
            );
        }
    } else {
        $checks[] = array(
            'category' => '🔧 Classe PHP',
            'name' => 'Classe PlanningProduction',
            'description' => 'Vérification de l\'existence de la classe principale',
            'status' => 'error',
            'details' => 'Classe non trouvée'
        );
    }
    
    // ========== PERMISSIONS ==========
    
    $has_read = $user->hasRight('planningproduction', 'planning', 'read');
    $has_write = $user->hasRight('planningproduction', 'planning', 'write');
    
    $checks[] = array(
        'category' => '🔐 Permissions utilisateur',
        'name' => 'Droits de lecture',
        'description' => 'Vérification des permissions de lecture sur le module',
        'status' => $has_read ? 'success' : 'error',
        'details' => $has_read ? 'Droits accordés' : 'Droits manquants'
    );
    
    $checks[] = array(
        'category' => '🔐 Permissions utilisateur',
        'name' => 'Droits d\'écriture',
        'description' => 'Nécessaires pour utiliser le drag & drop',
        'status' => $has_write ? 'success' : 'warning',
        'details' => $has_write ? 'Droits accordés' : 'Fonctionnalité en lecture seule'
    );
    
    // ========== DONNÉES ==========
    
    if ($table_exists && in_array('ordre', $columns)) {
        // Compter les matières premières
        $sql_count = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."planningproduction_matieres";
        $resql_count = $db->query($sql_count);
        $nb_matieres = 0;
        if ($resql_count) {
            $obj = $db->fetch_object($resql_count);
            $nb_matieres = (int) $obj->nb;
        }
        
        $checks[] = array(
            'category' => '📊 Données',
            'name' => 'Nombre de matières',
            'description' => 'Vérification du nombre de matières premières configurées',
            'status' => $nb_matieres >= 2 ? 'success' : ($nb_matieres > 0 ? 'warning' : 'info'),
            'details' => $nb_matieres.' matière(s) trouvée(s)'.($nb_matieres < 2 ? ' - Drag & drop non visible' : '')
        );
        
        // Vérifier la cohérence des ordres
        if ($nb_matieres > 0) {
            $sql_ordre = "SELECT MIN(ordre) as min_ordre, MAX(ordre) as max_ordre FROM ".MAIN_DB_PREFIX."planningproduction_matieres";
            $resql_ordre = $db->query($sql_ordre);
            if ($resql_ordre) {
                $obj = $db->fetch_object($resql_ordre);
                $coherent = ((int) $obj->min_ordre > 0);
                
                $checks[] = array(
                    'category' => '📊 Données',
                    'name' => 'Cohérence des ordres',
                    'description' => 'Vérification que tous les ordres sont définis',
                    'status' => $coherent ? 'success' : 'warning',
                    'details' => 'Ordres de '.$obj->min_ordre.' à '.$obj->max_ordre.($coherent ? '' : ' - Migration recommandée')
                );
            }
        }
    }
    
    // ========== TRADUCTIONS ==========
    
    $lang_fr_exists = file_exists('langs/fr_FR/planningproduction.lang');
    $lang_en_exists = file_exists('langs/en_US/planningproduction.lang');
    
    $checks[] = array(
        'category' => '🌍 Traductions',
        'name' => 'Fichier français',
        'description' => 'Vérification des traductions françaises',
        'status' => $lang_fr_exists ? 'success' : 'warning',
        'details' => $lang_fr_exists ? 'Traductions FR disponibles' : 'Traductions manquantes'
    );
    
    $checks[] = array(
        'category' => '🌍 Traductions',
        'name' => 'Fichier anglais',
        'description' => 'Vérification des traductions anglaises',
        'status' => $lang_en_exists ? 'success' : 'warning',
        'details' => $lang_en_exists ? 'Traductions EN disponibles' : 'Traductions manquantes'
    );
    
    // ========== DOCUMENTATION ==========
    
    $docs = array(
        'docs/MATIERES_ORDER.md' => 'Guide utilisateur',
        'docs/README_MATIERES_ORDER.md' => 'Guide d\'installation',
        'RECAP_MATIERES_ORDER.md' => 'Récapitulatif'
    );
    
    foreach ($docs as $file => $name) {
        $exists = file_exists($file);
        $checks[] = array(
            'category' => '📚 Documentation',
            'name' => $name,
            'description' => 'Vérification de la documentation '.$file,
            'status' => $exists ? 'success' : 'info',
            'details' => $exists ? 'Documentation disponible' : 'Documentation manquante'
        );
    }
    
    return $checks;
}
