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
dol_include_once('/planningproduction/class/airtablestock.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';

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

// ========== ACTIONS SYNCHRONISATION AIRTABLE (LECTURE SEULE) ==========
if ($action == 'update_airtable') {
    $error = 0;

    // Token : un champ vide conserve le token existant ; la case « Effacer » le supprime
    if (GETPOST('airtable_clear_token', 'int')) {
        dolibarr_set_const($db, 'PLANNINGPRODUCTION_AIRTABLE_TOKEN', '', 'chaine', 0, '', $conf->entity);
    } else {
        $new_token = trim(GETPOST('PLANNINGPRODUCTION_AIRTABLE_TOKEN', 'none'));
        if ($new_token !== '') {
            if (!preg_match('/^[A-Za-z0-9._-]{10,255}$/', $new_token)) {
                setEventMessages($langs->trans('AirtableTokenInvalidFormat'), null, 'errors');
                $error++;
            } else {
                dolibarr_set_const($db, 'PLANNINGPRODUCTION_AIRTABLE_TOKEN', dolEncrypt($new_token), 'chaine', 0, '', $conf->entity);
            }
        }
    }

    $base = trim(GETPOST('PLANNINGPRODUCTION_AIRTABLE_BASE', 'alphanohtml'));
    $table = trim(GETPOST('PLANNINGPRODUCTION_AIRTABLE_TABLE', 'alphanohtml'));
    $tolerance = str_replace(',', '.', trim(GETPOST('PLANNINGPRODUCTION_AIRTABLE_TOLERANCE', 'alphanohtml')));
    $ttl = trim(GETPOST('PLANNINGPRODUCTION_AIRTABLE_CACHE_TTL', 'alphanohtml'));

    if (!preg_match('/^app[A-Za-z0-9]{14}$/', $base)) {
        setEventMessages($langs->trans('AirtableBaseInvalid'), null, 'errors');
        $error++;
    }
    if (!preg_match('/^tbl[A-Za-z0-9]{14}$/', $table)) {
        setEventMessages($langs->trans('AirtableTableInvalid'), null, 'errors');
        $error++;
    }
    if (!is_numeric($tolerance) || (float) $tolerance < 0) {
        setEventMessages($langs->trans('AirtableToleranceInvalid'), null, 'errors');
        $error++;
    }
    if (!preg_match('/^\d+$/', $ttl) || (int) $ttl > 86400) {
        setEventMessages($langs->trans('AirtableCacheTtlInvalid'), null, 'errors');
        $error++;
    }

    if (!$error) {
        dolibarr_set_const($db, 'PLANNINGPRODUCTION_AIRTABLE_BASE', $base, 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($db, 'PLANNINGPRODUCTION_AIRTABLE_TABLE', $table, 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($db, 'PLANNINGPRODUCTION_AIRTABLE_TOLERANCE', (string) ((float) $tolerance), 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($db, 'PLANNINGPRODUCTION_AIRTABLE_CACHE_TTL', (string) ((int) $ttl), 'chaine', 0, '', $conf->entity);
        // La configuration a changé : vider le cache
        $airtable_tmp = new AirtableStock($db);
        $airtable_tmp->clearCache();
        setEventMessages($langs->trans('AirtableConfigSaved'), null, 'mesgs');
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}

if ($action == 'test_airtable') {
    $airtable_tmp = new AirtableStock($db);
    if (!$airtable_tmp->isConfigured()) {
        setEventMessages($langs->trans('AirtableNotConfigured'), null, 'errors');
    } else {
        // Appel de l'API meta (schéma) puis lecture des bobines, sans cache
        $choices_test = $airtable_tmp->getFilterChoices(true);
        $info_test = $airtable_tmp->getLoadInfo();
        if ($choices_test === false || !empty($info_test['stale'])) {
            setEventMessages($langs->trans('AirtableTestKo', $info_test['error']), null, 'errors');
        } else {
            $planning_tmp = new PlanningProduction($db);
            $matieres_tmp = $planning_tmp->getAllMatieres(true);
            $nb_with_filter = 0;
            foreach ((array) $matieres_tmp as $m) {
                if (!empty($m['airtable_filtre'])) {
                    $nb_with_filter++;
                }
            }
            setEventMessages($langs->trans('AirtableTestOk', $info_test['nb_bobines'], $nb_with_filter, is_array($matieres_tmp) ? count($matieres_tmp) : 0), null, 'mesgs');
        }
    }
}

// ========== ACTIONS SUR LES MATIÈRES PREMIÈRES ==========
// Traitées avant tout affichage : la redirection après enregistrement (header Location) échouait
// une fois l'en-tête de page envoyé, et la page s'arrêtait avant le tableau
$planning = new PlanningProduction($db);
$airtable = new AirtableStock($db);
if (in_array($action, array('add_matiere', 'delete_matiere', 'update_matiere', 'prefill_all_confirm'))) {
    $matieres = $planning->getAllMatieres(true);
    if ($matieres === false) {
        $matieres = array();
    }
}

// Traitement des actions sur les matières premières
if ($action == 'add_matiere' && $user->hasRight('planningproduction', 'planning', 'write')) {
    $new_code_mp = GETPOST('new_code_mp', 'alphanohtml');
    $new_stock = GETPOST('new_stock', 'alphanohtml');
    $new_lien = GETPOST('new_lien', 'alphanohtml');

    // Debug des valeurs reçues
    dol_syslog("add_matiere - new_code_mp: '".$new_code_mp."', new_stock: '".$new_stock."'", LOG_DEBUG);

    if (!empty($new_code_mp) && $new_stock !== '' && is_numeric($new_stock)) {
        $result = $planning->createMatiere($new_code_mp, (float)$new_stock, $new_lien);
        if ($result > 0) {
            // Filtre Airtable (seulement si les listes étaient actives dans le formulaire)
            if (GETPOST('atfnew_present', 'int')) {
                if ($planning->updateMatiereAirtableFiltre($result, planningproductionGetPostedAirtableFilter('atfnew_')) < 0) {
                    setEventMessages($langs->trans('AirtableFilterSaveError'), null, 'warnings');
                }
            }
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
    $lien = GETPOST('lien', 'alphanohtml');

    // Debug des valeurs reçues
    dol_syslog("update_matiere - rowid: '".$rowid."', code_mp: '".$code_mp."', stock: '".$stock."'", LOG_DEBUG);

    if ($rowid > 0 && !empty($code_mp) && $stock !== '' && is_numeric($stock)) {
        $result = $planning->updateMatiere($rowid, $code_mp, (float)$stock, $lien);
        if ($result > 0) {
            // Filtre Airtable : si les listes étaient désactivées (token absent, Airtable injoignable), le filtre enregistré est conservé
            if (GETPOST('atfedit_present', 'int')) {
                if ($planning->updateMatiereAirtableFiltre($rowid, planningproductionGetPostedAirtableFilter('atfedit_')) < 0) {
                    setEventMessages($langs->trans('AirtableFilterSaveError'), null, 'warnings');
                }
            }
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

// Pré-remplissage en masse : enregistrement après validation par l'admin
if ($action == 'prefill_all_confirm' && $user->hasRight('planningproduction', 'planning', 'write')) {
    $selected_rowids = GETPOST('prefill_rowid', 'array');
    $nb_saved = 0;
    foreach ($matieres as $m) {
        if (!in_array((string) $m['rowid'], array_map('strval', (array) $selected_rowids), true)) {
            continue;
        }
        // Ne jamais écraser un filtre déjà renseigné
        if (!empty($m['airtable_filtre']) || empty($m['lien'])) {
            continue;
        }
        $proposed = $airtable->parseInterfaceLink($m['lien']);
        if ($proposed !== false && $planning->updateMatiereAirtableFiltre($m['rowid'], $proposed) > 0) {
            $nb_saved++;
        }
    }
    setEventMessages($langs->trans('AirtablePrefillSaved', $nb_saved), null, 'mesgs');
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
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

// ========== SECTION SYNCHRONISATION AIRTABLE (LECTURE SEULE) ==========
$airtable = new AirtableStock($db);
$airtable_configured = $airtable->isConfigured();

print '<br><hr><br>';
print '<h3>'.$langs->trans('AirtableSectionTitle').'</h3>';
print '<p>'.$langs->trans('AirtableSectionDesc').'</p>';

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" autocomplete="off">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update_airtable">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

// Token (jamais réaffiché)
print '<tr class="oddeven"><td><strong>'.$langs->trans('AirtableToken').'</strong>';
print '<br><small>'.$langs->trans('AirtableTokenHelp').'</small></td><td>';
print '<input type="password" name="PLANNINGPRODUCTION_AIRTABLE_TOKEN" value="" autocomplete="new-password" class="minwidth300" placeholder="'.dol_escape_htmltag($airtable_configured ? $langs->trans('AirtableTokenKeep') : 'pat…').'"> ';
if ($airtable_configured) {
    print '<span style="color: #27ae60; font-weight: bold;">'.$langs->trans('AirtableTokenConfigured').'</span>';
    print '<br><label><input type="checkbox" name="airtable_clear_token" value="1"> '.$langs->trans('AirtableClearToken').'</label>';
} else {
    print '<span style="color: #c0392b; font-weight: bold;">'.$langs->trans('AirtableTokenNotConfigured').'</span>';
}
print '</td></tr>';

print '<tr class="oddeven"><td><strong>'.$langs->trans('AirtableBase').'</strong><br><small>PLANNINGPRODUCTION_AIRTABLE_BASE</small></td><td>';
print '<input type="text" name="PLANNINGPRODUCTION_AIRTABLE_BASE" value="'.dol_escape_htmltag($airtable->getBaseId()).'" class="minwidth200"></td></tr>';

print '<tr class="oddeven"><td><strong>'.$langs->trans('AirtableTable').'</strong><br><small>PLANNINGPRODUCTION_AIRTABLE_TABLE</small></td><td>';
print '<input type="text" name="PLANNINGPRODUCTION_AIRTABLE_TABLE" value="'.dol_escape_htmltag($airtable->getTableId()).'" class="minwidth200"></td></tr>';

print '<tr class="oddeven"><td><strong>'.$langs->trans('AirtableTolerance').'</strong><br><small>'.$langs->trans('AirtableToleranceHelp').'</small></td><td>';
print '<input type="text" name="PLANNINGPRODUCTION_AIRTABLE_TOLERANCE" value="'.dol_escape_htmltag(getDolGlobalString('PLANNINGPRODUCTION_AIRTABLE_TOLERANCE', '0.5')).'" style="width: 80px;"></td></tr>';

print '<tr class="oddeven"><td><strong>'.$langs->trans('AirtableCacheTtl').'</strong><br><small>'.$langs->trans('AirtableCacheTtlHelp').'</small></td><td>';
print '<input type="number" min="0" max="86400" name="PLANNINGPRODUCTION_AIRTABLE_CACHE_TTL" value="'.((int) AirtableStock::getCacheTtl()).'" style="width: 80px;"> s</td></tr>';

print '</table>';
print '<br><input type="submit" class="button" value="Sauvegarder">';
print '</form>';

// Tester la connexion
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" style="display: inline-block; margin-top: 10px;">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="test_airtable">';
print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('AirtableTestConnection')).'"'.($airtable_configured ? '' : ' disabled').'>';
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

// Choix Airtable pour les listes du filtre (via le cache) ; false si non configuré ou injoignable
$airtable_choices = $airtable_configured ? $airtable->getFilterChoices() : false;
$airtable_choices_error = '';
if ($airtable_choices === false) {
    $airtable_choices_error = $airtable_configured ? $airtable->getLoadInfo()['error'] : $langs->trans('AirtableNotConfigured');
}

// Formulaire d'ajout
if ($user->hasRight('planningproduction', 'planning', 'write')) {
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" style="margin-bottom: 20px;">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="add_matiere">';
    
    print '<table class="noborder" style="width: 800px;">';
    print '<tr class="liste_titre">';
    print '<td colspan="4">Ajouter une nouvelle matière première</td>';
    print '</tr>';
    print '<tr>';
    print '<td style="width: 200px;">Code MP :</td>';
    print '<td style="width: 150px;">Stock initial :</td>';
    print '<td style="width: 250px;">Lien :</td>';
    print '<td style="width: 100px;">Action</td>';
    print '</tr>';
    print '<tr>';
    print '<td><input type="text" name="new_code_mp" placeholder="Ex: 400 BLANC" required style="width: 180px;"></td>';
    print '<td><input type="number" name="new_stock" step="0.01" value="0" style="width: 120px;"></td>';
    print '<td><input type="url" name="new_lien" id="new_lien" placeholder="https://..." style="width: 240px;"></td>';
    print '<td><input type="submit" value="Ajouter" class="button"></td>';
    print '</tr>';
    print '<tr><td colspan="4">';
    planningproductionPrintAirtableFilterBlock($form, $airtable, $airtable_choices, $airtable_choices_error, 'atfnew_', array(), 'new_lien');
    print '</td></tr>';
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

// Pré-remplissage de tous les filtres vides depuis les liens (proposition, rien n'est enregistré sans validation)
if ($user->hasRight('planningproduction', 'planning', 'write') && count($matieres) > 0) {
    if ($action == 'prefill_all_preview') {
        print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="action" value="prefill_all_confirm">';
        print '<table class="noborder centpercent" style="margin-bottom: 10px;">';
        print '<tr class="liste_titre"><td colspan="5">'.$langs->trans('AirtablePrefillTitle').'</td></tr>';
        print '<tr class="liste_titre"><td width="30"></td><td>Code MP</td><td>'.$langs->trans('AirtableProposedFilter').'</td><td class="right">'.$langs->trans('AirtableNbBobines').'</td><td class="right">'.$langs->trans('AirtableSurface').'</td></tr>';
        $nb_proposed = 0;
        foreach ($matieres as $m) {
            if (!empty($m['airtable_filtre'])) {
                continue;
            }
            $proposed = !empty($m['lien']) ? $airtable->parseInterfaceLink($m['lien']) : false;
            print '<tr class="oddeven">';
            if ($proposed === false) {
                print '<td></td><td><strong>'.dol_escape_htmltag($m['code_mp']).'</strong></td>';
                print '<td colspan="3" class="opacitymedium">'.(empty($m['lien']) ? $langs->trans('AirtableNoLink') : $langs->trans('AirtableLinkNotDecodable')).'</td>';
            } else {
                $nb_proposed++;
                $desc = $airtable->describeFilter($proposed, $airtable_choices);
                $calc = $airtable->computeForFilter($proposed);
                print '<td><input type="checkbox" name="prefill_rowid[]" value="'.((int) $m['rowid']).'" checked></td>';
                print '<td><strong>'.dol_escape_htmltag($m['code_mp']).'</strong></td>';
                print '<td>'.dol_escape_htmltag($desc['text']).($desc['invalid'] ? ' <span style="color: #e67e22;" title="'.dol_escape_htmltag($desc['message']).'">⚠</span>' : '').'</td>';
                if ($calc['status'] == 'ok') {
                    print '<td class="right">'.((int) $calc['nb_bobines']).'</td><td class="right">'.price($calc['surface'], 0, '', 1, 2, 2).' m²</td>';
                } else {
                    print '<td colspan="2" class="right opacitymedium">'.dol_escape_htmltag($calc['message']).'</td>';
                }
            }
            print '</tr>';
        }
        if ($nb_proposed == 0) {
            print '<tr><td colspan="5" class="opacitymedium">'.$langs->trans('AirtablePrefillNothing').'</td></tr>';
        }
        print '</table>';
        if ($nb_proposed > 0) {
            print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('AirtablePrefillConfirm')).'"> ';
        }
        print '<a href="'.$_SERVER["PHP_SELF"].'" class="button button-cancel">Annuler</a>';
        print '</form><br>';
    } else {
        print '<div style="margin-bottom: 10px;">';
        print '<a href="'.$_SERVER["PHP_SELF"].'?action=prefill_all_preview&token='.newToken().'" class="button">'.$langs->trans('AirtablePrefillAll').'</a>';
        print '</div>';
    }
}

// En mode édition, le formulaire englobe le tableau (les autres lignes ne contiennent que des liens)
$edit_matiere_id = GETPOSTINT('edit_matiere');
$is_editing = ($user->hasRight('planningproduction', 'planning', 'write') && $edit_matiere_id > 0);
if ($is_editing) {
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" style="margin: 0;">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="update_matiere">';
    print '<input type="hidden" name="rowid" value="'.((int) $edit_matiere_id).'">';
}

print '<table class="noborder centpercent matieres-table" id="matieres-sortable">';
print '<tr class="liste_titre">';
print '<td>Code MP</td>';
print '<td>Stock</td>';
print '<td>Lien</td>';
print '<td>'.$langs->trans('AirtableFilter').'</td>';
print '<td>Dernière MàJ</td>';
if ($user->hasRight('planningproduction', 'planning', 'write')) {
    print '<td width="150">Actions</td>';
}
print '</tr>';

if (count($matieres) == 0) {
    print '<tr><td colspan="'.(($user->hasRight('planningproduction', 'planning', 'write')) ? '6' : '5').'" class="opacitymedium">Aucune matière première configurée</td></tr>';
} else {
    foreach ($matieres as $matiere) {
        print '<tr class="oddeven" data-matiere-id="'.$matiere['rowid'].'" data-ordre="'.($matiere['ordre'] ?? 0).'">';
        
        // Edition en ligne pour les utilisateurs autorisés
        if ($is_editing && $edit_matiere_id == $matiere['rowid']) {
            print '<td><input type="text" name="code_mp" value="'.htmlspecialchars($matiere['code_mp']).'" required style="width: 100%;"></td>';
            print '<td><input type="number" name="stock" value="'.$matiere['stock'].'" step="0.01" style="width: 100px;"></td>';
            print '<td><input type="url" name="lien" id="edit_lien" value="'.htmlspecialchars($matiere['lien'] ?? '').'" placeholder="https://..." style="width: 200px;"></td>';
            print '<td>'.planningproductionAirtableFilterSummaryHtml($airtable, $matiere['airtable_filtre'], $airtable_choices).'</td>';
            print '<td>'.dol_print_date($matiere['date_maj'], 'dayhour').'</td>';
            print '<td>';
            print '<input type="submit" value="Sauver" class="button" style="margin-right: 5px;">';
            print '<a href="'.$_SERVER["PHP_SELF"].'" class="button">Annuler</a>';
            print '</td>';
            print '</tr>';
            // Bloc « Filtre Airtable » sous la ligne en édition
            print '<tr class="oddeven"><td colspan="6">';
            planningproductionPrintAirtableFilterBlock($form, $airtable, $airtable_choices, $airtable_choices_error, 'atfedit_', $matiere['airtable_filtre'], 'edit_lien');
            print '</td>';
        } else {
            // Affichage normal
            $drag_handle = '';
            if ($user->hasRight('planningproduction', 'planning', 'write') && count($matieres) > 1 && !$is_editing) {
                $drag_handle = '<span class="drag-handle" title="Glisser pour réorganiser"><i class="fa fa-bars"></i></span>';
            }
            print '<td>'.$drag_handle.'<strong>'.htmlspecialchars($matiere['code_mp']).'</strong></td>';
            print '<td>'.number_format($matiere['stock'], 2).'</td>';
            if (!empty($matiere['lien'])) {
                print '<td><a href="'.htmlspecialchars($matiere['lien']).'" target="_blank" title="'.htmlspecialchars($matiere['lien']).'">'.dol_trunc(htmlspecialchars($matiere['lien']), 30).'</a></td>';
            } else {
                print '<td>-</td>';
            }
            print '<td>'.planningproductionAirtableFilterSummaryHtml($airtable, $matiere['airtable_filtre'], $airtable_choices).'</td>';
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
if ($is_editing) {
    print '</form>';
}

// JavaScript du filtre Airtable (pré-remplissage depuis le lien, aperçu n bobines / surface)
if ($user->hasRight('planningproduction', 'planning', 'write')) {
    planningproductionPrintAirtableFilterJs();
}

// Initialisation du drag & drop : URL et token générés côté PHP (respecte DOL_URL_ROOT et le dossier custom réel)
// Désactivé pendant l'édition d'une matière (le bloc « Filtre Airtable » occupe une ligne supplémentaire)
if ($user->hasRight('planningproduction', 'planning', 'write') && count($matieres) > 1 && !$is_editing) {
    print '<script type="text/javascript">'."\n";
    print 'if (typeof MatieresOrderManager !== "undefined") {'."\n";
    print 'window.matieresOrderManager = new MatieresOrderManager("#matieres-sortable", {'
        .'saveUrl: '.json_encode(dol_buildpath('/planningproduction/ajax_matieres_order.php', 1)).', '
        .'token: '.json_encode(newToken())
        .'});'."\n";
    print '} else {'."\n";
    print '    console.warn("MatieresOrderManager non disponible - Vérifiez le chargement de matieres_order.js");'."\n";
    print '}'."\n";
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

/**
 * Lire le filtre Airtable posté par le formulaire d'une matière
 *
 * @param string $prefix Préfixe des listes (atfnew_ ou atfedit_)
 * @return array ['fldXXX' => ['selA', …], …] (validé ensuite par updateMatiereAirtableFiltre)
 */
function planningproductionGetPostedAirtableFilter($prefix)
{
    $filtre = array();
    foreach (AirtableStock::getFilterFields() as $field_id => $label) {
        $values = GETPOST($prefix.$field_id, 'array');
        if (is_array($values) && count($values) > 0) {
            $filtre[$field_id] = array_values(array_map('strval', $values));
        }
    }
    return $filtre;
}

/**
 * Résumé lisible du filtre Airtable d'une matière (colonne « Filtre Airtable »)
 *
 * @param AirtableStock $airtable Instance Airtable
 * @param array         $filtre   Filtre de la matière
 * @param array|false   $choices  Choix Airtable, false si indisponibles
 * @return string HTML
 */
function planningproductionAirtableFilterSummaryHtml($airtable, $filtre, $choices)
{
    global $langs;

    if (empty($filtre)) {
        return '<span class="opacitymedium">—</span>';
    }
    $desc = $airtable->describeFilter($filtre, ($choices === false) ? null : $choices);
    $html = dol_escape_htmltag($desc['text']);
    if ($desc['invalid']) {
        $html .= ' <span style="color: #e67e22; font-weight: bold;" title="'.dol_escape_htmltag($desc['message']).'">⚠ '.$langs->trans('AirtableInvalidFilter').'</span>';
    }
    return $html;
}

/**
 * Afficher le bloc « Filtre Airtable » (4 listes à sélection multiple) du formulaire d'une matière
 *
 * Si Airtable n'est pas disponible, les listes sont désactivées et le filtre enregistré est conservé tel quel
 * (le champ <prefix>present n'est pas envoyé).
 *
 * @param Form          $form          Formulaire Dolibarr
 * @param AirtableStock $airtable      Instance Airtable
 * @param array|false   $choices       Sortie de getFilterChoices(), false si indisponible
 * @param string        $choices_error Message à afficher si indisponible
 * @param string        $prefix        Préfixe des listes (atfnew_ ou atfedit_)
 * @param array         $selected      Filtre déjà enregistré
 * @param string        $lien_input_id ID du champ « lien » servant au pré-remplissage
 * @return void
 */
function planningproductionPrintAirtableFilterBlock($form, $airtable, $choices, $choices_error, $prefix, $selected, $lien_input_id)
{
    global $langs;

    $selected = is_array($selected) ? $selected : array();

    print '<div class="atf-block" data-prefix="'.dol_escape_htmltag($prefix).'" data-lien-input="'.dol_escape_htmltag($lien_input_id).'" style="padding: 8px; border: 1px dashed #b39ddb; border-radius: 4px; background: #faf7fc;">';
    print '<strong>'.$langs->trans('AirtableFilter').'</strong> <small class="opacitymedium">'.$langs->trans('AirtableFilterHelp').'</small>';

    if ($choices === false) {
        print '<div class="warning" style="margin: 6px 0;">'.$langs->trans('AirtableFilterUnavailable').' : '.dol_escape_htmltag($choices_error).'</div>';
        if (!empty($selected)) {
            print '<small>'.$langs->trans('AirtableFilterKept').'</small>';
        }
        print '<div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 6px;">';
        foreach (AirtableStock::getFilterFields() as $field_id => $label) {
            print '<div style="flex: 1 1 200px;"><label class="opacitymedium">'.dol_escape_htmltag($label).'</label><br>';
            print '<select multiple disabled style="width: 100%;"></select></div>';
        }
        print '</div></div>';
        return;
    }

    print '<input type="hidden" name="'.dol_escape_htmltag($prefix).'present" value="1">';
    print '<div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 6px;">';
    foreach ($choices as $field_id => $def) {
        $options = array();
        foreach ($def['choices'] as $choice) {
            $options[$choice['id']] = $choice['name'];
        }
        $sel = isset($selected[$field_id]) ? $selected[$field_id] : array();
        // Un choix enregistré mais supprimé dans Airtable reste visible pour pouvoir le retirer
        foreach ($sel as $id) {
            if (!isset($options[$id])) {
                $options[$id] = '⚠ Choix supprimé ('.$id.')';
            }
        }
        print '<div style="flex: 1 1 200px; min-width: 180px;"><label>'.dol_escape_htmltag($def['label']).'</label><br>';
        print $form->multiselectarray($prefix.$field_id, $options, $sel, 0, 0, 'atf-select', 0, '100%');
        print '</div>';
    }
    print '</div>';
    print '<div style="margin-top: 6px;">';
    print '<button type="button" class="button smallpaddingimp atf-prefill">'.$langs->trans('AirtablePrefillFromLink').'</button> ';
    print '<span class="atf-preview" style="font-weight: bold; color: #6c3483;"></span> ';
    print '<span class="atf-msg" style="color: #c0392b;"></span>';
    print '</div>';
    print '</div>';
}

/**
 * JavaScript des blocs « Filtre Airtable » : pré-remplissage depuis le lien et aperçu (AJAX)
 * Le token Airtable n'est jamais envoyé au navigateur : seules les actions admin d'ajax_matieres.php sont appelées.
 *
 * @return void
 */
function planningproductionPrintAirtableFilterJs()
{
    print '<script type="text/javascript">'."\n";
    print 'var ATF_AJAX_URL = '.json_encode(dol_buildpath('/planningproduction/ajax_matieres.php', 1)).';'."\n";
    print 'var ATF_TOKEN = '.json_encode(newToken()).';'."\n";
    print 'var ATF_FIELDS = '.json_encode(array_keys(AirtableStock::getFilterFields())).';'."\n";
    print <<<'JS'
(function() {
    function post(params) {
        params.token = ATF_TOKEN;
        return fetch(ATF_AJAX_URL, { method: 'POST', body: new URLSearchParams(params) })
            .then(function(r) { return r.text(); })
            .then(function(t) {
                try { return JSON.parse(t); } catch (e) { return { success: false, message: 'Réponse invalide du serveur' }; }
            })
            .catch(function() { return { success: false, message: 'Erreur de communication' }; });
    }
    function blockFilter(block) {
        var filtre = {};
        ATF_FIELDS.forEach(function(fid) {
            var sel = document.getElementById(block.dataset.prefix + fid);
            if (!sel) return;
            var ids = Array.prototype.filter.call(sel.options, function(o) { return o.selected; }).map(function(o) { return o.value; });
            if (ids.length) filtre[fid] = ids;
        });
        return filtre;
    }
    function setMsg(block, text) {
        var el = block.querySelector('.atf-msg');
        if (el) el.textContent = text || '';
    }
    function fmt(n) {
        return Number(n || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function updatePreview(block) {
        var out = block.querySelector('.atf-preview');
        if (!out) return;
        var filtre = blockFilter(block);
        if (Object.keys(filtre).length === 0) {
            out.textContent = '→ aucun critère : matière non comparée';
            return;
        }
        out.textContent = '…';
        post({ action: 'preview_airtable_filtre', filtre: JSON.stringify(filtre) }).then(function(r) {
            if (!r.success) { out.textContent = ''; setMsg(block, r.message); return; }
            var d = r.data;
            if (d.status === 'ok') {
                out.textContent = '→ ' + d.nb_bobines + ' bobine(s), ' + fmt(d.surface) + ' m²' + (d.message ? ' (' + d.message + ')' : '');
            } else {
                out.textContent = '⚠ ' + (d.message || d.status);
            }
        });
    }
    var timers = {};
    jQuery(document).on('change', '.atf-block select.atf-select', function() {
        var block = this.closest('.atf-block');
        var key = block.dataset.prefix;
        clearTimeout(timers[key]);
        timers[key] = setTimeout(function() { updatePreview(block); }, 300);
    });
    jQuery(document).on('click', '.atf-block .atf-prefill', function() {
        var block = this.closest('.atf-block');
        var input = document.getElementById(block.dataset.lienInput);
        var lien = input ? input.value.trim() : '';
        setMsg(block, '');
        if (!lien) { setMsg(block, 'Aucun lien renseigné'); return; }
        post({ action: 'parse_airtable_link', lien: lien }).then(function(r) {
            if (!r.success) { setMsg(block, r.message || 'Lien non décodable, sélection manuelle'); return; }
            var missing = false;
            ATF_FIELDS.forEach(function(fid) {
                var sel = document.getElementById(block.dataset.prefix + fid);
                if (!sel) return;
                var ids = r.data.filtre[fid] || [];
                var available = ids.filter(function(id) { return sel.querySelector('option[value="' + id + '"]'); });
                if (available.length < ids.length) missing = true;
                // Coche les choix du lien sans enregistrer (select2 suit via l'événement change)
                jQuery(sel).val(available).trigger('change');
            });
            setMsg(block, missing ? 'Certains choix du lien n\'existent plus dans Airtable' : '');
        });
    });
    jQuery(function() {
        document.querySelectorAll('.atf-block').forEach(function(block) {
            if (block.querySelector('select.atf-select')) updatePreview(block);
        });
    });
})();
JS;
    print "\n".'</script>'."\n";
}
