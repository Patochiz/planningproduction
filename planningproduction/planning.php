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
 * \file    planning.php
 * \ingroup planningproduction
 * \brief   Planning de production avec panneau à onglets
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
dol_include_once('/planningproduction/class/planningproduction.class.php');
dol_include_once('/planningproduction/lib/planning_functions.php');

// Translations
$langs->loadLangs(array("planningproduction@planningproduction", "other"));

// Security check
if (!$user->hasRight('planningproduction', 'planning', 'read')) {
    accessforbidden();
}

$action = GETPOST('action', 'aZ09');

// Parameters
$year = GETPOST('year', 'int') ? GETPOST('year', 'int') : date('Y');
$week_count = GETPOST('week_count', 'int') ? GETPOST('week_count', 'int') : 5;
$start_week = GETPOST('start_week', 'int') ? GETPOST('start_week', 'int') : date('W');

// Initialize technical objects
$object = new PlanningProduction($db);
$extrafields = new ExtraFields($db);

/*
 * Actions
 */

// None

/*
 * View
 */

$form = new Form($db);

$title = $langs->trans('PlanningHybride');
$help_url = '';

// Header Dolibarr standard sans ressources externes
llxHeader('', $title, $help_url);

// Ajouter une classe wrapper pour scoper les styles CSS au planning uniquement
print '<script type="text/javascript">document.body.classList.add("planning-page");</script>'."\n";

// === INCLUSION DIRECTE DES RESSOURCES ===
// Inclure les CSS avec variable de largeur des cartes
$card_width = getDolGlobalString('PLANNINGPRODUCTION_CARD_WIDTH', '260');

// Calculer la largeur du panneau en fonction de la largeur des cartes
// Formule : largeur_carte + marges (30px) + scrollbar (20px) = largeur_panneau
$panel_width = intval($card_width) + 50; // 30px de marge + 20px pour scrollbar
$panel_width_collapsed = 50; // Largeur réduite fixe

// CSS avec variable personnalisée
print '<style type="text/css">'."\n";
print ':root {'."\n";
print '  --planning-card-width: '.$card_width.'px;'."\n";
print '  --planning-panel-width: '.$panel_width.'px;'."\n";
print '  --planning-panel-collapsed-width: '.$panel_width_collapsed.'px;'."\n";
print '}'."\n";

// Styles pour le panneau des onglets
print '.tabs-column {'."\n";
print '  width: var(--planning-panel-width) !important;'."\n";
print '  min-width: var(--planning-panel-width) !important;'."\n";
print '}'."\n";

print '.tabs-column.collapsed {'."\n";
print '  width: var(--planning-panel-collapsed-width) !important;'."\n";
print '  min-width: var(--planning-panel-collapsed-width) !important;'."\n";
print '}'."\n";

// Responsive : sur mobile, toujours pleine largeur
print '@media (max-width: 768px) {'."\n";
print '  .tabs-column {'."\n";
print '    width: 100% !important;'."\n";
print '    min-width: unset !important;'."\n";
print '  }'."\n";
print '  .tabs-column.collapsed {'."\n";
print '    width: 100% !important;'."\n";
print '    min-width: unset !important;'."\n";
print '    height: 60px;'."\n";
print '  }'."\n";
print '}'."\n";

print '</style>'."\n";

// Inclure les CSS externes
print '<link rel="stylesheet" type="text/css" href="'.dol_buildpath('/planningproduction/css/planning.css', 1).'?v='.time().'" />'."\n";
print '<link rel="stylesheet" type="text/css" href="'.dol_buildpath('/planningproduction/css/planning_tabs.css', 1).'?v='.time().'" />'."\n";

