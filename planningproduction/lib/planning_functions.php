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
 * \file    lib/planning_functions.php
 * \ingroup planningproduction
 * \brief   Fonctions de génération HTML pour le planning de production
 */

// Pas de inclusion de main.inc.php ici car ce fichier est inclus depuis planning.php

/**
 * Génère le HTML d'une carte kanban
 *
 * @param array $card Données de la carte
 * @param Translate $langs Objet de traduction
 * @return string HTML de la carte
 */
function generateCardHTML($card, $langs) 
{
    $paint_required = (isset($card['postlaquage']) && $card['postlaquage'] == 'oui') ? ' paint-required' : '';
    
    // Déterminer la couleur de bordure selon les statuts MP et AR
    $border_class = '';
    $mp_ok = (isset($card['statut_mp']) && strpos($card['statut_mp'], 'MP Ok') !== false);
    $ar_ok = (isset($card['statut_ar']) && $card['statut_ar'] == 'AR VALIDÉ');
    
    if ($mp_ok && $ar_ok) {
        $border_class = ' border-green';
    } else {
        $border_class = ' border-red';
    }
    
    $html = '<div class="kanban-card' . $paint_required . $border_class . '" draggable="true" ';
    $html .= 'data-fk-commande="' . $card['fk_commande'] . '" ';
    $html .= 'data-fk-commandedet="' . $card['fk_commandedet'] . '" ';
    $html .= 'data-produit="' . htmlspecialchars($card['produit'] ?? '') . '" ';
    $html .= 'data-produit-ref="' . htmlspecialchars($card['produit_ref'] ?? '') . '" ';
    $html .= 'data-quantity="' . ($card['quantity'] ?? 0) . '" ';
    $html .= 'data-unite="' . htmlspecialchars($card['unite'] ?? 'u') . '">';
    
    // Header de la carte avec titre et actions
    $html .= '<div class="card-header">';
    $html .= '<div class="card-header-row">';
    $html .= '<div class="card-title">';
    
    // Titre : N° commande + Version + Client + Ref chantier (si présente)
    $command_link = dol_buildpath('/commande/card.php?id=' . $card['fk_commande'], 1);
    $client_link = isset($card['fk_soc']) ? dol_buildpath('/societe/card.php?socid=' . $card['fk_soc'], 1) : '#';
    
    $html .= '<a href="' . $command_link . '" class="card-commande" target="_blank">' . $card['commande_ref'] . '</a> ';
    $html .= 'V' . $card['version'] . ' ';
    $html .= '<a href="' . $client_link . '" class="card-tiers" target="_blank">' . $card['client'] . '</a>';

    // Afficher le ref_chantier du service (ID=361) si présent
    if (!empty($card['ref_chantier']) && $card['ref_chantier'] !== '-') {
        $html .= ' / ' . htmlspecialchars($card['ref_chantier']);
    }
    
    $html .= '</div>';
    
    $html .= '<div class="card-header-actions">';
    // Badges de statut dans le header
    $html .= '<div class="status-badges">';
    
    // Badge MP
    if (!empty($card['statut_mp'])) {
        $mp_parts = explode(',', $card['statut_mp']);
        $mp_text = trim($mp_parts[0]);
        if (strpos($mp_text, 'MP Ok') !== false) {
            $html .= '<span class="status-badge badge-mp-ok">MP OK</span>';
        } else {
            $html .= '<span class="status-badge badge-mp-waiting">' . htmlspecialchars($mp_text) . '</span>';
        }
    }
    
    // Badge AR
    if (!empty($card['statut_ar'])) {
        if ($card['statut_ar'] == 'AR VALIDÉ') {
            $html .= '<span class="status-badge badge-ar-ok">AR VALIDÉ</span>';
        } else {
            $html .= '<span class="status-badge badge-ar-waiting">' . htmlspecialchars($card['statut_ar']) . '</span>';
        }
    }
    
    // Badge à peindre supprimé - la couleur jaune fluo de la carte suffit
    
    // Badge statut production
    if (!empty($card['statut_prod'])) {
        $html .= '<span class="status-badge badge-production">' . htmlspecialchars($card['statut_prod']) . '</span>';
    }
    
    $html .= '</div>';
    
    // Actions
    $html .= '<div class="card-actions">';
    $html .= '<button class="card-btn card-btn-edit" title="' . $langs->trans('Editer') . '">✏️</button>';
    $html .= '<button class="card-btn card-btn-delete" title="' . $langs->trans('Deplanifier') . '">🗑️</button>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    // Informations de la carte
    $html .= '<div class="card-info">';
    
    // Grille 3 colonnes x 2 lignes
    $html .= '<div class="card-grid">';
    
    // Colonne A (Délai + Livraison)
    $html .= '<div class="card-grid-col-a">';
    $html .= '<div class="card-grid-cell"><span class="card-label">Délai:</span> <span class="card-value">' . htmlspecialchars($card['deadline']) . '</span></div>';
    $html .= '<div class="card-grid-cell"><span class="card-label">Livraison:</span> <span class="card-value">' . htmlspecialchars($card['delivery']) . '</span></div>';
    $html .= '</div>';
    
    // Colonne B (Produit + Matière)
    $html .= '<div class="card-grid-col-b">';
    $html .= '<div class="card-grid-cell card-grid-product">';
    if (!empty($card['produit'])) {
        if (!empty($card['produit_ref'])) {
            $product_link = dol_buildpath('/product/card.php?ref=' . urlencode($card['produit_ref']), 1);
            $html .= '<a href="' . $product_link . '" target="_blank">' . htmlspecialchars($card['produit']) . '</a>';
        } else {
            $html .= htmlspecialchars($card['produit']);
        }
    } else {
        $html .= '-';
    }
    $html .= '</div>';
    $html .= '<div class="card-grid-cell"><span class="card-label">Matière:</span> <span class="card-value">' . htmlspecialchars($card['matiere']) . '</span></div>';
    $html .= '</div>';
    
    // Colonne C (Label Quantité + Valeur)
    $html .= '<div class="card-grid-col-c">';
    $html .= '<div class="card-grid-cell card-grid-qty-label">Quantité:</div>';
    $html .= '<div class="card-grid-cell card-grid-qty-value">' . $card['quantity'] . ' ' . $card['unite'] . '</div>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Génère le HTML d'une semaine avec ses groupes de production
 *
 * @param int $week_num Numéro de semaine
 * @param int $year Année
 * @param array $week_data Données de la semaine avec cartes groupées
 * @param Translate $langs Objet de traduction
 * @return string HTML de la semaine
 */
function generateWeekHTML($week_num, $year, $week_data, $langs) 
{
    // Calculer les dates de début et fin de semaine
    $week_start = new DateTime();
    $week_start->setISODate($year, $week_num);
    $week_end = clone $week_start;
    $week_end->add(new DateInterval('P6D'));
    
    $html = '<div class="week-row" data-week="' . $week_num . '">';
    $html .= '<div class="week-header">';
    $html .= '<div class="week-info">';
    $html .= '<span>SEMAINE ' . sprintf('%02d', $week_num) . '</span>';
    $html .= '<span style="font-size: 12px; color: rgba(255,255,255,0.8);">';
    $html .= $week_start->format('d/m') . ' - ' . $week_end->format('d/m');
    $html .= '</span>';
    $html .= '<span class="week-count">' . $week_data['elements'] . ' éléments</span>';
    $html .= '<span class="week-count">' . $week_data['groups'] . ' groupes</span>';
    $html .= '</div>';
    
    $html .= '<div class="week-actions">';
    $html .= '<button class="btn btn-sm btn-success" onclick="validerSemaine(' . $week_num . ')">✅ Valider</button>';
    $html .= '<button class="btn btn-sm btn-primary" onclick="exportSemaine(' . $week_num . ')">📊 Export</button>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Corps de la semaine avec les groupes
    $html .= '<div class="week-groups" data-week="' . $week_num . '">';
    
    if (empty($week_data['cards'])) {
        // Semaine vide
        $html .= '<div class="empty-week" data-week="' . $week_num . '">';
        $html .= '<div>📅 Aucune carte planifiée</div>';
        $html .= '<div style="font-size: 11px; color: rgba(149, 165, 166, 0.7); margin-top: 5px;">Glissez des cartes ici pour les planifier</div>';
        $html .= '</div>';
    } else {
        // Grouper les cartes par groupe
        $groups = array();
        foreach ($week_data['cards'] as $card) {
            $group_name = $card['groupe'] ?? 'Groupe par défaut';
            if (!isset($groups[$group_name])) {
                $groups[$group_name] = array();
            }
            $groups[$group_name][] = $card;
        }
        
        // Afficher chaque groupe
        foreach ($groups as $group_name => $cards) {
            $html .= generateGroupHTML($group_name, $cards, $week_num, $langs);
        }
        
        // Zone pour créer un nouveau groupe
        $html .= '<div class="new-group-zone" data-week="' . $week_num . '">';
        $html .= '<div>➕ Nouveau groupe</div>';
        $html .= '<div style="font-size: 11px; margin-top: 5px;">Glissez une carte ici pour créer un nouveau groupe</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    // Footer avec statistiques
    if (!empty($week_data['cards'])) {
        $html .= '<div class="week-stats">';
        $html .= $week_data['elements'] . ' élément' . ($week_data['elements'] > 1 ? 's' : '') . ' dans ' . $week_data['groups'] . ' groupe' . ($week_data['groups'] > 1 ? 's' : '');
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Génère le HTML d'un groupe de production
 *
 * @param string $group_name Nom du groupe
 * @param array $cards Cartes du groupe
 * @param int $week_num Numéro de semaine
 * @param Translate $langs Objet de traduction
 * @return string HTML du groupe
 */
function generateGroupHTML($group_name, $cards, $week_num, $langs)
{
    // Calculer la quantité totale et récupérer le produit_ref du groupe
    $total_qty = 0;
    $group_unite = 'u';
    $group_produit_ref = '';
    foreach ($cards as $card) {
        $total_qty += floatval($card['quantity'] ?? 0);
        if (empty($group_produit_ref) && !empty($card['produit_ref'])) {
            $group_produit_ref = $card['produit_ref'];
            $group_unite = $card['unite'] ?? 'u';
        }
    }

    $html = '<div class="production-group expanded" data-group="' . htmlspecialchars($group_name) . '" data-produit-ref="' . htmlspecialchars($group_produit_ref) . '">';

    // Header du groupe (draggable)
    $html .= '<div class="group-header" draggable="true">';
    $qty_display = ($total_qty == intval($total_qty)) ? intval($total_qty) : $total_qty;
    $html .= '<span class="group-count">' . $qty_display . ' ' . htmlspecialchars($group_unite) . '</span>';
    $html .= '<div class="group-title">';
    $html .= '📁 <input type="text" value="' . htmlspecialchars($group_name) . '" placeholder="Nom du groupe">';
    $html .= '</div>';
    $html .= '<div class="group-controls">';
    $html .= '<button class="group-toggle" onclick="toggleGroup(this)">🔽</button>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Cartes du groupe
    $html .= '<div class="group-cards">';
    foreach ($cards as $card) {
        $html .= generateCardHTML($card, $langs);
    }
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}
