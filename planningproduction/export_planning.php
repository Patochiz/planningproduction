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
 * \file    export_planning.php
 * \ingroup planningproduction
 * \brief   Export des plannings au format HTML imprimable
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
dol_include_once('/planningproduction/class/planningproduction.class.php');

// Translations
$langs->loadLangs(array("planningproduction@planningproduction", "other"));

// Security check
if (!$user->hasRight('planningproduction', 'planning', 'read')) {
    accessforbidden();
}

// Parameters
$type = GETPOST('type', 'alpha');
$year = GETPOST('year', 'int') ? GETPOST('year', 'int') : date('Y');
$week_count = GETPOST('week_count', 'int') ? GETPOST('week_count', 'int') : 5;
$start_week = GETPOST('start_week', 'int') ? GETPOST('start_week', 'int') : date('W');

// Initialize objects
$object = new PlanningProduction($db);

// Build mapping of code_mp -> lien for clickable materials
$matieres_liens = array();
$all_matieres = $object->getAllMatieres(false);
if ($all_matieres !== false) {
    foreach ($all_matieres as $m) {
        if (!empty($m['lien'])) {
            $matieres_liens[$m['code_mp']] = $m['lien'];
        }
    }
}

// Get data based on type
$data = array();
$title = '';
$subtitle = '';

switch ($type) {
    case 'unplanned':
        $data = $object->getCardsByStatus('unplanned');
        $title = $langs->trans('ExportNonPlanifiees');
        $subtitle = 'Éléments non planifiés';
        break;
        
    case 'to_finish':
        $data = $object->getCardsByStatus('a_terminer');
        $title = $langs->trans('ExportATerminer');
        $subtitle = 'Éléments à terminer';
        break;

    case 'to_paint':
        $data = $object->getCardsByStatus('a_peindre');
        $title = 'À Peindre';
        $subtitle = 'Éléments à peindre';
        break;

    case 'to_ship':
        $data = $object->getCardsByStatus('a_expedier');
        $title = $langs->trans('ExportAExpedier');
        $subtitle = 'Éléments à expédier';
        break;
        
    case 'planned':
        $planned_cards = $object->getPlannedCards($start_week, $week_count, $year);
        $data = $planned_cards; // Garder la structure par semaine
        $title = $langs->trans('ExportPlanifiees');
        $subtitle = "Éléments planifiés - Semaines $start_week à " . ($start_week + $week_count - 1) . " ($year)";
        break;
        
    case 'global':
        // Export global avec toutes les catégories
        $unplanned = $object->getCardsByStatus('unplanned');
        $to_finish = $object->getCardsByStatus('a_terminer');
        $to_paint = $object->getCardsByStatus('a_peindre');
        $to_ship = $object->getCardsByStatus('a_expedier');
        $planned_cards = $object->getPlannedCards(1, 52, $year);

        $title = $langs->trans('ExportGlobal');
        $subtitle = "Export global du planning de production - Année $year";
        break;
        
    default:
        $title = 'Export Planning';
        $subtitle = 'Type d\'export non spécifié';
}