// Inclure les JS dans le bon ordre
print '<script type="text/javascript" src="'.dol_buildpath('/planningproduction/js/planning.js', 1).'?v='.time().'"></script>'."\n";
print '<script type="text/javascript" src="'.dol_buildpath('/planningproduction/js/tabs.js', 1).'?v='.time().'"></script>'."\n";
print '<script type="text/javascript" src="'.dol_buildpath('/planningproduction/js/modal.js', 1).'?v='.time().'"></script>'."\n";
print '<script type="text/javascript" src="'.dol_buildpath('/planningproduction/js/matieres.js', 1).'?v='.time().'"></script>'."\n";
print '<script type="text/javascript" src="'.dol_buildpath('/planningproduction/js/dragdrop.js', 1).'?v='.time().'"></script>'."\n";
print '<script type="text/javascript" src="'.dol_buildpath('/planningproduction/js/events.js', 1).'?v='.time().'"></script>'."\n";

// Récupérer les cartes par catégorie
$unplanned_cards = $object->getUnplannedCards();
if ($unplanned_cards === false) {
    setEventMessages($object->errors, null, 'errors');
    $unplanned_cards = array();
}

$cards_to_finish = $object->getCardsToFinish();
if ($cards_to_finish === false) {
    setEventMessages($object->errors, null, 'errors');
    $cards_to_finish = array();
}

$cards_to_ship = $object->getCardsToShip();
if ($cards_to_ship === false) {
    setEventMessages($object->errors, null, 'errors');
    $cards_to_ship = array();
}

// Récupérer les cartes planifiées
$planned_cards = $object->getPlannedCards($start_week, $week_count, $year);
if ($planned_cards === false) {
    setEventMessages($object->errors, null, 'errors');
    $planned_cards = array();
}

?>

<!-- SIDEBAR GAUCHE AVEC CONTRÔLES -->
<div class="planning-sidebar">
    <div class="sidebar-header">
        <h2 class="sidebar-title">📋 <?php echo $langs->trans('PlanningHybride'); ?></h2>
        <p class="sidebar-subtitle"><?php echo $langs->trans('TimelineGroupement'); ?></p>
    </div>
    
    <div class="sidebar-section">
        <h3 class="sidebar-section-title">Actions globales</h3>
        <div class="sidebar-buttons">
            <button class="sidebar-btn btn-config" onclick="window.open('<?php echo dol_buildpath('/planningproduction/admin/setup.php', 1); ?>', '_blank')">⚙️ <?php echo $langs->trans('Configuration'); ?></button>
            <button class="sidebar-btn btn-matieres" onclick="openMatieresModal()">🧱 <?php echo $langs->trans('MatieresPremieresTitle', 'Matières'); ?></button>
            <button class="sidebar-btn btn-export" onclick="exportGlobal()">📊 <?php echo $langs->trans('ExportGlobal'); ?></button>
            <button class="sidebar-btn btn-sync" onclick="synchroniser()">🔄 <?php echo $langs->trans('Synchroniser'); ?></button>
        </div>
    </div>
    
    <form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="sidebar-form">
        <input type="hidden" name="token" value="<?php echo newToken(); ?>">
        
        <div class="sidebar-section">
            <h3 class="sidebar-section-title">Période</h3>
            
            <div class="sidebar-field">
                <label><?php echo $langs->trans('Annee'); ?></label>
                <select name="year" id="yearSelect" class="sidebar-select">
                    <option value="<?php echo $year; ?>" selected><?php echo $year; ?></option>
                    <option value="<?php echo $year - 1; ?>"><?php echo $year - 1; ?></option>
                    <option value="<?php echo $year + 1; ?>"><?php echo $year + 1; ?></option>
                </select>
            </div>
            
            <div class="sidebar-field">
                <label><?php echo $langs->trans('NombreSemaines'); ?></label>
                <select name="week_count" id="weekCountSelect" class="sidebar-select">
                    <option value="3" <?php echo ($week_count == 3) ? 'selected' : ''; ?>>3 semaines</option>
                    <option value="5" <?php echo ($week_count == 5) ? 'selected' : ''; ?>>5 semaines</option>
                    <option value="8" <?php echo ($week_count == 8) ? 'selected' : ''; ?>>8 semaines</option>
                </select>
            </div>
            
            <div class="sidebar-field">
                <label><?php echo $langs->trans('SemaineDepart'); ?></label>
                <div class="week-selector">
                    <button type="button" class="week-nav-btn" onclick="navigatePrevWeek()">◀</button>
                    <select name="start_week" id="startWeekSelect" class="sidebar-select">
                        <?php
                        for ($w = 1; $w <= 52; $w++) {
                            $selected = ($w == $start_week) ? 'selected' : '';
                            $week_str = sprintf('%02d', $w);
                            echo "<option value=\"$w\" $selected>S$week_str</option>";
                        }
                        ?>
                    </select>
                    <button type="button" class="week-nav-btn" onclick="navigateNextWeek()">▶</button>
                </div>
            </div>
        </div>
        
        <div class="sidebar-section">
            <h3 class="sidebar-section-title">Filtres</h3>
            
            <div class="sidebar-field">
                <label><?php echo $langs->trans('Client'); ?></label>
                <select class="sidebar-select">
                    <option><?php echo $langs->trans('TousLesClients'); ?></option>
                </select>
            </div>
            
            <div class="sidebar-field">
                <label><?php echo $langs->trans('Recherche'); ?></label>
                <input type="text" class="sidebar-input" placeholder="<?php echo $langs->trans('RechercheReference'); ?>">
            </div>
        </div>
        
        <div class="sidebar-submit">
            <button type="submit" class="sidebar-btn btn-primary btn-full">🔍 <?php echo $langs->trans('Filtrer'); ?> / <?php echo $langs->trans('Actualiser'); ?></button>
        </div>
    </form>
