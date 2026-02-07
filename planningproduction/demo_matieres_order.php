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
 * \file    demo_matieres_order.php
 * \ingroup planningproduction
 * \brief   Demonstration page for materials order management feature.
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
if (!isModEnabled('planningproduction')) {
    accessforbidden('Module Planning Production non activé');
}

$action = GETPOST('action', 'alpha');

/*
 * Actions
 */

$message = '';
$planning = new PlanningProduction($db);

if ($action == 'create_demo_data' && $user->hasRight('planningproduction', 'planning', 'write')) {
    // Créer des données de démonstration
    $demo_materials = array(
        array('code' => 'DEMO - ACIER INOX', 'stock' => 500.00),
        array('code' => 'DEMO - ALUMINIUM', 'stock' => 300.50),
        array('code' => 'DEMO - CUIVRE', 'stock' => 150.75),
        array('code' => 'DEMO - BRONZE', 'stock' => 75.25),
        array('code' => 'DEMO - LAITON', 'stock' => 200.00)
    );
    
    $created = 0;
    foreach ($demo_materials as $material) {
        $result = $planning->createMatiere($material['code'], $material['stock']);
        if ($result > 0) {
            $created++;
        }
    }
    
    if ($created > 0) {
        $message = "✅ $created matière(s) de démonstration créée(s) avec succès !";
    } else {
        $message = "❌ Aucune matière de démonstration créée (peut-être existent-elles déjà ?)";
    }
}

if ($action == 'cleanup_demo_data' && $user->hasRight('planningproduction', 'planning', 'write')) {
    // Supprimer les données de démonstration
    $matieres = $planning->getAllMatieres(false);
    $deleted = 0;
    
    foreach ($matieres as $matiere) {
        if (strpos($matiere['code_mp'], 'DEMO - ') === 0) {
            $result = $planning->deleteMatiere($matiere['rowid']);
            if ($result > 0) {
                $deleted++;
            }
        }
    }
    
    if ($deleted > 0) {
        $message = "✅ $deleted matière(s) de démonstration supprimée(s) avec succès !";
    } else {
        $message = "ℹ️ Aucune matière de démonstration trouvée à supprimer.";
    }
}

/*
 * View
 */

$title = "Démonstration - Gestion de l'ordre des Matières Premières";
llxHeader('', $title, '');

// CSS pour la démonstration
print '<style>
.demo-container {
    max-width: 1200px;
    margin: 0 auto;
}
.demo-step {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin: 20px 0;
}
.demo-step h3 {
    margin-top: 0;
    color: white;
}
.demo-feature {
    background: #f8f9fa;
    border-left: 4px solid #007bff;
    padding: 15px;
    margin: 15px 0;
}
.demo-video-placeholder {
    background: #e9ecef;
    border: 2px dashed #adb5bd;
    height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    margin: 20px 0;
}
.demo-screenshot {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin: 10px 0;
    max-width: 100%;
    height: auto;
}
</style>';

print load_fiche_titre($title, '', 'fa-play-circle');

print '<div class="demo-container">';

// Message de retour
if ($message) {
    $class = strpos($message, '✅') === 0 ? 'ok' : (strpos($message, '❌') === 0 ? 'error' : 'info');
    print '<div class="'.$class.'" style="padding: 15px; margin: 15px 0; border-radius: 6px;">';
    print $message;
    print '</div>';
}

// Introduction
print '<div class="info" style="padding: 20px; border-radius: 10px; margin-bottom: 30px;">';
print '<h2 style="margin-top: 0;">🎯 Bienvenue dans la démonstration !</h2>';
print '<p><strong>Cette page vous montre comment utiliser la nouvelle fonctionnalité de réorganisation des matières premières.</strong></p>';
print '<p>Vous pourrez tester le glisser-déposer en temps réel et voir comment l\'ordre est sauvegardé automatiquement.</p>';
print '</div>';

// Étape 1: Préparation des données
print '<div class="demo-step">';
print '<h3>📋 Étape 1 : Préparation des données de test</h3>';
print '<p>Avant de tester la fonctionnalité, nous avons besoin de quelques matières premières.</p>';

$matieres = $planning->getAllMatieres(true);
$demo_count = 0;
foreach ($matieres as $matiere) {
    if (strpos($matiere['code_mp'], 'DEMO - ') === 0) {
        $demo_count++;
    }
}

