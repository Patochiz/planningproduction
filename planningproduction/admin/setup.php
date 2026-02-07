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
 * \file    admin/setup.php
 * \ingroup planningproduction
 * \brief   PlanningProduction setup page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

dol_include_once('/planningproduction/class/planningproduction.class.php');

global $langs, $user;

// Translations
$langs->loadLangs(array("admin", "planningproduction@planningproduction"));

// Security check
if (!$user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'myobject';

$arrayofparameters = array(
    'PLANNINGPRODUCTION_CARD_WIDTH' => array(
        'type' => 'string',
        'css' => 'minwidth200',
        'enabled' => 1
    ),
);

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

// Action manuelle pour notre cas spécifique
if ($action == 'update_card_width') {
    $card_width = GETPOST('PLANNINGPRODUCTION_CARD_WIDTH', 'int');
    
    if ($card_width >= 200 && $card_width <= 1000) {
        if (!function_exists('dolibarr_set_const')) {
            include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
        }
        
        $result = dolibarr_set_const($db, 'PLANNINGPRODUCTION_CARD_WIDTH', $card_width, 'chaine', 0, '', $conf->entity);
        
        if ($result > 0) {
            setEventMessages("Configuration sauvegardée avec succès", null, 'mesgs');
        } else {
            setEventMessages("Erreur lors de la sauvegarde", null, 'errors');
        }
    } else {
        setEventMessages("La valeur doit être comprise entre 200 et 1000 pixels", null, 'errors');
    }
}

/*
 * View
 */

$form = new Form($db);

$help_url = '';
$title = "PlanningProductionSetup";

llxHeader('', $langs->trans($title), $help_url);

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

// Configuration header
$head = planningproductionAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($title), -1, 'planningproduction@planningproduction');

// Setup page goes here
print info_admin($langs->trans("PlanningProductionSetupPage"));

print '<br>';

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update_card_width">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>';

// Largeur des cartes
print '<tr class="oddeven">';
print '<td>';
print '<strong>Largeur des cartes Kanban</strong>';
print '<br><small>Définit la largeur des cartes dans le planning hybride</small>';
print '</td>';
print '<td>';
print '<input type="number" name="PLANNINGPRODUCTION_CARD_WIDTH" id="cardWidthInput" value="'.getDolGlobalString('PLANNINGPRODUCTION_CARD_WIDTH', '260').'" min="200" max="500" step="10" style="width: 80px;" onchange="updatePanelPreview(this.value)">';
print ' px';
print '<br><small style="color: #666;">Recommandé : 260px (min: 200, max: 1000)</small>';
$current_width = intval(getDolGlobalString('PLANNINGPRODUCTION_CARD_WIDTH', '260'));
$current_panel = $current_width + 50;
print '<br><small style="color: #999;">Valeur actuelle : '.$current_width.'px → Panneau : '.$current_panel.'px</small>';
print '<br><small id="panelPreview" style="color: #3498db; font-weight: bold;"></small>';
print '</td>';
print '</tr>';

print '</table>';

print '<br>';
print '<input type="submit" class="button" value="Sauvegarder">';

print '</form>';

// ========== SECTION GESTION DES MATIÈRES PREMIÈRES ==========
print '<br><hr><br>';
print '<h3>Gestion des Matières Premières</h3>';
print '<p>Configuration des codes matières premières utilisés dans le planning de production.</p>';

// Récupérer les matières existantes AVEC l'ordre
$planning = new PlanningProduction($db);
$matieres = $planning->getAllMatieres(true); // IMPORTANT: trier par ordre

// Debug des données récupérées
if ($matieres === false) {
    $matieres = array();
    $error_msg = "Erreur lors de la récupération des matières premières";
    if (!empty($planning->errors)) {
        $error_msg .= ": " . implode(", ", $planning->errors);
    }
    setEventMessages($error_msg, null, 'errors');
    dol_syslog("admin/setup.php - getAllMatieres() returned false. Errors: " . implode(", ", $planning->errors), LOG_ERR);
} else {
    dol_syslog("admin/setup.php - getAllMatieres() returned " . count($matieres) . " matières", LOG_DEBUG);
}

// Vérifier si la table existe
$sql_check = "SHOW TABLES LIKE '" . MAIN_DB_PREFIX . "planningproduction_matieres'";
$resql_check = $db->query($sql_check);
if (!$resql_check || $db->num_rows($resql_check) == 0) {
    setEventMessages("Table des matières premières non trouvée. Désactivez et réactivez le module pour la créer.", null, 'errors');
    dol_syslog("admin/setup.php - Table llx_planningproduction_matieres does not exist", LOG_ERR);
}