</div>

<!-- ZONE PRINCIPALE -->
<div class="planning-main-area">
    <div class="planning-container">
        
        <!-- COLONNE ONGLETS -->
        <div class="tabs-column collapsed" id="tabsColumn" data-active-tab="unplanned">
            <div class="tabs-header" onclick="toggleTabsPanel()">
                <div class="tabs-header-content">
                    <span>📋 Cartes par statut</span>
                    <span class="tab-total-count"><?php echo count($unplanned_cards) + count($cards_to_finish) + count($cards_to_ship); ?></span>
                </div>
                <button class="tabs-toggle" id="tabsToggle" title="Afficher le panneau">▶</button>
            </div>
            
            <div class="tabs-nav">
                <button class="tab-button active" data-tab="unplanned">
                    Non planifiées
                    <span class="tab-badge"><?php echo count($unplanned_cards); ?></span>
                </button>
                <button class="tab-button" data-tab="to-finish">
                    À terminer
                    <span class="tab-badge"><?php echo count($cards_to_finish); ?></span>
                </button>
                <button class="tab-button" data-tab="to-ship">
                    À expédier
                    <span class="tab-badge"><?php echo count($cards_to_ship); ?></span>
                </button>
            </div>
            
            <!-- Onglet Non planifiées -->
            <div class="tab-content active" data-tab="unplanned">
                <div class="tab-body">
                    <?php
                    foreach ($unplanned_cards as $card) {
                        echo generateCardHTML($card, $langs);
                    }
                    ?>
                </div>
                <div class="tabs-stats">
                    <?php echo count($unplanned_cards) . ' ' . $langs->trans('Elements'); ?>
                </div>
            </div>
            
            <!-- Onglet À terminer -->
            <div class="tab-content" data-tab="to-finish">
                <div class="tab-body">
                    <?php
                    foreach ($cards_to_finish as $card) {
                        echo generateCardHTML($card, $langs);
                    }
                    ?>
                </div>
                <div class="tabs-stats">
                    <?php echo count($cards_to_finish) . ' ' . $langs->trans('Elements'); ?> - Statut : <?php echo $langs->trans('ATerminer'); ?>
                </div>
            </div>
            
            <!-- Onglet À expédier -->
            <div class="tab-content" data-tab="to-ship">
                <div class="tab-body">
                    <?php
                    foreach ($cards_to_ship as $card) {
                        echo generateCardHTML($card, $langs);
                    }
                    ?>
                </div>
                <div class="tabs-stats">
                    <?php echo count($cards_to_ship) . ' ' . $langs->trans('Elements'); ?> - Statut : <?php echo $langs->trans('BonPourExpedition'); ?>
                </div>
            </div>
        </div>

        <!-- TIMELINE CONTAINER -->
        <div class="timeline-container">
            <?php
            for ($i = 0; $i < $week_count; $i++) {
                $week_num = $start_week + $i;
                if ($week_num > 52) break;
                
                $week_data = isset($planned_cards[$week_num]) ? $planned_cards[$week_num] : array('elements' => 0, 'groups' => 0, 'cards' => array());
                echo generateWeekHTML($week_num, $year, $week_data, $langs);
            }
            ?>
        </div>
        
    </div>