// Handle errors
if ($data === false && $type !== 'global') {
    $data = array();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - <?php echo htmlspecialchars($conf->global->MAIN_INFO_SOCIETE_NOM); ?></title>
    
    <style>
    /* === CSS POUR L'IMPRESSION === */
    
    /* Reset et base */
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }
    
    body {
        font-family: 'Arial', sans-serif;
        font-size: 11pt;
        line-height: 1.4;
        color: #333;
        background: white;
        margin: 15px;
    }
    
    /* Header de la page */
    .export-header {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 2px solid #333;
    }
    
    .export-title {
        font-size: 18pt;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 5px;
    }
    
    .export-subtitle {
        font-size: 12pt;
        color: #7f8c8d;
        margin-bottom: 10px;
    }
    
    .export-info {
        font-size: 9pt;
        color: #95a5a6;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    /* Sections pour export global */
    .export-section {
        margin-bottom: 40px;
    }
    
    .section-title {
        font-size: 14pt;
        font-weight: bold;
        color: #34495e;
        margin-bottom: 15px;
        padding: 8px 12px;
        background: #ecf0f1;
        border-left: 4px solid #3498db;
    }
    
    .section-count {
        font-size: 10pt;
        color: #7f8c8d;
        float: right;
        font-weight: normal;
    }
    
    /* Titre de semaine pour les planifiées */
    .week-title {
        font-size: 14pt;
        font-weight: bold;
        color: #2c3e50;
        margin: 30px 0 15px 0;
        padding: 8px 12px;
        background: #34495e;
        color: white;
        text-align: center;
    }
    
    .week-title:first-child {
        margin-top: 0;
    }
    
    /* Ligne de groupe */
    .group-separator {
        background: #ecf0f1;
        border: none;
        text-align: left;
        font-weight: bold;
        color: #2c3e50;
        padding: 8px;
        font-size: 11pt;
    }

    .group-qty-badge {
        background: rgba(52, 73, 94, 0.15);
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 9pt;
        margin-right: 6px;
    }

    .badge-vn {
        display: inline-block;
        background: #e74c3c;
        color: #fff;
        font-size: 8pt;
        font-weight: bold;
        padding: 1px 4px;
        border-radius: 3px;
        vertical-align: middle;
    }

    /* Tableau des éléments */
    .export-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        font-size: 9pt;
    }
    
    .export-table th {
        background: #34495e;
        color: white;
        padding: 8px 6px;
        text-align: left;
        font-weight: bold;
        border: 1px solid #2c3e50;
        font-size: 9pt;
    }
    
    .export-table td {
        padding: 6px;
        border: 1px solid #bdc3c7;
        vertical-align: top;
        font-size: 9pt;
    }
    
    .export-table tr:nth-child(even) {
        background: #f8f9fa;
    }
    
    .export-table tr:hover {
        background: #e8f4fd;
    }
    
    /* Ligne jaune pour éléments à peindre */
    .export-table tr.paint-required {
        background: #ffff00 !important;
    }
    
    .export-table tr.paint-required:hover {
        background: #ffff66 !important;
    }

    /* Bordures gauches selon statuts MP/AR */
    .export-table tr.border-green td:first-child {
        border-left: 6px solid #27ae60;
    }

    .export-table tr.border-red td:first-child {
        border-left: 6px solid #e74c3c;
    }

    /* Colonnes spécifiques - NOUVEL ORDRE */
    .col-commande { width: 13%; }
    .col-ref { width: 12%; }
    .col-delai { width: 4%; }
    .col-produit { width: 23%; }
    .col-matiere { width: 14%; }
    .col-qte { width: 8%; text-align: right; }
    .col-livraison { width: 12%; }
    .col-statuts { width: 9%; }
    .col-actions { width: 5%; text-align: center; }

    /* Bouton popup par ligne */
    .btn-popup-row {
        background: #3498db;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 3px 8px;
        cursor: pointer;
        font-size: 12px;
        transition: background 0.2s;
        white-space: nowrap;
    }
    .btn-popup-row:hover { background: #2980b9; }

    .row-checkbox {
        cursor: pointer;
        width: 16px;
        height: 16px;
        vertical-align: middle;
        margin-right: 4px;
    }

    .selection-notification {
        position: fixed;
        top: 70px;
        right: 20px;
        background: #3498db;
        color: white;
        padding: 10px 18px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        z-index: 999;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .selection-notification strong {
        margin-right: 6px;
    }

    /* === MODAL D'ÉDITION (identique à planning.php) === */
    .edit-modal {
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 10002;
    }
    .edit-modal.show { display: flex; }
    .edit-modal-content {
        background: white;
        border-radius: 12px;
        padding: 24px;
        width: 90%;
        max-width: 500px;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        transform: scale(0.9);
        transition: transform 0.2s ease;
    }
    .edit-modal.show .edit-modal-content { transform: scale(1); }
    .edit-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #ecf0f1;
    }
    .edit-modal-title { font-size: 20px; font-weight: 600; color: #2c3e50; margin: 0; }
    .edit-modal-close {
        background: none; border: none;
        font-size: 24px; color: #95a5a6; cursor: pointer;
        padding: 0; width: 30px; height: 30px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 6px; transition: all 0.2s;
    }
    .edit-modal-close:hover { background: #ecf0f1; color: #2c3e50; }
    .edit-form-group { margin-bottom: 20px; }
    .edit-form-label { display: block; font-weight: 600; color: #2c3e50; margin-bottom: 8px; font-size: 14px; }
    .edit-form-input {
        width: 100%; padding: 12px;
        border: 2px solid #ecf0f1; border-radius: 8px;
        font-size: 14px; background: white; transition: border-color 0.2s;
    }
    .edit-form-input:focus { outline: none; border-color: #3498db; box-shadow: 0 0 0 3px rgba(52,152,219,0.1); }
    .edit-form-select {
        width: 100%; padding: 12px;
        border: 2px solid #ecf0f1; border-radius: 8px;
        font-size: 14px; background: white; cursor: pointer; transition: border-color 0.2s;
    }
    .edit-form-select:focus { outline: none; border-color: #3498db; box-shadow: 0 0 0 3px rgba(52,152,219,0.1); }
    .edit-current-values {
        background: #f8f9fa; padding: 15px;
        border-radius: 8px; margin-bottom: 20px;
        border-left: 4px solid #3498db;
    }
    .edit-current-title { font-weight: 600; color: #2c3e50; margin-bottom: 8px; font-size: 14px; }
    .edit-current-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; font-size: 13px; }
    .edit-current-label { color: #7f8c8d; font-weight: 500; }
    .edit-current-value { color: #2c3e50; font-weight: 500; }
    .edit-modal-actions {
        display: flex; gap: 12px; justify-content: flex-end;
        margin-top: 24px; padding-top: 20px; border-top: 1px solid #ecf0f1;
    }
    .edit-btn { padding: 12px 24px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; min-width: 100px; }
    .edit-btn-cancel { background: #ecf0f1; color: #7f8c8d; }
    .edit-btn-cancel:hover { background: #bdc3c7; color: #2c3e50; }
    .edit-btn-save { background: #27ae60; color: white; }
    .edit-btn-save:hover { background: #229954; transform: translateY(-1px); box-shadow: 0 4px 8px rgba(39,174,96,0.3); }
    .edit-badge-preview { display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; margin-left: 8px; }
    .edit-badge-preview.green { background: #d5f4e6; color: #27ae60; }
    .edit-badge-preview.orange { background: #fff3cd; color: #f39c12; }
    .edit-badge-preview.red { background: #f8d7da; color: #e74c3c; }
    .edit-badge-preview.blue { background: #e3f2fd; color: #3498db; }

    .no-print { }

    /* Badges de statut */
    .status-badge {
        display: inline-block;
        padding: 2px 4px;
        border-radius: 3px;
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        margin: 1px;
        white-space: nowrap;
    }
    
    .badge-mp-ok { background: #d5f4e6; color: #27ae60; }
    .badge-mp-waiting { background: #f8d7da; color: #e74c3c; }
    .badge-ar-ok { background: #d5f4e6; color: #27ae60; }
    .badge-ar-waiting { background: #f8d7da; color: #e74c3c; }
    .badge-peindre { background: #fff3cd; color: #f39c12; }
    .badge-production { background: #e8f4fd; color: #3498db; }
    
    /* Cellule de statuts */
    .status-cell {
        font-size: 7pt;
        line-height: 1.2;
    }
    
    /* Message si vide */
    .empty-message {
        text-align: center;
        padding: 30px;
        color: #95a5a6;
        font-style: italic;
        border: 2px dashed #bdc3c7;
        border-radius: 8px;
        background: #f8f9fa;
    }
    
    /* Statistiques */
    .export-stats {
        margin-top: 20px;
        padding: 15px;
        background: #ecf0f1;
        border-radius: 6px;
        text-align: center;
        font-size: 10pt;
        color: #2c3e50;
    }
    
    /* Spécifique à l'impression */
    @media print {
        body {
            font-size: 9pt;
            line-height: 1.3;
        }
        
        .export-header {
            margin-bottom: 20px;
        }
        
        .export-title {
            font-size: 16pt;
        }
        
        .export-subtitle {
            font-size: 11pt;
        }
        
        .export-table {
            font-size: 8pt;
        }
        
        .export-table th,
        .export-table td {
            padding: 4px;
        }
        
        .section-title {
            font-size: 12pt;
        }
        
        .week-title {
            font-size: 12pt;
        }
        
        .status-badge {
            font-size: 6pt;
            padding: 1px 3px;
        }
        
        /* Éviter les coupures au niveau des lignes */
        .export-table tr {
            page-break-inside: avoid;
        }

        /* Forcer les sauts de page pour export global */
        .page-break {
            page-break-before: always;
        }

        .week-title {
            page-break-after: avoid;
        }

        .col-commande { width: 15%; }
        .col-ref { width: 13%; }
        .col-delai { width: 4%; }
        .col-produit { width: 25%; }
        .col-matiere { width: 15%; }
        .col-qte { width: 8%; text-align: right; }
        .col-livraison { width: 12%; }
        .col-statuts { width: 8%; }
        .col-actions { display: none !important; }
        .no-print { display: none !important; }
        .export-actions { display: none !important; }
        .edit-modal { display: none !important; }
        .matiere-modal { display: none !important; }

        /* Matières table print styles */
        .matieres-table { font-size: 8pt; width: 100%; border-collapse: collapse; }
        .matieres-table th, .matieres-table td { padding: 4px 6px; border: 1px solid #ccc; }
        .matieres-table th { background: #ecf0f1 !important; color: #333 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .matieres-table th[style*="f39c12"] { background: #f39c12 !important; color: white !important; }
        .matieres-table .stock-editable,
        .matieres-table .cde-editable { border: none; background: transparent; font-size: 8pt; padding: 0; width: auto; }
        .matieres-table .btn-update-cde { display: none; }
        .row-stock-alert { background-color: #ffebee !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .row-desync { background-color: #fff3e0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .export-table tr.paint-required { background: #ffff00 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .export-table tr.paint-required td { background: inherit !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        #matieresInlineSection .loading-spinner { display: none; }
        #matieresInlineSection { display: none !important; }
    }
    
    /* Boutons d'action (cachés à l'impression) */
    .export-actions {
        position: fixed;
        top: 20px;
        right: 20px;
        display: flex;
        gap: 10px;
        z-index: 1000;
    }
    
    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 11pt;
        font-weight: bold;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    
    .btn-print {
        background: #3498db;
        color: white;
    }
    
    .btn-print:hover {
        background: #2980b9;
    }
    
    .btn-back {
        background: #95a5a6;
        color: white;
    }
    
    .btn-back:hover {
        background: #7f8c8d;
    }
    
    .btn-matieres { background: #9b59b6; color: white; }
    .btn-matieres:hover { background: #8e44ad; }

    .btn-secondary { background: #6c757d; color: white; }
    .btn-secondary:hover { background: #5a6268; }

    .btn-primary { background: #3498db; color: white; }
    .btn-primary:hover { background: #2980b9; }

    /* Modal matières premières */
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
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

    .matiere-modal-title { margin: 0; font-size: 20px; font-weight: 600; }

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

    .matiere-modal-body { padding: 25px; max-height: 60vh; overflow-y: auto; }

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
        font-size: 11px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .matieres-table th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 600;
        padding: 6px 8px;
        text-align: left;
        border: none;
    }

    .matieres-table td {
        padding: 4px 8px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }

    .matieres-table tbody tr:hover { background-color: #f8f9fa; }
    .matieres-table tbody tr:last-child td { border-bottom: none; }

    .stock-editable {
        background: none;
        border: 1px solid #ddd;
        padding: 3px 6px;
        border-radius: 4px;
        font-size: 11px;
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

    .cde-editable {
        background: #fff8e1;
        border: 1px solid #f39c12;
        padding: 3px 6px;
        border-radius: 4px;
        font-size: 11px;
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

    .btn-update-cde {
        background: #28a745;
        color: white;
        border: none;
        padding: 3px 8px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 10px;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .btn-update-cde:hover { background: #218838; transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .btn-update-cde:active { transform: translateY(0); }

    .numeric-cell { text-align: right; font-weight: 500; }
    .row-stock-alert { background-color: #ffebee !important; }
    .row-stock-alert td { background-color: inherit !important; }
    .reste-alert { color: #c62828 !important; font-weight: bold !important; }
    .row-desync { background-color: #fff3e0 !important; }
    .row-desync td { background-color: inherit !important; }

    .matiere-message { padding: 8px; border-radius: 6px; margin-bottom: 10px; font-size: 11px; }
    .matiere-message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .matiere-message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .matiere-message.info { background-color: #cce7ff; color: #004085; border: 1px solid #b6d7ff; }

    </style>
</head>
<body>
    <!-- Actions (non imprimées) -->
    <div class="export-actions">
        <button class="btn btn-print" onclick="window.print()">🖨️ Imprimer</button>
        <a href="planning.php" class="btn btn-back">✏️ Modifier</a>
    </div>
    
    <!-- Notification de sélection (cachée par défaut) -->
    <div class="selection-notification no-print" id="selectionNotification" style="display:none;">
        <strong>Sélection :</strong>
        <span id="selectionSummary"></span>
    </div>

    <!-- Header -->
    <div class="export-header">
        <div class="export-title"><?php echo htmlspecialchars($title); ?></div>
        <div class="export-subtitle"><?php echo htmlspecialchars($subtitle); ?></div>
        <div class="export-info">
            <span><?php echo htmlspecialchars($conf->global->MAIN_INFO_SOCIETE_NOM ?: 'Planning Production'); ?></span>
            <span>Généré le <?php echo dol_print_date(dol_now(), '%d/%m/%Y à %H:%M'); ?></span>
        </div>
    </div>
    
    <!-- Section Matières Premières (inline) -->
    <div class="export-section" id="matieresInlineSection">
        <h2 class="section-title">
            🧱 Matières Premières
        </h2>
        <div id="matieresInlineContainer">
            <div class="loading-spinner" style="text-align: center; padding: 30px;">
                <div style="display: inline-block; width: 40px; height: 40px; border: 3px solid #f3f3f3; border-top: 3px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <p>Chargement des matières...</p>
            </div>
        </div>
    </div>

    <?php if ($type === 'global'): ?>
        <!-- EXPORT GLOBAL - NOUVEAU ORDRE AVEC PLANIFIÉES EN PREMIER -->
        
        <!-- Planifiées - MAINTENANT EN PREMIÈRE POSITION -->
        <div class="export-section">
            <h2 class="section-title">
                📅 Planifiées
                <span class="section-count"><?php echo getTotalPlannedCards($planned_cards); ?> éléments</span>
            </h2>
            <?php renderPlannedCardsByWeek($planned_cards, $langs); ?>
        </div>
        
        <!-- Non planifiées -->
        <?php if (!empty($unplanned)): ?>
        <div class="export-section page-break">
            <h2 class="section-title">
                📋 Non Planifiées
                <span class="section-count"><?php echo count($unplanned); ?> éléments</span>
            </h2>
            <?php renderCardsTable($unplanned, $langs); ?>
        </div>
        <?php endif; ?>

        <!-- À peindre -->
        <?php if (!empty($to_paint)): ?>
        <div class="export-section page-break">
            <h2 class="section-title">
                🎨 À Peindre
                <span class="section-count"><?php echo count($to_paint); ?> éléments</span>
            </h2>
            <?php renderCardsTable($to_paint, $langs); ?>
        </div>
        <?php endif; ?>

        <!-- À terminer -->
        <?php if (!empty($to_finish)): ?>
        <div class="export-section page-break">
            <h2 class="section-title">
                ⚠️ À Terminer
                <span class="section-count"><?php echo count($to_finish); ?> éléments</span>
            </h2>
            <?php renderCardsTable($to_finish, $langs); ?>
        </div>
        <?php endif; ?>

        <!-- À expédier -->
        <?php if (!empty($to_ship)): ?>
        <div class="export-section page-break">
            <h2 class="section-title">
                ✅ À Expédier
                <span class="section-count"><?php echo count($to_ship); ?> éléments</span>
            </h2>
            <?php renderCardsTable($to_ship, $langs); ?>
        </div>
        <?php endif; ?>

        <!-- Statistiques globales -->
        <div class="export-stats">
            <strong>Résumé :</strong>
            <?php echo getTotalPlannedCards($planned_cards); ?> planifiées •
            <?php echo count($unplanned); ?> non planifiées •
            <?php echo count($to_finish); ?> à terminer •
            <?php echo count($to_paint); ?> à peindre •
            <?php echo count($to_ship); ?> à expédier •
            <strong>Total : <?php echo (count($unplanned) + count($to_finish) + count($to_paint) + count($to_ship) + getTotalPlannedCards($planned_cards)); ?> éléments</strong>
        </div>
        
    <?php elseif ($type === 'planned'): ?>
        <!-- EXPORT PLANIFIÉES PAR SEMAINE -->
        <?php renderPlannedCardsByWeek($data, $langs); ?>
        
        <div class="export-stats">
            <strong><?php echo getTotalPlannedCards($data); ?> éléments planifiés au total</strong>
        </div>
        
    <?php else: ?>
        <!-- EXPORT SIMPLE -->
        <?php renderCardsTable($data, $langs); ?>
        
        <div class="export-stats">
            <strong><?php echo count($data); ?> éléments au total</strong>
        </div>
    <?php endif; ?>

    <!-- MODAL D'ÉDITION (identique à planning.php) -->
    <div class="edit-modal" id="editModal">
        <div class="edit-modal-content">
            <div class="edit-modal-header">
                <h3 class="edit-modal-title">✏️ Éditer la carte</h3>
                <button class="edit-modal-close" onclick="closeEditModal()">×</button>
            </div>

            <div class="edit-current-values">
                <div class="edit-current-title">Carte actuelle</div>
                <div class="edit-current-item">
                    <span class="edit-current-label">Client :</span>
                    <span class="edit-current-value" id="editCurrentTitle">-</span>
                </div>
                <div class="edit-current-item">
                    <span class="edit-current-label">Référence :</span>
                    <span class="edit-current-value" id="editCurrentClient">-</span>
                </div>
                <div class="edit-current-item">
                    <span class="edit-current-label">Réf. chantier :</span>
                    <span class="edit-current-value" id="editCurrentRefChantier">-</span>
                </div>
                <div class="edit-current-item">
                    <span class="edit-current-label">Produit :</span>
                    <span class="edit-current-value" id="editCurrentOrder">-</span>
                </div>
            </div>

            <form id="editForm">
                <div class="edit-form-group">
                    <label class="edit-form-label" for="editMatiere">Matière</label>
                    <input type="text" id="editMatiere" class="edit-form-input" placeholder="Saisir la matière...">
                </div>

                <div class="edit-form-group">
                    <label class="edit-form-label" for="editMpStatus">Statut matière première</label>
                    <select id="editMpStatus" class="edit-form-select" onchange="updateBadgePreview('mp', this.value)">
                        <option value="">-- Sélectionner --</option>
                        <option value="MP Ok,MP Ok">MP Ok</option>
                        <option value="MP en attente,MP en attente">MP en attente</option>
                        <option value="MP Manquante,MP Manquante">MP Manquante</option>
                        <option value="BL A FAIRE,BL A FAIRE">BL A FAIRE</option>
                        <option value="PROFORMA A VALIDER,PROFORMA A VALIDER">PROFORMA A VALIDER</option>
                        <option value="MàJ AIRTABLE à Faire,MàJ AIRTABLE à Faire">MàJ AIRTABLE à Faire</option>
                    </select>
                    <span class="edit-badge-preview" id="mpStatusPreview"></span>
                </div>

                <div class="edit-form-group">
                    <label class="edit-form-label" for="editProductionStatus">Statut production</label>
                    <select id="editProductionStatus" class="edit-form-select">
                        <option value="À PRODUIRE">À PRODUIRE</option>
                        <option value="EN COURS">EN COURS</option>
                        <option value="À PEINDRE">À PEINDRE</option>
                        <option value="À TERMINER">À TERMINER</option>
                        <option value="BON POUR EXPÉDITION">BON POUR EXPÉDITION</option>
                    </select>
                </div>

                <div class="edit-form-group">
                    <label class="edit-form-label" for="editPeindre">À peindre</label>
                    <select id="editPeindre" class="edit-form-select">
                        <option value="non">Non</option>
                        <option value="oui">Oui</option>
                    </select>
                    <small style="color:#7f8c8d;font-size:12px;margin-top:5px;display:block;">Si fond jaune fluo</small>
                </div>
            </form>

            <div class="edit-modal-actions">
                <button type="button" class="edit-btn edit-btn-cancel" onclick="closeEditModal()">Annuler</button>
                <button type="button" class="edit-btn edit-btn-save" onclick="saveCardEdit()">Sauvegarder</button>
            </div>
        </div>
    </div>

    <!-- MODAL MATIÈRES PREMIÈRES -->
    <div class="matiere-modal" id="matieresModal">
        <div class="matiere-modal-content">
            <div class="matiere-modal-header">
                <h3 class="matiere-modal-title">🧱 Matières Premières</h3>
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
                <button type="button" class="btn btn-secondary" onclick="closeMatieresModal()">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="refreshMatieresData()">🔄 Actualiser les données</button>
            </div>
        </div>
    </div>

    <?php
    // Configuration JS pour le module matières
    print '<script type="text/javascript">'."\n";
    print 'window.DOLIBARR_PLANNING_CONFIG = {'."\n";
    print '    current_token: "'.newToken().'"'."\n";
    print '};'."\n";
    print '</script>'."\n";
    ?>
    <script type="text/javascript" src="<?php echo dol_buildpath('/planningproduction/js/matieres.js', 1); ?>?v=<?php echo time(); ?>"></script>

    <script type="text/javascript">
    // === IDENTIQUE À modal.js / planning.php ===

    var currentEditCard = null;

    // Ouvre le modal depuis un bouton de ligne (data-attributes)
    function openCardModal(btn) {
        // On construit un objet "carte virtuelle" que saveCardEdit() peut utiliser
        currentEditCard = { dataset: { fkCommandedet: btn.dataset.id } };

        // Remplir les infos affichées
        document.getElementById('editCurrentTitle').textContent       = btn.dataset.client   || '-';
        document.getElementById('editCurrentRefChantier').textContent = btn.dataset.ref      || '-';
        document.getElementById('editCurrentOrder').textContent       = btn.dataset.produit  || '-';

        // Référence commande : lien cliquable si URL disponible
        var clientEl = document.getElementById('editCurrentClient');
        var commandeText = btn.dataset.commande || '-';
        var commandeUrl  = btn.dataset.commandeUrl || '';
        if (commandeUrl) {
            clientEl.innerHTML = '<a href="' + commandeUrl + '" target="_blank" style="color:#3498db;text-decoration:none;font-weight:600;">' + commandeText + ' 🔗</a>';
        } else {
            clientEl.textContent = commandeText;
        }

        // Remplir les champs du formulaire
        document.getElementById('editMatiere').value          = btn.dataset.matiere      || '';
        document.getElementById('editMpStatus').value         = btn.dataset.statutMp     || '';
        document.getElementById('editProductionStatus').value = btn.dataset.statutProd   || 'À PRODUIRE';
        document.getElementById('editPeindre').value          = btn.dataset.postlaquage  || 'non';

        updateBadgePreview('mp', document.getElementById('editMpStatus').value);
        document.getElementById('editModal').classList.add('show');
    }

    function closeEditModal() {
        var modal = document.getElementById('editModal');
        if (!modal) return;
        modal.classList.remove('show');
        currentEditCard = null;
        var form = document.getElementById('editForm');
        if (form) form.reset();
        document.querySelectorAll('.edit-badge-preview').forEach(function(b) {
            b.textContent = '';
            b.className = 'edit-badge-preview';
        });
    }

    function updateBadgePreview(type, value) {
        var previewElement = document.getElementById(type + 'StatusPreview');
        if (!previewElement) return;
        previewElement.className = 'edit-badge-preview';
        if (type === 'mp') {
            switch (value) {
                case 'MP Ok,MP Ok':
                    previewElement.textContent = 'MP OK';
                    previewElement.classList.add('green'); break;
                case 'MP en attente,MP en attente':
                    previewElement.textContent = 'MP EN ATTENTE';
                    previewElement.classList.add('red'); break;
                case 'MP Manquante,MP Manquante':
                    previewElement.textContent = 'MP MANQUANTE';
                    previewElement.classList.add('red'); break;
                case 'BL A FAIRE,BL A FAIRE':
                    previewElement.textContent = 'BL A FAIRE';
                    previewElement.classList.add('red'); break;
                case 'PROFORMA A VALIDER,PROFORMA A VALIDER':
                    previewElement.textContent = 'PROFORMA A VALIDER';
                    previewElement.classList.add('red'); break;
                case 'MàJ AIRTABLE à Faire,MàJ AIRTABLE à Faire':
                    previewElement.textContent = 'MAJ AIRTABLE A FAIRE';
                    previewElement.classList.add('red'); break;
                default:
                    previewElement.textContent = '';
            }
        }
    }

    function saveCardEdit() {
        if (!currentEditCard) return;
        var fkCommandedet    = currentEditCard.dataset.fkCommandedet;
        var matiereValue     = document.getElementById('editMatiere').value.trim();
        var mpStatus         = document.getElementById('editMpStatus').value;
        var prodStatus       = document.getElementById('editProductionStatus').value;
        var peindreStatus    = document.getElementById('editPeindre').value;

        if (!fkCommandedet) {
            showToast('Erreur : données de la carte manquantes', 'error');
            return;
        }

        var formData = new FormData();
        formData.append('action', 'update_card');
        formData.append('fk_commandedet', fkCommandedet);
        formData.append('matiere',        matiereValue);
        formData.append('statut_mp',      mpStatus);
        formData.append('statut_prod',    prodStatus);
        formData.append('postlaquage',    peindreStatus);
        if (window.DOLIBARR_PLANNING_CONFIG && window.DOLIBARR_PLANNING_CONFIG.current_token) {
            formData.append('token', window.DOLIBARR_PLANNING_CONFIG.current_token);
        }

        fetch('ajax_planning.php', { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    showToast('Carte mise à jour', 'success');
                    closeEditModal();
                    setTimeout(function() { window.location.reload(); }, 1000);
                } else {
                    showToast(data.error || 'Erreur de sauvegarde', 'error');
                }
            })
            .catch(function(err) {
                console.error('Error:', err);
                showToast('Erreur de sauvegarde', 'error');
            });
    }

    function showToast(message, type) {
        var existing = document.querySelector('.export-toast');
        if (existing) existing.remove();
        var toast = document.createElement('div');
        toast.className = 'export-toast';
        toast.textContent = message;
        toast.style.cssText = 'position:fixed;top:80px;right:20px;padding:12px 20px;border-radius:8px;color:white;font-weight:600;z-index:20000;font-size:14px;box-shadow:0 4px 12px rgba(0,0,0,0.2);';
        toast.style.background = (type === 'error') ? '#e74c3c' : '#27ae60';
        document.body.appendChild(toast);
        setTimeout(function() { if (toast.parentNode) toast.remove(); }, 3000);
    }

    // Fermer en cliquant en dehors
    document.addEventListener('click', function(e) {
        var modal = document.getElementById('editModal');
        if (modal && e.target === modal) closeEditModal();
    });

    // Fermer avec Escape
    document.addEventListener('keydown', function(e) {
        var modal = document.getElementById('editModal');
        if (e.key === 'Escape' && modal && modal.classList.contains('show')) closeEditModal();
    });

    // === SOMME DES QUANTITÉS DES LIGNES COCHÉES ===
    function updateSelectionNotification() {
        var checked = document.querySelectorAll('.row-checkbox:checked');
        var notif = document.getElementById('selectionNotification');
        var summary = document.getElementById('selectionSummary');

        if (checked.length === 0) {
            notif.style.display = 'none';
            return;
        }

        var totals = {};
        checked.forEach(function(cb) {
            var qty = parseFloat(cb.dataset.qty) || 0;
            var unite = cb.dataset.unite || 'u';
            if (!totals[unite]) totals[unite] = 0;
            totals[unite] += qty;
        });

        var parts = [];
        for (var unite in totals) {
            var val = totals[unite];
            var display = (val === Math.floor(val)) ? val.toString() : val.toFixed(2).replace('.', ',');
            parts.push(display + ' ' + unite);
        }

        summary.textContent = checked.length + ' ligne' + (checked.length > 1 ? 's' : '') + ' — ' + parts.join(' | ');
        notif.style.display = 'block';
    }
    </script>

</body>
</html>

<?php

/**
 * Render table of cards with new column order
 */
function renderCardsTable($cards, $langs)
{
    global $matieres_liens;

    if (empty($cards)) {
        echo '<div class="empty-message">Aucun élément à afficher dans cette catégorie.</div>';
        return;
    }
    
    echo '<table class="export-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th class="col-commande">Commande</th>';
    echo '<th class="col-ref">Ref.</th>';
    echo '<th class="col-delai">Délai</th>';
    echo '<th class="col-produit">Produit</th>';
    echo '<th class="col-matiere">Matière</th>';
    echo '<th class="col-qte">Quantité</th>';
    echo '<th class="col-livraison">Livraison</th>';
    echo '<th class="col-statuts">Statuts</th>';
    echo '<th class="col-actions no-print"></th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($cards as $card) {
        // Ligne jaune si à peindre
        $paint_class = (!empty($card['postlaquage']) && $card['postlaquage'] == 'oui') ? ' paint-required' : '';

        // Bordure gauche selon statuts MP et AR
        $mp_ok = (isset($card['statut_mp']) && strpos($card['statut_mp'], 'MP Ok') !== false);
        $ar_ok = (isset($card['statut_ar']) && $card['statut_ar'] == 'AR VALIDÉ');
        $border_class = ($mp_ok && $ar_ok) ? ' border-green' : ' border-red';

        echo '<tr class="' . trim($paint_class . $border_class) . '">';

        // Commande (client + numéro/version)
        $commande_cell = htmlspecialchars($card['client'] ?? '-');
        $commande_cell .= '<br><small>' . htmlspecialchars($card['commande_ref'] ?? '-');
        if (!empty($card['version'])) {
            $commande_cell .= ' ' . htmlspecialchars($card['version']);
        }
        $commande_cell .= '</small>';
        echo '<td>' . $commande_cell . '</td>';

        // Référence client
        echo '<td>' . htmlspecialchars($card['ref_chantier'] ?? '-') . '</td>';

        // Délai
        echo '<td>' . htmlspecialchars($card['deadline'] ?? '-') . '</td>';

        // Produit (référence + description)
        $produit = '';
        $vn_badge = !empty($card['has_vn']) ? ' <span class="badge-vn">+VN</span>' : '';
        if (!empty($card['produit_ref'])) {
            $produit = '<strong>' . htmlspecialchars($card['produit_ref']) . '</strong>' . $vn_badge;
            if (!empty($card['produit'])) {
                $produit .= '<br><small>' . htmlspecialchars($card['produit']) . '</small>';
            }
        } else if (!empty($card['produit'])) {
            $produit = htmlspecialchars($card['produit']) . $vn_badge;
        } else {
            $produit = '-';
        }
        echo '<td>' . $produit . '</td>';

        // Matière (cliquable si un lien est configuré pour ce code MP)
        $matiere_val = $card['matiere'] ?? '-';
        $matiere_lien = '';
        if (!empty($matiere_val) && $matiere_val !== '-' && !empty($matieres_liens)) {
            foreach ($matieres_liens as $code_mp => $lien) {
                if (stripos($matiere_val, $code_mp) !== false) {
                    $matiere_lien = $lien;
                    break;
                }
            }
        }
        if (!empty($matiere_lien)) {
            echo '<td><a href="' . htmlspecialchars($matiere_lien) . '" target="_blank" style="color: inherit; text-decoration: underline;">' . htmlspecialchars($matiere_val) . '</a></td>';
        } else {
            echo '<td>' . htmlspecialchars($matiere_val) . '</td>';
        }

        // Quantité
        echo '<td style="text-align:right">' . htmlspecialchars(number_format(floatval($card['quantity'] ?? 0), 2, ',', '') . ' ' . ($card['unite'] ?? 'u')) . '</td>';

        // Livraison
        echo '<td>' . htmlspecialchars($card['delivery'] ?? '-') . '</td>';

        // Statuts
        echo '<td class="status-cell">';

        // Statut MP
        if (!empty($card['statut_mp'])) {
            $mp_parts = explode(',', $card['statut_mp']);
            $mp_text = trim($mp_parts[0]);
            if (strpos($mp_text, 'MP Ok') !== false) {
                echo '<span class="status-badge badge-mp-ok">MP OK</span>';
            } else {
                echo '<span class="status-badge badge-mp-waiting">' . htmlspecialchars($mp_text) . '</span>';
            }
        }

        // Statut AR
        if (!empty($card['statut_ar'])) {
            if ($card['statut_ar'] == 'AR VALIDÉ') {
                echo '<span class="status-badge badge-ar-ok">AR OK</span>';
            } else {
                echo '<span class="status-badge badge-ar-waiting">' . htmlspecialchars($card['statut_ar']) . '</span>';
            }
        }

        // Statut production
        if (!empty($card['statut_prod'])) {
            echo '<span class="status-badge badge-production">' . htmlspecialchars($card['statut_prod']) . '</span>';
        }

        echo '</td>';

        // Bouton popup
        $btn_data  = ' data-id="' . (int)($card['fk_commandedet'] ?? 0) . '"';
        $btn_data .= ' data-client="' . htmlspecialchars($card['client'] ?? '', ENT_QUOTES) . '"';
        $btn_data .= ' data-commande="' . htmlspecialchars(($card['commande_ref'] ?? '') . (!empty($card['version']) ? ' ' . $card['version'] : ''), ENT_QUOTES) . '"';
        $btn_data .= ' data-commande-url="' . htmlspecialchars(DOL_URL_ROOT . '/commande/card.php?id=' . (int)($card['fk_commande'] ?? 0), ENT_QUOTES) . '"';
        $btn_data .= ' data-ref="' . htmlspecialchars($card['ref_chantier'] ?? '', ENT_QUOTES) . '"';
        $btn_data .= ' data-produit="' . htmlspecialchars((!empty($card['produit_ref']) ? $card['produit_ref'] : ($card['produit'] ?? '')), ENT_QUOTES) . '"';
        $btn_data .= ' data-matiere="' . htmlspecialchars($card['matiere'] ?? '', ENT_QUOTES) . '"';
        $btn_data .= ' data-statut-mp="' . htmlspecialchars($card['statut_mp'] ?? '', ENT_QUOTES) . '"';
        $btn_data .= ' data-statut-prod="' . htmlspecialchars($card['statut_prod'] ?? '', ENT_QUOTES) . '"';
        $btn_data .= ' data-postlaquage="' . htmlspecialchars($card['postlaquage'] ?? 'non', ENT_QUOTES) . '"';
        echo '<td class="no-print"><input type="checkbox" class="row-checkbox" data-qty="' . floatval($card['quantity'] ?? 0) . '" data-unite="' . htmlspecialchars($card['unite'] ?? 'u', ENT_QUOTES) . '" onchange="updateSelectionNotification()"><button class="btn-popup-row"' . $btn_data . ' onclick="openCardModal(this)">✏️</button></td>';

        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}

/**
 * Render planned cards organized by week with groups
 */
function renderPlannedCardsByWeek($planned_cards, $langs)
{
    global $matieres_liens;

    if (empty($planned_cards)) {
        echo '<div class="empty-message">Aucun élément planifié à afficher.</div>';
        return;
    }
    
    foreach ($planned_cards as $week => $week_data) {
        if (empty($week_data['cards'])) {
            continue;
        }
        
        // Titre de semaine
        echo '<div class="week-title">SEMAINE ' . sprintf('%02d', $week) . ' - ' . count($week_data['cards']) . ' éléments</div>';
        
        // Grouper les cartes par groupe
        $groups = array();
        foreach ($week_data['cards'] as $card) {
            $group_name = $card['groupe'] ?? 'Sans groupe';
            if (!isset($groups[$group_name])) {
                $groups[$group_name] = array();
            }
            $groups[$group_name][] = $card;
        }
        
        // Créer un tableau unique pour cette semaine
        echo '<table class="export-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th class="col-commande">Commande</th>';
        echo '<th class="col-ref">Ref.</th>';
        echo '<th class="col-delai">Délai</th>';
        echo '<th class="col-produit">Produit</th>';
        echo '<th class="col-matiere">Matière</th>';
        echo '<th class="col-qte">Quantité</th>';
        echo '<th class="col-livraison">Livraison</th>';
        echo '<th class="col-statuts">Statuts</th>';
        echo '<th class="col-actions no-print"></th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        $first_group = true;
        foreach ($groups as $group_name => $cards) {
            // Calculer la quantité totale du groupe
            $group_total_qty = 0;
            $group_unite = 'u';
            foreach ($cards as $c) {
                $group_total_qty += floatval($c['quantity'] ?? 0);
                if ($group_unite === 'u' && !empty($c['unite'])) {
                    $group_unite = $c['unite'];
                }
            }
            $qty_display = ($group_total_qty == intval($group_total_qty)) ? intval($group_total_qty) : $group_total_qty;

            echo '<tr><td colspan="9" class="group-separator"><span class="group-qty-badge">' . $qty_display . ' ' . htmlspecialchars($group_unite) . '</span>📁 ' . htmlspecialchars($group_name) . '</td></tr>';
            $first_group = false;

            // Cartes du groupe
            foreach ($cards as $card) {
                // Ligne jaune si à peindre
                $paint_class = (!empty($card['postlaquage']) && $card['postlaquage'] == 'oui') ? ' paint-required' : '';

                // Bordure gauche selon statuts MP et AR
                $mp_ok = (isset($card['statut_mp']) && strpos($card['statut_mp'], 'MP Ok') !== false);
                $ar_ok = (isset($card['statut_ar']) && $card['statut_ar'] == 'AR VALIDÉ');
                $border_class = ($mp_ok && $ar_ok) ? ' border-green' : ' border-red';

                echo '<tr class="' . trim($paint_class . $border_class) . '">';

                // Commande (client + numéro/version)
                $commande_cell = htmlspecialchars($card['client'] ?? '-');
                $commande_cell .= '<br><small>' . htmlspecialchars($card['commande_ref'] ?? '-');
                if (!empty($card['version'])) {
                    $commande_cell .= ' ' . htmlspecialchars($card['version']);
                }
                $commande_cell .= '</small>';
                echo '<td>' . $commande_cell . '</td>';

                // Référence client
                echo '<td>' . htmlspecialchars($card['ref_chantier'] ?? '-') . '</td>';

                // Délai
                echo '<td>' . htmlspecialchars($card['deadline'] ?? '-') . '</td>';

                // Produit (référence + description)
                $produit = '';
                $vn_badge = !empty($card['has_vn']) ? ' <span class="badge-vn">+VN</span>' : '';
                if (!empty($card['produit_ref'])) {
                    $produit = '<strong>' . htmlspecialchars($card['produit_ref']) . '</strong>' . $vn_badge;
                    if (!empty($card['produit'])) {
                        $produit .= '<br><small>' . htmlspecialchars($card['produit']) . '</small>';
                    }
                } else if (!empty($card['produit'])) {
                    $produit = htmlspecialchars($card['produit']) . $vn_badge;
                } else {
                    $produit = '-';
                }
                echo '<td>' . $produit . '</td>';

                // Matière (cliquable si un lien est configuré pour ce code MP)
                $matiere_val = $card['matiere'] ?? '-';
                $matiere_lien = '';
                if (!empty($matiere_val) && $matiere_val !== '-' && !empty($matieres_liens)) {
                    foreach ($matieres_liens as $code_mp => $lien) {
                        if (stripos($matiere_val, $code_mp) !== false) {
                            $matiere_lien = $lien;
                            break;
                        }
                    }
                }
                if (!empty($matiere_lien)) {
                    echo '<td><a href="' . htmlspecialchars($matiere_lien) . '" target="_blank" style="color: inherit; text-decoration: underline;">' . htmlspecialchars($matiere_val) . '</a></td>';
                } else {
                    echo '<td>' . htmlspecialchars($matiere_val) . '</td>';
                }

                // Quantité
                echo '<td style="text-align:right">' . htmlspecialchars(number_format(floatval($card['quantity'] ?? 0), 2, ',', '') . ' ' . ($card['unite'] ?? 'u')) . '</td>';

                // Livraison
                echo '<td>' . htmlspecialchars($card['delivery'] ?? '-') . '</td>';

                // Statuts
                echo '<td class="status-cell">';

                // Statut MP
                if (!empty($card['statut_mp'])) {
                    $mp_parts = explode(',', $card['statut_mp']);
                    $mp_text = trim($mp_parts[0]);
                    if (strpos($mp_text, 'MP Ok') !== false) {
                        echo '<span class="status-badge badge-mp-ok">MP OK</span>';
                    } else {
                        echo '<span class="status-badge badge-mp-waiting">' . htmlspecialchars($mp_text) . '</span>';
                    }
                }

                // Statut AR
                if (!empty($card['statut_ar'])) {
                    if ($card['statut_ar'] == 'AR VALIDÉ') {
                        echo '<span class="status-badge badge-ar-ok">AR OK</span>';
                    } else {
                        echo '<span class="status-badge badge-ar-waiting">' . htmlspecialchars($card['statut_ar']) . '</span>';
                    }
                }

                // Statut production
                if (!empty($card['statut_prod'])) {
                    echo '<span class="status-badge badge-production">' . htmlspecialchars($card['statut_prod']) . '</span>';
                }

                echo '</td>';

                // Bouton popup
                $btn_data  = ' data-id="' . (int)($card['fk_commandedet'] ?? 0) . '"';
                $btn_data .= ' data-client="' . htmlspecialchars($card['client'] ?? '', ENT_QUOTES) . '"';
                $btn_data .= ' data-commande="' . htmlspecialchars(($card['commande_ref'] ?? '') . (!empty($card['version']) ? ' ' . $card['version'] : ''), ENT_QUOTES) . '"';
                $btn_data .= ' data-commande-url="' . htmlspecialchars(DOL_URL_ROOT . '/commande/card.php?id=' . (int)($card['fk_commande'] ?? 0), ENT_QUOTES) . '"';
                $btn_data .= ' data-ref="' . htmlspecialchars($card['ref_chantier'] ?? '', ENT_QUOTES) . '"';
                $btn_data .= ' data-produit="' . htmlspecialchars((!empty($card['produit_ref']) ? $card['produit_ref'] : ($card['produit'] ?? '')), ENT_QUOTES) . '"';
                $btn_data .= ' data-matiere="' . htmlspecialchars($card['matiere'] ?? '', ENT_QUOTES) . '"';
                $btn_data .= ' data-statut-mp="' . htmlspecialchars($card['statut_mp'] ?? '', ENT_QUOTES) . '"';
                $btn_data .= ' data-statut-prod="' . htmlspecialchars($card['statut_prod'] ?? '', ENT_QUOTES) . '"';
                $btn_data .= ' data-postlaquage="' . htmlspecialchars($card['postlaquage'] ?? 'non', ENT_QUOTES) . '"';
                echo '<td class="no-print"><input type="checkbox" class="row-checkbox" data-qty="' . floatval($card['quantity'] ?? 0) . '" data-unite="' . htmlspecialchars($card['unite'] ?? 'u', ENT_QUOTES) . '" onchange="updateSelectionNotification()"><button class="btn-popup-row"' . $btn_data . ' onclick="openCardModal(this)">✏️</button></td>';

                echo '</tr>';
            }
        }

        echo '</tbody>';
        echo '</table>';
    }
}

/**
 * Get total number of planned cards
 */
function getTotalPlannedCards($planned_cards) 
{
    $total = 0;
    if (is_array($planned_cards)) {
        foreach ($planned_cards as $week_data) {
            if (isset($week_data['cards']) && is_array($week_data['cards'])) {
                $total += count($week_data['cards']);
            }
        }
    }
    return $total;
}
?>