// Traitement des actions sur les matières premières
if ($action == 'add_matiere' && $user->hasRight('planningproduction', 'planning', 'write')) {
    $new_code_mp = GETPOST('new_code_mp', 'alphanohtml');
    $new_stock = GETPOST('new_stock', 'alphanohtml');
    
    // Debug des valeurs reçues
    dol_syslog("add_matiere - new_code_mp: '".$new_code_mp."', new_stock: '".$new_stock."'", LOG_DEBUG);
    
    if (!empty($new_code_mp) && $new_stock !== '' && is_numeric($new_stock)) {
        $result = $planning->createMatiere($new_code_mp, (float)$new_stock);
        if ($result > 0) {
            setEventMessages("Matière première ajoutée avec succès", null, 'mesgs');
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        } else {
            setEventMessages("Erreur lors de l'ajout (code déjà existant ?)", null, 'errors');
        }
    } else {
        setEventMessages("Code MP ou stock invalide. Code MP: '".$new_code_mp."', Stock: '".$new_stock."'", null, 'errors');
    }
}

if ($action == 'delete_matiere' && $user->hasRight('planningproduction', 'planning', 'write')) {
    $rowid = GETPOST('rowid', 'int');
    if ($rowid > 0) {
        $result = $planning->deleteMatiere($rowid);
        if ($result > 0) {
            setEventMessages("Matière première supprimée avec succès", null, 'mesgs');
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        } else {
            setEventMessages("Erreur lors de la suppression", null, 'errors');
        }
    } else {
        setEventMessages("ID invalide", null, 'errors');
    }
}

if ($action == 'update_matiere' && $user->hasRight('planningproduction', 'planning', 'write')) {
    $rowid = GETPOST('rowid', 'int');
    $code_mp = GETPOST('code_mp', 'alphanohtml');
    $stock = GETPOST('stock', 'alphanohtml');
    
    // Debug des valeurs reçues
    dol_syslog("update_matiere - rowid: '".$rowid."', code_mp: '".$code_mp."', stock: '".$stock."'", LOG_DEBUG);
    
    if ($rowid > 0 && !empty($code_mp) && $stock !== '' && is_numeric($stock)) {
        $result = $planning->updateMatiere($rowid, $code_mp, (float)$stock);
        if ($result > 0) {
            setEventMessages("Matière première mise à jour avec succès", null, 'mesgs');
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        } else {
            setEventMessages("Erreur lors de la mise à jour (code déjà existant ?)", null, 'errors');
        }
    } else {
        $debug_msg = "Paramètres invalides - ";
        $debug_msg .= "rowid: '".$rowid."' (".(($rowid > 0) ? 'OK' : 'KO')."), ";
        $debug_msg .= "code_mp: '".$code_mp."' (".(!empty($code_mp) ? 'OK' : 'KO')."), ";
        $debug_msg .= "stock: '".$stock."' (".(($stock !== '' && is_numeric($stock)) ? 'OK' : 'KO').")";
        setEventMessages($debug_msg, null, 'errors');
    }
}

// Formulaire d'ajout
if ($user->hasRight('planningproduction', 'planning', 'write')) {
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" style="margin-bottom: 20px;">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="add_matiere">';
    
    print '<table class="noborder" style="width: 600px;">';
    print '<tr class="liste_titre">';
    print '<td colspan="3">Ajouter une nouvelle matière première</td>';
    print '</tr>';
    print '<tr>';
    print '<td style="width: 200px;">Code MP :</td>';
    print '<td style="width: 150px;">Stock initial :</td>';
    print '<td style="width: 100px;">Action</td>';
    print '</tr>';
    print '<tr>';
    print '<td><input type="text" name="new_code_mp" placeholder="Ex: 400 BLANC" required style="width: 180px;"></td>';
    print '<td><input type="number" name="new_stock" step="0.01" value="0" style="width: 120px;"></td>';
    print '<td><input type="submit" value="Ajouter" class="button"></td>';
    print '</tr>';
    print '</table>';
    print '</form>';
}

// Inclure le CSS et JavaScript pour le drag & drop AVANT le tableau
if ($user->hasRight('planningproduction', 'planning', 'write')) {
    print '<link rel="stylesheet" href="'.dol_buildpath('/planningproduction/css/matieres_order.css', 1).'?v='.time().'" type="text/css">'."\n";
    print '<script src="'.dol_buildpath('/planningproduction/js/matieres_order.js', 1).'?v='.time().'"></script>'."\n";
}

// Tableau des matières existantes avec drag & drop
if ($user->hasRight('planningproduction', 'planning', 'write') && count($matieres) > 1) {
    print '<div class="matieres-info">';
    print '<i class="fa fa-info-circle"></i> ';
    print 'Vous pouvez réorganiser l\'ordre des matières premières en les faisant glisser avec la poignée <i class="fa fa-bars" style="color: #999;"></i>.';
    print '</div>';
}

print '<table class="noborder centpercent matieres-table" id="matieres-sortable">';
print '<tr class="liste_titre">';
print '<td>Code MP</td>';
print '<td>Stock</td>';
print '<td>Dernière MàJ</td>';
if ($user->hasRight('planningproduction', 'planning', 'write')) {
    print '<td width="150">Actions</td>';
}
print '</tr>';

