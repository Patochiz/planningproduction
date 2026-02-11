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
        $to_ship = $object->getCardsByStatus('a_expedier');
        $planned_cards = $object->getPlannedCards($start_week, $week_count, $year);
        
        $title = $langs->trans('ExportGlobal');
        $subtitle = "Export global du planning de production";
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
    .col-commande { width: 15%; }
    .col-ref { width: 12%; }
    .col-delai { width: 4%; }
    .col-produit { width: 25%; }
    .col-matiere { width: 14%; }
    .col-qte { width: 8%; text-align: right; }
    .col-livraison { width: 12%; }
    .col-statuts { width: 10%; }

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
        .col-ref { width: 12%; }
        .col-delai { width: 4%; }
        .col-produit { width: 25%; }
        .col-matiere { width: 14%; }
        .col-qte { width: 8%; text-align: right; }
        .col-livraison { width: 12%; }
        .col-statuts { width: 10%; }
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

    .matieres-table tbody tr:hover { background-color: #f8f9fa; }
    .matieres-table tbody tr:last-child td { border-bottom: none; }

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

    .btn-update-cde:hover { background: #218838; transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .btn-update-cde:active { transform: translateY(0); }

    .numeric-cell { text-align: right; font-weight: 500; }
    .stock-alert { background-color: #ffebee !important; color: #c62828 !important; font-weight: bold; }
    .row-desync { background-color: #ffe5e5 !important; }
    .row-desync td { background-color: inherit !important; }

    .matiere-message { padding: 12px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
    .matiere-message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .matiere-message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .matiere-message.info { background-color: #cce7ff; color: #004085; border: 1px solid #b6d7ff; }

    @media print {
        .export-actions {
            display: none !important;
        }
        .matiere-modal {
            display: none !important;
        }
    }
    </style>
</head>
<body>
    <!-- Actions (non imprimées) -->
    <div class="export-actions">
        <button class="btn btn-matieres" onclick="openMatieresModal()">🧱 Matières</button>
        <button class="btn btn-print" onclick="window.print()">🖨️ Imprimer</button>
        <a href="planning.php" class="btn btn-back">✏️ Modifier</a>
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
            <?php echo count($to_ship); ?> à expédier •
            <strong>Total : <?php echo (count($unplanned) + count($to_finish) + count($to_ship) + getTotalPlannedCards($planned_cards)); ?> éléments</strong>
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

</body>
</html>

<?php

/**
 * Render table of cards with new column order
 */
function renderCardsTable($cards, $langs) 
{
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

        // Matière
        echo '<td>' . htmlspecialchars($card['matiere'] ?? '-') . '</td>';

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

            echo '<tr><td colspan="8" class="group-separator"><span class="group-qty-badge">' . $qty_display . ' ' . htmlspecialchars($group_unite) . '</span>📁 ' . htmlspecialchars($group_name) . '</td></tr>';
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
                
                // Matière
                echo '<td>' . htmlspecialchars($card['matiere'] ?? '-') . '</td>';
                
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