if ($demo_count > 0) {
    print '<p>✅ <strong>'.$demo_count.' matière(s) de démonstration déjà présente(s)</strong></p>';
    
    if ($user->hasRight('planningproduction', 'planning', 'write')) {
        print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" style="display: inline;">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="action" value="cleanup_demo_data">';
        print '<input type="submit" class="button" value="🗑️ Nettoyer les données de test" onclick="return confirm(\'Supprimer les matières de démonstration ?\');">';
        print '</form>';
    }
} else {
    print '<p>ℹ️ Aucune donnée de démonstration trouvée.</p>';
    
    if ($user->hasRight('planningproduction', 'planning', 'write')) {
        print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" style="display: inline;">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="action" value="create_demo_data">';
        print '<input type="submit" class="button" value="🎬 Créer des données de démonstration">';
        print '</form>';
    } else {
        print '<div class="warning">⚠️ Vous avez besoin des droits d\'écriture pour créer des données de test.</div>';
    }
}
print '</div>';

// Étape 2: Accès à la fonctionnalité
print '<div class="demo-step">';
print '<h3>🚀 Étape 2 : Accéder à la fonctionnalité</h3>';
print '<p>Pour utiliser la réorganisation des matières premières :</p>';
print '<ol style="margin-left: 20px;">';
print '<li><strong>Menu principal</strong> → Configuration → Modules/Applications</li>';
print '<li><strong>Rechercher</strong> "Planning Production"</li>';
print '<li><strong>Cliquer</strong> sur le module</li>';
print '<li><strong>Onglet</strong> "Configuration"</li>';
print '<li><strong>Section</strong> "Gestion des Matières Premières"</li>';
print '</ol>';

print '<p style="text-align: center; margin: 20px 0;">';
print '<a href="../admin/setup.php" class="button" style="font-size: 18px; padding: 15px 25px;">';
print '🔧 Aller à la configuration maintenant';
print '</a>';
print '</p>';
print '</div>';

// Étape 3: Utilisation
print '<div class="demo-step">';
print '<h3>🎮 Étape 3 : Comment utiliser le drag & drop</h3>';
print '<div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px; margin: 15px 0;">';

print '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">';

// Colonne gauche - Instructions
print '<div>';
print '<h4 style="color: #fff; margin-top: 0;">📝 Instructions :</h4>';
print '<ol style="margin-left: 15px; color: #fff;">';
print '<li><strong>Localiser la poignée</strong> ≡ à côté du code MP</li>';
print '<li><strong>Cliquer et maintenir</strong> sur la poignée</li>';
print '<li><strong>Glisser</strong> vers la nouvelle position</li>';
print '<li><strong>Relâcher</strong> à l\'endroit souhaité</li>';
print '<li><strong>Confirmation</strong> automatique en haut à droite</li>';
print '</ol>';
print '</div>';

// Colonne droite - Indicateurs visuels
print '<div>';
print '<h4 style="color: #fff; margin-top: 0;">👁️ Indicateurs visuels :</h4>';
print '<ul style="margin-left: 15px; color: #fff;">';
print '<li><strong>Ligne en déplacement</strong> : Semi-transparente</li>';
print '<li><strong>Zone de dépôt</strong> : Ligne bleue</li>';
print '<li><strong>Au survol</strong> : Changement de couleur</li>';
print '<li><strong>Sauvegarde</strong> : Message de confirmation</li>';
print '</ul>';
print '</div>';

print '</div>';

// Placeholder pour vidéo de démonstration
print '<div class="demo-video-placeholder">';
print '<div style="text-align: center;">';
print '<i class="fa fa-play-circle" style="font-size: 48px; color: #6c757d; margin-bottom: 10px;"></i><br>';
print '<strong>Vidéo de démonstration</strong><br>';
print '<small>Ici se trouverait une vidéo montrant l\'utilisation du drag & drop</small>';
print '</div>';
print '</div>';

print '</div>';
print '</div>';

// Fonctionnalités avancées
print '<div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;">';
print '<h3>🔥 Fonctionnalités avancées</h3>';

print '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">';