</div>

<!-- MODAL D'ÉDITION -->
<div class="edit-modal" id="editModal">
    <div class="edit-modal-content">
        <div class="edit-modal-header">
            <h3 class="edit-modal-title"><?php echo $langs->trans('EditerCarte'); ?></h3>
            <button class="edit-modal-close" onclick="closeEditModal()">×</button>
        </div>

        <div class="edit-current-values">
            <div class="edit-current-title"><?php echo $langs->trans('CarteActuelle'); ?></div>
            <div class="edit-current-item">
                <span class="edit-current-label"><?php echo $langs->trans('Client'); ?>:</span>
                <span class="edit-current-value" id="editCurrentTitle">-</span>
            </div>
            <div class="edit-current-item">
                <span class="edit-current-label"><?php echo $langs->trans('Reference'); ?>:</span>
                <span class="edit-current-value" id="editCurrentClient">-</span>
            </div>
            <div class="edit-current-item">
                <span class="edit-current-label"><?php echo $langs->trans('Produit'); ?>:</span>
                <span class="edit-current-value" id="editCurrentOrder">-</span>
            </div>
        </div>

        <form id="editForm">
            <div class="edit-form-group">
                <label class="edit-form-label" for="editMatiere"><?php echo $langs->trans('Matiere'); ?></label>
                <input type="text" id="editMatiere" class="edit-form-input" placeholder="<?php echo $langs->trans('SaisirMatiere'); ?>">
            </div>

            <div class="edit-form-group">
                <label class="edit-form-label" for="editMpStatus"><?php echo $langs->trans('StatutMatierePremiere'); ?></label>
                <select id="editMpStatus" class="edit-form-select" onchange="updateBadgePreview('mp', this.value)">
                    <option value="">-- <?php echo $langs->trans('Selectionner'); ?> --</option>
                    <option value="MP Ok,MP Ok"><?php echo $langs->trans('MPOk'); ?></option>
                    <option value="MP en attente,MP en attente"><?php echo $langs->trans('MPAttente'); ?></option>
                    <option value="MP Manquante,MP Manquante"><?php echo $langs->trans('MPManquante'); ?></option>
                    <option value="BL A FAIRE,BL A FAIRE"><?php echo $langs->trans('BLAFaire'); ?></option>
                    <option value="PROFORMA A VALIDER,PROFORMA A VALIDER"><?php echo $langs->trans('ProformaAValider'); ?></option>
                    <option value="MàJ AIRTABLE à Faire,MàJ AIRTABLE à Faire"><?php echo $langs->trans('MAJAirtableAFaire'); ?></option>
                </select>
                <span class="edit-badge-preview" id="mpStatusPreview"></span>
            </div>

            <div class="edit-form-group">
                <label class="edit-form-label" for="editProductionStatus"><?php echo $langs->trans('StatutProduction'); ?></label>
                <select id="editProductionStatus" class="edit-form-select">
                    <option value="À PRODUIRE"><?php echo $langs->trans('AProdure'); ?></option>
                    <option value="EN COURS"><?php echo $langs->trans('EnCours'); ?></option>
                    <option value="À TERMINER"><?php echo $langs->trans('ATerminer'); ?></option>
                    <option value="BON POUR EXPÉDITION"><?php echo $langs->trans('BonPourExpedition'); ?></option>
                </select>
            </div>

            <div class="edit-form-group">
                <label class="edit-form-label" for="editPeindre"><?php echo $langs->trans('APeindre'); ?></label>
                <select id="editPeindre" class="edit-form-select">
                    <option value="non"><?php echo $langs->trans('Non'); ?></option>
                    <option value="oui"><?php echo $langs->trans('Oui'); ?></option>
                </select>
                <small style="color: #7f8c8d; font-size: 12px; margin-top: 5px; display: block;">
                    <?php echo $langs->trans('SiFondJauneFluo'); ?>
                </small>
            </div>
        </form>

        <div class="edit-modal-actions">
            <button type="button" class="edit-btn edit-btn-cancel" onclick="closeEditModal()"><?php echo $langs->trans('Annuler'); ?></button>
            <button type="button" class="edit-btn edit-btn-save" onclick="saveCardEdit()"><?php echo $langs->trans('Sauvegarder'); ?></button>
        </div>
    </div>
