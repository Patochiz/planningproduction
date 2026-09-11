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
 * \file    historique.php
 * \ingroup planningproduction
 * \brief   Historique des lignes de commande livrées
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
dol_include_once('/planningproduction/class/planningproduction.class.php');

$langs->loadLangs(array("planningproduction@planningproduction", "other"));

if (!$user->hasRight('planningproduction', 'planning', 'read')) {
    accessforbidden();
}

// Parameters
$date_start = GETPOST('date_start', 'alpha');
$date_end = GETPOST('date_end', 'alpha');

$show_all = GETPOST('show_all', 'int');

// Default: last 90 days (unless "show all" is requested)
if (empty($date_start) && empty($date_end) && empty($show_all)) {
    $date_start = date('Y-m-d', strtotime('-90 days'));
    $date_end = date('Y-m-d');
}
if (!empty($show_all)) {
    $date_start = '';
    $date_end = '';
}

$object = new PlanningProduction($db);

$matieres_liens = array();
$all_matieres = $object->getAllMatieres(false);
if ($all_matieres !== false) {
    foreach ($all_matieres as $m) {
        if (!empty($m['lien'])) {
            $matieres_liens[$m['code_mp']] = $m['lien'];
        }
    }
}

$data = $object->getDeliveredCards($date_start, $date_end);
if ($data === false) {
    $data = array();
}