$features = array(
    array(
        'icon' => '💾',
        'title' => 'Sauvegarde automatique',
        'desc' => 'L\'ordre est sauvegardé automatiquement en base de données dès que vous relâchez un élément.'
    ),
    array(
        'icon' => '🔄',
        'title' => 'Restauration d\'erreur',
        'desc' => 'En cas d\'erreur réseau, l\'ordre original est automatiquement restauré.'
    ),
    array(
        'icon' => '📱',
        'title' => 'Compatible mobile',
        'desc' => 'L\'interface fonctionne parfaitement sur les appareils tactiles (tablettes, smartphones).'
    ),
    array(
        'icon' => '🔒',
        'title' => 'Sécurisé',
        'desc' => 'Vérification des permissions utilisateur et protection contre les modifications non autorisées.'
    ),
    array(
        'icon' => '⚡',
        'title' => 'Temps réel',
        'desc' => 'Feedback visuel immédiat avec indicateurs de chargement et messages de confirmation.'
    ),
    array(
        'icon' => '🎨',
        'title' => 'Interface intuitive',
        'desc' => 'Design moderne avec animations fluides et indicateurs visuels clairs.'
    )
);

foreach ($features as $feature) {
    print '<div class="demo-feature">';
    print '<h4 style="margin-top: 0;">'.$feature['icon'].' '.$feature['title'].'</h4>';
    print '<p style="margin-bottom: 0;">'.$feature['desc'].'</p>';
    print '</div>';
}

print '</div>';
print '</div>';

// État actuel du système
print '<div style="background: #e8f4f8; border: 1px solid #bee5eb; padding: 20px; border-radius: 10px; margin: 20px 0;">';
print '<h3 style="margin-top: 0;">📊 État actuel du système</h3>';

$total_matieres = count($matieres);
$demo_matieres = $demo_count;
$real_matieres = $total_matieres - $demo_matieres;

print '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">';

$stats = array(
    array('label' => 'Total matières', 'value' => $total_matieres, 'icon' => '📦'),
    array('label' => 'Matières réelles', 'value' => $real_matieres, 'icon' => '🏭'),
    array('label' => 'Matières de démo', 'value' => $demo_matieres, 'icon' => '🎬'),
    array('label' => 'Drag & drop actif', 'value' => $total_matieres >= 2 ? 'Oui' : 'Non', 'icon' => '🖱️')
);

foreach ($stats as $stat) {
    print '<div style="text-align: center; background: white; padding: 15px; border-radius: 8px; border: 1px solid #dee2e6;">';
    print '<div style="font-size: 24px; margin-bottom: 5px;">'.$stat['icon'].'</div>';
    print '<div style="font-size: 20px; font-weight: bold; color: #007bff;">'.$stat['value'].'</div>';
    print '<div style="font-size: 14px; color: #6c757d;">'.$stat['label'].'</div>';
    print '</div>';
}

print '</div>';
print '</div>';

// Liens utiles
print '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 10px; margin: 20px 0;">';
print '<h3 style="margin-top: 0;">🔗 Liens utiles</h3>';

$links = array(
    array('url' => '../admin/setup.php', 'text' => '⚙️ Configuration du module', 'desc' => 'Page principale de configuration'),
    array('url' => 'test_matieres_order.php', 'text' => '🧪 Tests de la fonctionnalité', 'desc' => 'Vérifier que tout fonctionne correctement'),
    array('url' => 'install_matieres_order.php', 'text' => '💾 Installation automatique', 'desc' => 'Script d\'installation guidé'),
    array('url' => 'docs/MATIERES_ORDER.md', 'text' => '📚 Documentation utilisateur', 'desc' => 'Guide complet d\'utilisation')
);

foreach ($links as $link) {
    print '<p style="margin: 10px 0;">';
    print '<a href="'.$link['url'].'" class="button" style="margin-right: 10px;">'.$link['text'].'</a>';
    print '<span style="color: #6c757d;">'.$link['desc'].'</span>';
    print '</p>';
}

print '</div>';

// Footer
print '<div style="text-align: center; padding: 20px; color: #6c757d; border-top: 1px solid #dee2e6; margin-top: 40px;">';
print '<p><strong>Gestion de l\'ordre des Matières Premières</strong></p>';
print '<p>Version 1.0 - Développé par Patrick Delcroix - © 2024</p>';
print '<p><small>Cette fonctionnalité fait partie du module Planning Production pour Dolibarr</small></p>';
print '</div>';

print '</div>'; // Fin demo-container

llxFooter();
$db->close();