</div>

<!-- MODAL MATIÈRES PREMIÈRES -->
<div class="matiere-modal" id="matieresModal">
    <div class="matiere-modal-content">
        <div class="matiere-modal-header">
            <h3 class="matiere-modal-title">🧱 <?php echo $langs->trans('MatieresPremieresTitle', 'Matières Premières'); ?></h3>
            <button class="matiere-modal-close" onclick="closeMatieresModal()">×</button>
        </div>

        <div class="matiere-modal-body">
            <p style="margin-bottom: 20px; color: #666;">
                Tableau récapitulatif des stocks de matières premières principales.<br>
                <small>Cliquez sur "MàJ" pour recalculer les commandes en cours.</small>
            </p>
            
            <div id="matieresTableContainer">
                <div class="loading-spinner" style="text-align: center; padding: 50px;">
                    <div style="display: inline-block; width: 40px; height: 40px; border: 3px solid #f3f3f3; border-top: 3px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                    <p>Chargement des données...</p>
                </div>
            </div>
        </div>

        <div class="matiere-modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeMatieresModal()"><?php echo $langs->trans('Fermer'); ?></button>
            <button type="button" class="btn btn-primary" onclick="refreshMatieresData()">🔄 Actualiser les données</button>
        </div>
    </div>
</div>

<style>
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.matiere-modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.6);
    backdrop-filter: blur(3px);
}

.matiere-modal-content {
    position: relative;
    background-color: #fefefe;
    margin: 2% auto;
    padding: 0;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    width: 95%;
    max-width: 1200px;
    max-height: 90vh;
    overflow: hidden;
    animation: fadeInUp 0.3s ease-out;
}

.matiere-modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e0e0e0;
}

.matiere-modal-title {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

.matiere-modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.matiere-modal-close:hover {
    background-color: rgba(255,255,255,0.2);
    transform: rotate(90deg);
}

.matiere-modal-body {
    padding: 25px;
    max-height: 60vh;
    overflow-y: auto;
}

.matiere-modal-actions {
    background-color: #f8f9fa;
    padding: 15px 25px;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    gap: 10px;
}

.matieres-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.matieres-table th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    padding: 15px 12px;
    text-align: left;
    border: none;
}

.matieres-table td {
    padding: 12px;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
}

.matieres-table tbody tr:hover {
    background-color: #f8f9fa;
}

.matieres-table tbody tr:last-child td {
    border-bottom: none;
}

.stock-editable {
    background: none;
    border: 1px solid #ddd;
    padding: 8px 10px;
    border-radius: 4px;
    font-size: 14px;
    width: 80px;
    text-align: right;
    transition: all 0.2s ease;
}

.stock-editable:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    background-color: #fff;
}