$title = 'Historique des livraisons';
$subtitle = 'Lignes de commande livrées';
if (!empty($date_start) || !empty($date_end)) {
    $parts = array();
    if (!empty($date_start)) $parts[] = 'du ' . date('d/m/Y', strtotime($date_start));
    if (!empty($date_end)) $parts[] = 'au ' . date('d/m/Y', strtotime($date_end));
    $subtitle .= ' - ' . implode(' ', $parts);
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - <?php echo htmlspecialchars($conf->global->MAIN_INFO_SOCIETE_NOM); ?></title>

    <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: 'Arial', sans-serif;
        font-size: 11pt;
        line-height: 1.4;
        color: #333;
        background: white;
        margin: 15px;
        padding-top: 56px;
    }

    .export-header {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 2px solid #333;
    }
    .export-title { font-size: 18pt; font-weight: bold; color: #2c3e50; margin-bottom: 5px; }
    .export-subtitle { font-size: 12pt; color: #7f8c8d; margin-bottom: 10px; }
    .export-info {
        font-size: 9pt; color: #95a5a6;
        display: flex; justify-content: space-between; align-items: center;
    }

    .export-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        font-size: 9pt;
    }
    .export-table th {
        background: #34495e; color: white;
        padding: 8px 6px; text-align: left;
        font-weight: bold; border: 1px solid #2c3e50; font-size: 9pt;
    }
    .export-table td {
        padding: 6px; border: 1px solid #bdc3c7;
        vertical-align: top; font-size: 9pt;
    }
    .export-table tr:nth-child(even) { background: #f8f9fa; }
    .export-table tr:hover { background: #e8f4fd; }

    .col-commande { width: 13%; }
    .col-ref { width: 11%; }
    .col-delai { width: 4%; }
    .col-produit { width: 20%; }
    .col-matiere { width: 12%; }
    .col-qte { width: 8%; text-align: right; }
    .col-expedie { width: 8%; text-align: right; }
    .col-date-exp { width: 8%; }
    .col-livraison { width: 10%; }
    .col-statuts { width: 6%; }

    .badge-vn {
        display: inline-block; background: #e74c3c; color: #fff;
        font-size: 8pt; font-weight: bold; padding: 1px 4px;
        border-radius: 3px; vertical-align: middle;
    }

    .status-badge {
        display: inline-block; padding: 2px 4px; border-radius: 3px;
        font-size: 7pt; font-weight: bold; text-transform: uppercase;
        margin: 1px; white-space: nowrap;
    }
    .badge-mp-ok { background: #d5f4e6; color: #27ae60; }
    .badge-mp-waiting { background: #f8d7da; color: #e74c3c; }
    .badge-ar-ok { background: #d5f4e6; color: #27ae60; }
    .badge-ar-waiting { background: #f8d7da; color: #e74c3c; }
    .badge-production { background: #e8f4fd; color: #3498db; }
    .badge-delivered { background: #d5f4e6; color: #27ae60; }
    .status-cell { font-size: 7pt; line-height: 1.2; }

    .empty-message {
        text-align: center; padding: 30px; color: #95a5a6;
        font-style: italic; border: 2px dashed #bdc3c7;
        border-radius: 8px; background: #f8f9fa;
    }

    .export-stats {
        margin-top: 20px; padding: 15px; background: #ecf0f1;
        border-radius: 6px; text-align: center;
        font-size: 10pt; color: #2c3e50;
    }

    /* Filter bar */
    .filter-bar {
        position: fixed; top: 0; left: 0; right: 0; z-index: 200;
        background: #fff; border-bottom: 2px solid #dee2e6;
        padding: 7px 16px; box-shadow: 0 2px 6px rgba(0,0,0,.1);
    }
    .filter-bar-inner {
        display: flex; gap: 8px; align-items: center; flex-wrap: wrap;
    }
    .filter-label { font-size: 12px; font-weight: 600; color: #495057; margin-right: 2px; }
    .filter-input {
        border: 1px solid #ced4da; border-radius: 4px;
        padding: 4px 8px; font-size: 13px; width: 130px;
    }
    .filter-input:focus { outline: none; border-color: #0d6efd; box-shadow: 0 0 0 2px rgba(13,110,253,.15); }
    .filter-date {
        border: 1px solid #ced4da; border-radius: 4px;
        padding: 4px 8px; font-size: 13px; width: 140px; background: #fff;
    }
    .filter-date:focus { outline: none; border-color: #0d6efd; }
    .filter-clear-btn {
        background: #6c757d; color: #fff; border: none;
        border-radius: 4px; padding: 4px 10px; cursor: pointer; font-size: 13px;
    }
    .filter-clear-btn:hover { background: #5a6268; }
    .filter-apply-btn {
        background: #3498db; color: #fff; border: none;
        border-radius: 4px; padding: 4px 12px; cursor: pointer; font-size: 13px; font-weight: 600;
    }
    .filter-apply-btn:hover { background: #2980b9; }
    .filter-count { font-size: 12px; color: #6c757d; font-style: italic; }

    tr.filter-hidden { display: none; }

    /* Buttons */
    .export-actions {
        position: fixed; top: 20px; right: 20px;
        display: flex; gap: 10px; z-index: 1000;
    }
    .btn {
        padding: 8px 16px; border: none; border-radius: 4px;
        cursor: pointer; font-size: 11pt; font-weight: bold;
        text-decoration: none; display: inline-flex;
        align-items: center; gap: 6px; transition: all 0.2s;
    }
    .btn-print { background: #3498db; color: white; }
    .btn-print:hover { background: #2980b9; }
    .btn-back { background: #95a5a6; color: white; }
    .btn-back:hover { background: #7f8c8d; }

    .no-print { }

    /* Selection notification */
    .selection-notification {
        position: fixed; top: 70px; right: 20px;
        background: #3498db; color: white;
        padding: 10px 18px; border-radius: 8px;
        font-size: 13px; font-weight: 500; z-index: 999;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .selection-notification strong { margin-right: 6px; }

    .row-checkbox {
        cursor: pointer; width: 16px; height: 16px;
        vertical-align: middle; margin-right: 4px;
    }

    @media print {
        body { font-size: 9pt; line-height: 1.3; padding-top: 0; }
        .export-header { margin-bottom: 20px; }
        .export-title { font-size: 16pt; }
        .export-subtitle { font-size: 11pt; }
        .export-table { font-size: 8pt; }
        .export-table th, .export-table td { padding: 4px; }
        .status-badge { font-size: 6pt; padding: 1px 3px; }
        .export-table tr { page-break-inside: avoid; }
        .no-print { display: none !important; }
        .export-actions { display: none !important; }
        .filter-bar { display: none !important; }
    }
    </style>
</head>
<body>
    <!-- Actions -->
    <div class="export-actions">
        <button class="btn btn-print" onclick="window.print()">&#128424; Imprimer</button>
        <a href="export_planning.php?type=global" class="btn btn-back">&#128203; Planning</a>
    </div>

    <!-- Filter bar -->
    <div class="filter-bar no-print" id="filterBar">
        <div class="filter-bar-inner">
            <span class="filter-label">Du</span>
            <input type="date" id="filterDateStart" class="filter-date" value="<?php echo htmlspecialchars($date_start); ?>">
            <span class="filter-label">au</span>
            <input type="date" id="filterDateEnd" class="filter-date" value="<?php echo htmlspecialchars($date_end); ?>">
            <button class="filter-apply-btn" onclick="applyDateFilter()">Appliquer</button>
            <button class="filter-clear-btn" onclick="window.location.href='historique.php?show_all=1'">Tout afficher</button>
            <span style="color:#ccc;">|</span>
            <input type="text" id="filterCommande" placeholder="Commande&#8230;" class="filter-input" oninput="applyTextFilters()">
            <input type="text" id="filterRef" placeholder="R&#233;f. chantier&#8230;" class="filter-input" oninput="applyTextFilters()">
            <input type="text" id="filterProduit" placeholder="Produit&#8230;" class="filter-input" oninput="applyTextFilters()">
            <input type="text" id="filterMatiere" placeholder="Mati&#232;re&#8230;" class="filter-input" oninput="applyTextFilters()">
            <button class="filter-clear-btn" onclick="clearTextFilters()">&#10005; Effacer</button>
            <span id="filterCount" class="filter-count"></span>
        </div>
    </div>

    <!-- Selection notification -->
    <div class="selection-notification no-print" id="selectionNotification" style="display:none;">
        <strong>S&#233;lection :</strong>
        <span id="selectionSummary"></span>
    </div>

    <!-- Header -->
    <div class="export-header">
        <div class="export-title"><?php echo htmlspecialchars($title); ?></div>
        <div class="export-subtitle"><?php echo htmlspecialchars($subtitle); ?></div>
        <div class="export-info">
            <span><?php echo htmlspecialchars($conf->global->MAIN_INFO_SOCIETE_NOM ?: 'Planning Production'); ?></span>
            <span>G&#233;n&#233;r&#233; le <?php echo dol_print_date(dol_now(), '%d/%m/%Y à %H:%M'); ?></span>
        </div>
    </div>

    <?php if (empty($data)): ?>
        <div class="empty-message">Aucune livraison trouv&#233;e pour la p&#233;riode s&#233;lectionn&#233;e.</div>
    <?php else: ?>
        <table class="export-table">
        <thead>
        <tr>
            <th class="col-commande">Commande</th>
            <th class="col-ref">Ref.</th>
            <th class="col-delai">D&#233;lai</th>
            <th class="col-produit">Produit</th>
            <th class="col-matiere">Mati&#232;re</th>
            <th class="col-qte">Qt&#233; cde</th>
            <th class="col-expedie">Exp&#233;di&#233;</th>
            <th class="col-date-exp">Date exp.</th>
            <th class="col-livraison">Livraison</th>
            <th class="col-statuts">Statuts</th>
            <th class="no-print" style="width:3%"></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($data as $card) {
            echo '<tr>';

            // Commande
            $commande_cell = htmlspecialchars($card['client'] ?? '-');
            $commande_cell .= '<br><small>' . htmlspecialchars($card['commande_ref'] ?? '-');
            if (!empty($card['version'])) {
                $commande_cell .= ' ' . htmlspecialchars($card['version']);
            }
            $commande_cell .= '</small>';
            echo '<td>' . $commande_cell . '</td>';

            // Ref chantier
            echo '<td>' . htmlspecialchars($card['ref_chantier'] ?? '-') . '</td>';

            // Délai
            echo '<td>' . htmlspecialchars($card['deadline'] ?? '-') . '</td>';

            // Produit
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
                echo '<td><a href="' . htmlspecialchars($matiere_lien) . '" target="_blank" style="color:inherit;text-decoration:underline;">' . htmlspecialchars($matiere_val) . '</a></td>';
            } else {
                echo '<td>' . htmlspecialchars($matiere_val) . '</td>';
            }

            // Qté commandée
            echo '<td style="text-align:right">' . htmlspecialchars(number_format(floatval($card['quantity'] ?? 0), 2, ',', '') . ' ' . ($card['unite'] ?? 'u')) . '</td>';

            // Qté expédiée
            echo '<td style="text-align:right">' . htmlspecialchars(number_format(floatval($card['qty_shipped'] ?? 0), 2, ',', '') . ' ' . ($card['unite'] ?? 'u')) . '</td>';

            // Date expédition
            $date_exp = '-';
            if (!empty($card['date_expedition'])) {
                $ts = strtotime($card['date_expedition']);
                if ($ts) $date_exp = date('d/m/Y', $ts);
            }
            echo '<td>' . htmlspecialchars($date_exp) . '</td>';

            // Livraison
            echo '<td>' . htmlspecialchars($card['delivery'] ?? '-') . '</td>';

            // Statuts
            echo '<td class="status-cell">';
            echo '<span class="status-badge badge-delivered">LIVR&#201;</span>';
            if (!empty($card['statut_prod']) && $card['statut_prod'] !== '-') {
                echo '<span class="status-badge badge-production">' . htmlspecialchars($card['statut_prod']) . '</span>';
            }
            echo '</td>';

            // Checkbox
            echo '<td class="no-print"><input type="checkbox" class="row-checkbox" data-qty="' . floatval($card['quantity'] ?? 0) . '" data-unite="' . htmlspecialchars($card['unite'] ?? 'u', ENT_QUOTES) . '" onchange="updateSelectionNotification()"></td>';

            echo '</tr>';
        }
        ?>
        </tbody>
        </table>

        <div class="export-stats">
            <strong><?php echo count($data); ?> ligne<?php echo count($data) > 1 ? 's' : ''; ?> livr&#233;e<?php echo count($data) > 1 ? 's' : ''; ?></strong>
            <?php
            $total_qty = 0;
            $qty_by_unit = array();
            foreach ($data as $c) {
                $u = $c['unite'] ?? 'u';
                if (!isset($qty_by_unit[$u])) $qty_by_unit[$u] = 0;
                $qty_by_unit[$u] += floatval($c['quantity'] ?? 0);
            }
            $parts = array();
            foreach ($qty_by_unit as $u => $q) {
                $display = ($q == intval($q)) ? intval($q) : number_format($q, 2, ',', '');
                $parts[] = $display . ' ' . htmlspecialchars($u);
            }
            if (!empty($parts)) {
                echo ' &mdash; ' . implode(' | ', $parts);
            }
            ?>
        </div>
    <?php endif; ?>

    <script type="text/javascript">
    function applyDateFilter() {
        var start = document.getElementById('filterDateStart').value;
        var end   = document.getElementById('filterDateEnd').value;
        var url = window.location.pathname + '?';
        if (start) url += 'date_start=' + encodeURIComponent(start) + '&';
        if (end)   url += 'date_end=' + encodeURIComponent(end);
        window.location.href = url;
    }

    function applyTextFilters() {
        var fCommande = document.getElementById('filterCommande').value.toLowerCase().trim();
        var fRef      = document.getElementById('filterRef').value.toLowerCase().trim();
        var fProduit  = document.getElementById('filterProduit').value.toLowerCase().trim();
        var fMatiere  = document.getElementById('filterMatiere').value.toLowerCase().trim();

        var visibleTotal = 0;

        document.querySelectorAll('.export-table tbody tr').forEach(function(row) {
            var commande = (row.cells[0] ? row.cells[0].textContent : '').toLowerCase();
            var ref      = (row.cells[1] ? row.cells[1].textContent : '').toLowerCase();
            var produit  = (row.cells[3] ? row.cells[3].textContent : '').toLowerCase();
            var matiere  = (row.cells[4] ? row.cells[4].textContent : '').toLowerCase();

            var match =
                (!fCommande || commande.indexOf(fCommande) !== -1) &&
                (!fRef      || ref.indexOf(fRef) !== -1)           &&
                (!fProduit  || produit.indexOf(fProduit) !== -1)   &&
                (!fMatiere  || matiere.indexOf(fMatiere) !== -1);

            row.classList.toggle('filter-hidden', !match);
            if (match) visibleTotal++;
        });

        var anyFilter = fCommande || fRef || fProduit || fMatiere;
        document.getElementById('filterCount').textContent =
            anyFilter ? visibleTotal + ' ligne' + (visibleTotal > 1 ? 's' : '') + ' affichée' + (visibleTotal > 1 ? 's' : '') : '';
    }

    function clearTextFilters() {
        ['filterCommande','filterRef','filterProduit','filterMatiere'].forEach(function(id) {
            document.getElementById(id).value = '';
        });
        applyTextFilters();
    }

    function updateSelectionNotification() {
        var checked = document.querySelectorAll('.row-checkbox:checked');
        var notif = document.getElementById('selectionNotification');
        var summary = document.getElementById('selectionSummary');

        if (checked.length === 0) { notif.style.display = 'none'; return; }

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