if (count($matieres) == 0) {
    print '<tr><td colspan="'.(($user->hasRight('planningproduction', 'planning', 'write')) ? '4' : '3').'" class="opacitymedium">Aucune matière première configurée</td></tr>';
} else {
    foreach ($matieres as $matiere) {
        print '<tr class="oddeven" data-matiere-id="'.$matiere['rowid'].'" data-ordre="'.($matiere['ordre'] ?? 0).'">';
        
        // Edition en ligne pour les utilisateurs autorisés
        if ($user->hasRight('planningproduction', 'planning', 'write') && GETPOST('edit_matiere') == $matiere['rowid']) {
            print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" style="margin: 0;">';
            print '<input type="hidden" name="token" value="'.newToken().'">';
            print '<input type="hidden" name="action" value="update_matiere">';
            print '<input type="hidden" name="rowid" value="'.$matiere['rowid'].'">';
            
            print '<td><input type="text" name="code_mp" value="'.htmlspecialchars($matiere['code_mp']).'" required style="width: 100%;"></td>';
            print '<td><input type="number" name="stock" value="'.$matiere['stock'].'" step="0.01" style="width: 100px;"></td>';
            print '<td>'.dol_print_date($matiere['date_maj'], 'dayhour').'</td>';
            print '<td>';
            print '<input type="submit" value="Sauver" class="button" style="margin-right: 5px;">';
            print '<a href="'.$_SERVER["PHP_SELF"].'" class="button">Annuler</a>';
            print '</td>';
            print '</form>';
        } else {
            // Affichage normal
            $drag_handle = '';
            if ($user->hasRight('planningproduction', 'planning', 'write') && count($matieres) > 1) {
                $drag_handle = '<span class="drag-handle" title="Glisser pour réorganiser"><i class="fa fa-bars"></i></span>';
            }
            print '<td>'.$drag_handle.'<strong>'.htmlspecialchars($matiere['code_mp']).'</strong></td>';
            print '<td>'.number_format($matiere['stock'], 2).'</td>';
            print '<td>'.dol_print_date($matiere['date_maj'], 'dayhour').'</td>';
            
            if ($user->hasRight('planningproduction', 'planning', 'write')) {
                print '<td>';
                print '<a href="'.$_SERVER["PHP_SELF"].'?edit_matiere='.$matiere['rowid'].'" class="button" style="margin-right: 5px;">Modifier</a>';
                print '<a href="'.$_SERVER["PHP_SELF"].'?action=delete_matiere&rowid='.$matiere['rowid'].'&token='.newToken().'" class="button" onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer cette matière première ?\');">Supprimer</a>';
                print '</td>';
            }
        }
        print '</tr>';
    }
}
print '</table>';

// Script de réinitialisation du drag & drop après rechargement
if ($user->hasRight('planningproduction', 'planning', 'write') && count($matieres) > 1) {
    print '<script type="text/javascript">'."\n";
    print '// Réinitialiser le drag & drop après rechargement de la page'."\n";
    print 'document.addEventListener("DOMContentLoaded", function() {'."\n";
    print '    // Attendre que le DOM soit complètement chargé'."\n";
    print '    setTimeout(function() {'."\n";
    print '        if (typeof MatieresOrderManager !== "undefined") {'."\n";
    print '            console.log("Réinitialisation du drag & drop...");'."\n";
    print '            window.matieresOrderManager = new MatieresOrderManager("#matieres-sortable");'."\n";
    print '            console.log("Drag & drop des matières premières initialisé !");'."\n";
    print '        } else {'."\n";
    print '            console.warn("MatieresOrderManager non disponible - Vérifiez le chargement de matieres_order.js");'."\n";
    print '        }'."\n";
    print '    }, 200); // Attendre 200ms pour être sûr que le JS est chargé'."\n";
    print '});'."\n";
    print '</script>'."\n";
}

// JavaScript pour la prévisualisation
print '<script type="text/javascript">'."\n";
print 'function updatePanelPreview(cardWidth) {'."\n";
print '    const panelWidth = parseInt(cardWidth) + 50;'."\n";
print '    const preview = document.getElementById("panelPreview");'."\n";
print '    if (preview) {'."\n";
print '        preview.innerHTML = "Nouveau : " + cardWidth + "px → Panneau : " + panelWidth + "px";'."\n";
print '    }'."\n";
print '}'."\n";
print '</script>'."\n";

print dol_get_fiche_end();


llxFooter();
$db->close();

/**
 * Prepare admin pages header
 *
 * @return array
 */
function planningproductionAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load("planningproduction@planningproduction");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/planningproduction/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Settings");
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath("/planningproduction/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    complete_head_from_modules($conf, $langs, null, $head, $h, 'planningproduction@planningproduction');

    complete_head_from_modules($conf, $langs, null, $head, $h, 'planningproduction@planningproduction', 'remove');

    return $head;
}