.btn-update-cde {
    background: #28a745;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-update-cde:hover {
    background: #218838;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-update-cde:active {
    transform: translateY(0);
}

.numeric-cell {
    text-align: right;
    font-weight: 500;
}

.stock-alert {
    background-color: #ffebee !important;
    color: #c62828 !important;
    font-weight: bold;
}

/* Ligne en rouge si désynchronisation entre CDE EN COURS et CDE EN COURS à date */
.row-desync {
    background-color: #ffe5e5 !important;
}

.row-desync td {
    background-color: inherit !important;
}

/* Input pour CDE EN COURS à date - même style que stock-editable */
.cde-editable {
    background: #fff8e1;
    border: 1px solid #f39c12;
    padding: 8px 10px;
    border-radius: 4px;
    font-size: 14px;
    width: 80px;
    text-align: right;
    transition: all 0.2s ease;
}

.cde-editable:focus {
    outline: none;
    border-color: #e67e22;
    box-shadow: 0 0 0 3px rgba(243, 156, 18, 0.1);
    background-color: #fff;
}

.matiere-message {
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
    font-size: 14px;
}

.matiere-message.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.matiere-message.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.matiere-message.info {
    background-color: #cce7ff;
    color: #004085;
    border: 1px solid #b6d7ff;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .matiere-modal-content {
        width: 98%;
        margin: 1% auto;
        max-height: 95vh;
    }
    
    .matieres-table {
        font-size: 12px;
    }
    
    .matieres-table th,
    .matieres-table td {
        padding: 8px 6px;
    }
    
    .stock-editable {
        width: 60px;
    }
}
</style>

<?php

// === CONFIGURATION JAVASCRIPT ===
print '<script type="text/javascript">'."\n";
print 'window.DOLIBARR_PLANNING_CONFIG = {'."\n";
print '    start_week: '.($start_week).','."\n";
print '    year: '.($year).','."\n";
print '    current_token: "'.newToken().'",'."\n";
print '    debug_mode: true,'."\n";
print '    lang: {'."\n";
print '        ref: "'.$langs->trans('Ref').'",'."\n";
print '        produit: "'.$langs->trans('Produit').'",'."\n";
print '        matiere: "'.$langs->trans('Matiere').'",'."\n";
print '        mpok: "'.$langs->trans('MPOk').'",'."\n";
print '        mpattente: "'.$langs->trans('MPAttente').'",'."\n";
print '        mpmanquante: "'.$langs->trans('MPManquante').'",'."\n";
print '        blafaire: "'.$langs->trans('BLAFaire').'",'."\n";
print '        aproformavalider: "'.$langs->trans('ProformaAValider').'",'."\n";
print '        majairtableafaire: "'.$langs->trans('MAJAirtableAFaire').'"'."\n";
print '    }'."\n";
print '};'."\n";
print '</script>'."\n";

// === INITIALISATION DU PLANNING ===
print '<script type="text/javascript">'."\n";
print 'document.addEventListener("DOMContentLoaded", function() {'."\n";
print '    console.log("DOM chargé - Démarrage de l\'initialisation...");'."\n";
print '    const requiredFunctions = ["initializePlanning", "initializeTabs", "enableDragAndDrop", "initializeAllEvents", "initializeMutationObserver"];'."\n";
print '    let missingFunctions = [];'."\n";
print '    requiredFunctions.forEach(funcName => {'."\n";
print '        if (typeof window[funcName] !== "function") missingFunctions.push(funcName);'."\n";
print '    });'."\n";
print '    if (missingFunctions.length > 0) {'."\n";
print '        console.error("Fonctions manquantes:", missingFunctions);'."\n";
print '    } else {'."\n";
print '        console.log("Toutes les fonctions sont disponibles");'."\n";
print '        if (typeof initializePlanning === "function") initializePlanning();'."\n";
print '    }'."\n";
print '});'."\n";
print '</script>'."\n";

print '<script type="text/javascript">document.body.classList.remove("planning-page");</script>'."\n";

llxFooter();
?>
