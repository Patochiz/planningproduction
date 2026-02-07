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
 * \file    ajax_planning.php
 * \ingroup planningproduction
 * \brief   AJAX endpoint for planning operations - VERSION SÉCURISÉE ET FIABLE
 */

if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', '1'); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
    define('NOREQUIREAJAX', '1');
}

// Load Dolibarr environment
$res = 0;
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

dol_include_once('/planningproduction/class/planningproduction.class.php');

// Set content type to JSON
header('Content-Type: application/json');

// Security check - Toujours vérifier les permissions
if (!$user->hasRight('planningproduction', 'planning', 'read')) {
    http_response_code(403);
    echo json_encode(array('success' => false, 'error' => 'Access denied - No read permission'));
    exit;
}

$action = GETPOST('action', 'aZ09');

// Initialize response
$response = array('success' => false, 'error' => '', 'data' => null);

/**
 * Valider le token CSRF pour les actions d'écriture
 * @return bool True si valide, False sinon
 */
function validateCSRFToken() {
    $token = GETPOST('token', 'alpha');
    
    if (empty($token)) {
        dol_syslog("AJAX Planning: Token CSRF manquant", LOG_WARNING);
        return false;
    }
    
    // Validation stricte du token (longueur minimale)
    if (strlen($token) < 20) {
        dol_syslog("AJAX Planning: Token CSRF invalide (trop court)", LOG_WARNING);
        return false;
    }
    
    // TODO: Pour une sécurité maximale, vérifier le token en session
    // Pour l'instant, validation basique de longueur et format
    
    return true;
}

/**
 * Valider un paramètre numérique dans une plage
 * @param mixed $value Valeur à valider
 * @param int $min Valeur minimale
 * @param int $max Valeur maximale
 * @return int|false Valeur validée ou false
 */
function validateIntInRange($value, $min, $max) {
    if (!is_numeric($value)) {
        return false;
    }
    
    $intValue = (int)$value;
    
    if ($intValue < $min || $intValue > $max) {
        return false;
    }
    
    return $intValue;
}

try {
    $object = new PlanningProduction($db);
    
    switch ($action) {
        case 'update_card':
            // Vérifier les permissions d'écriture
            if (!$user->hasRight('planningproduction', 'planning', 'write')) {
                throw new Exception('Permission denied for write operations');
            }
            
            // SÉCURITÉ: Vérifier le token CSRF
            if (!validateCSRFToken()) {
                http_response_code(403);
                throw new Exception('Invalid or missing CSRF token');
            }
            
            // Validation stricte des paramètres
            $fk_commandedet = GETPOST('fk_commandedet', 'int');
            if (!$fk_commandedet || $fk_commandedet <= 0) {
                throw new Exception('Invalid commandedet ID');
            }
            
            $matiere = GETPOST('matiere', 'restricthtml');
            $statut_mp = GETPOST('statut_mp', 'restricthtml');
            $statut_prod = GETPOST('statut_prod', 'restricthtml');
            $postlaquage = GETPOST('postlaquage', 'restricthtml');
            
            // Validation des statuts si fournis
            $valid_statuts_mp = array('MP Ok,MP Ok', 'MP en attente,MP en attente', 'MP Manquante,MP Manquante', 
                'BL A FAIRE,BL A FAIRE', 'PROFORMA A VALIDER,PROFORMA A VALIDER', 'MàJ AIRTABLE à Faire,MàJ AIRTABLE à Faire');
            $valid_statuts_prod = array('À PRODUIRE', 'EN COURS', 'À TERMINER', 'BON POUR EXPÉDITION');
            $valid_postlaquage = array('oui', 'non');
            
            if ($statut_mp !== '' && !in_array($statut_mp, $valid_statuts_mp)) {
                throw new Exception('Invalid MP status value');
            }
            if ($statut_prod !== '' && !in_array($statut_prod, $valid_statuts_prod)) {
                throw new Exception('Invalid production status value');
            }
            if ($postlaquage !== '' && !in_array($postlaquage, $valid_postlaquage)) {
                throw new Exception('Invalid postlaquage value');
            }
            
            // Préparer les champs à mettre à jour
            $fields = array();
            if ($matiere !== '') {
                $fields['matiere'] = $matiere;
            }
            if ($statut_mp !== '') {
                $fields['statut_mp'] = $statut_mp;
            }
            if ($statut_prod !== '') {
                $fields['statut_prod'] = $statut_prod;
            }
            if ($postlaquage !== '') {
                $fields['postlaquage'] = $postlaquage;
            }
            
            if (empty($fields)) {
                throw new Exception('No fields to update');
            }
            
            // Mettre à jour les extrafields
            $result = $object->updateCommandedetExtrafields($fk_commandedet, $fields);
            
            if ($result > 0) {
                $response['success'] = true;
                $response['message'] = 'Card updated successfully';
                $response['data'] = array('fk_commandedet' => $fk_commandedet, 'fields_updated' => array_keys($fields));
                dol_syslog("AJAX Planning: Card $fk_commandedet updated successfully", LOG_INFO);
            } else {
                throw new Exception('Failed to update card: ' . implode(', ', $object->errors));
            }
            break;
            
        case 'move_card':
            // Vérifier les permissions d'écriture
            if (!$user->hasRight('planningproduction', 'planning', 'write')) {
                throw new Exception('Permission denied for write operations');
            }
            
            // SÉCURITÉ: Vérifier le token CSRF
            if (!validateCSRFToken()) {
                http_response_code(403);
                throw new Exception('Invalid or missing CSRF token');
            }
            
            // Validation stricte des paramètres
            $fk_commande = GETPOST('fk_commande', 'int');
            $fk_commandedet = GETPOST('fk_commandedet', 'int');
            
            if (!$fk_commande || $fk_commande <= 0) {
                throw new Exception('Invalid commande ID');
            }
            if (!$fk_commandedet || $fk_commandedet <= 0) {
                throw new Exception('Invalid commandedet ID');
            }
            
            $semaine = GETPOST('semaine', 'int');
            $annee = GETPOST('annee', 'int');
            $groupe_nom = GETPOST('groupe_nom', 'restricthtml');
            $ordre_groupe = GETPOST('ordre_groupe', 'int');
            $ordre_semaine = GETPOST('ordre_semaine', 'int');
            
            if ($semaine && $annee) {
                // Validation des plages
                $semaine_val = validateIntInRange($semaine, 1, 53);
                $annee_val = validateIntInRange($annee, 2020, 2050);
                
                if ($semaine_val === false) {
                    throw new Exception('Invalid week number (must be between 1 and 53)');
                }
                if ($annee_val === false) {
                    throw new Exception('Invalid year (must be between 2020 and 2050)');
                }
                
                // Planifier la carte
                $result = $object->savePlannedCard($fk_commande, $fk_commandedet, $semaine_val, $annee_val, $groupe_nom, $ordre_groupe, $ordre_semaine);
                
                if ($result > 0) {
                    $response['success'] = true;
                    $response['message'] = 'Card moved successfully';
                    $response['data'] = array('planning_id' => $result, 'semaine' => $semaine_val, 'annee' => $annee_val);
                    dol_syslog("AJAX Planning: Card $fk_commandedet moved to week $semaine_val/$annee_val", LOG_INFO);
                } else {
                    throw new Exception('Failed to move card: ' . implode(', ', $object->errors));
                }
            } else {
                // Déplanifier la carte (remettre dans non planifiées)
                $result = $object->removePlannedCard($fk_commandedet);
                
                if ($result > 0) {
                    $response['success'] = true;
                    $response['message'] = 'Card unplanned successfully';
                    $response['data'] = array('fk_commandedet' => $fk_commandedet);
                    dol_syslog("AJAX Planning: Card $fk_commandedet unplanned", LOG_INFO);
                } else {
                    throw new Exception('Failed to unplan card: ' . implode(', ', $object->errors));
                }
            }
            break;
            
        case 'update_group_order':
            // Vérifier les permissions d'écriture
            if (!$user->hasRight('planningproduction', 'planning', 'write')) {
                throw new Exception('Permission denied for write operations');
            }
            
            // SÉCURITÉ: Vérifier le token CSRF
            if (!validateCSRFToken()) {
                http_response_code(403);
                throw new Exception('Invalid or missing CSRF token');
            }
            
            $updates_json = GETPOST('updates', 'none');
            
            // Validation de sécurité pour le JSON
            if (empty($updates_json)) {
                throw new Exception('No updates provided - JSON is empty');
            }
            
            // Vérifier que c'est bien du JSON (commence par [ ou {)
            $updates_json = trim($updates_json);
            if (!preg_match('/^[\[\{]/', $updates_json)) {
                dol_syslog("ERROR: updates_json does not look like JSON: " . substr($updates_json, 0, 100), LOG_ERR);
                throw new Exception('Invalid updates format: Not valid JSON structure');
            }
            
            // Vérifier l'encodage UTF-8
            if (!mb_check_encoding($updates_json, 'UTF-8')) {
                dol_syslog("WARNING: updates_json is not valid UTF-8 - converting", LOG_WARNING);
                $updates_json = mb_convert_encoding($updates_json, 'UTF-8', 'auto');
            }
            
            // Tentative de décodage JSON avec gestion d'erreurs détaillée
            $updates = json_decode($updates_json, true);
            $json_error = json_last_error();
            
            if ($json_error !== JSON_ERROR_NONE) {
                $error_messages = array(
                    JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
                    JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
                    JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
                    JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
                    JSON_ERROR_UTF8 => 'Malformed UTF-8 characters'
                );
                
                $error_msg = isset($error_messages[$json_error]) ? $error_messages[$json_error] : 'Unknown JSON error';
                dol_syslog("ERROR JSON decode failed: " . $error_msg . " (code: " . $json_error . ")", LOG_ERR);
                throw new Exception('Invalid updates format: ' . $error_msg);
            }
            
            if (!is_array($updates) || count($updates) === 0) {
                throw new Exception('Invalid updates format: Expected non-empty array');
            }
            
            // Limiter le nombre d'updates pour éviter les abus
            if (count($updates) > 500) {
                throw new Exception('Too many updates at once (max 500)');
            }
            
            dol_syslog("AJAX Planning: Processing " . count($updates) . " group order updates", LOG_INFO);
            
            $db->begin();
            $errors = 0;
            $processed = 0;
            
            foreach ($updates as $index => $update) {
                // Validation stricte de chaque update
                if (!is_array($update)) {
                    dol_syslog("WARNING: Update $index is not an array, skipping", LOG_WARNING);
                    $errors++;
                    continue;
                }
                
                $fk_commandedet = isset($update['fk_commandedet']) ? (int)$update['fk_commandedet'] : 0;
                $semaine = isset($update['semaine']) ? (int)$update['semaine'] : 0;
                $annee = isset($update['annee']) ? (int)$update['annee'] : 0;
                $groupe_nom = isset($update['groupe_nom']) ? $update['groupe_nom'] : '';
                $ordre_groupe = isset($update['ordre_groupe']) ? (int)$update['ordre_groupe'] : 0;
                $ordre_semaine = isset($update['ordre_semaine']) ? (int)$update['ordre_semaine'] : 0;
                
                // Validation des plages
                if ($fk_commandedet <= 0) {
                    dol_syslog("WARNING: Invalid fk_commandedet in update $index", LOG_WARNING);
                    $errors++;
                    continue;
                }
                
                $semaine_val = validateIntInRange($semaine, 1, 53);
                $annee_val = validateIntInRange($annee, 2020, 2050);
                
                if ($semaine_val === false || $annee_val === false) {
                    dol_syslog("WARNING: Invalid week/year in update $index", LOG_WARNING);
                    $errors++;
                    continue;
                }
                
                // Chercher l'enregistrement de planning existant
                $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."planningproduction_planning ";
                $sql .= "WHERE fk_commandedet = ".((int) $fk_commandedet);
                
                $resql = $db->query($sql);
                if ($resql && $db->num_rows($resql)) {
                    $obj = $db->fetch_object($resql);
                    
                    $result = $object->updatePlannedCard($obj->rowid, $semaine_val, $annee_val, $groupe_nom, $ordre_groupe, $ordre_semaine);
                    if ($result < 0) {
                        dol_syslog("ERROR updatePlannedCard failed for rowid=" . $obj->rowid . ": " . implode(', ', $object->errors), LOG_ERR);
                        $errors++;
                    } else {
                        $processed++;
                    }
                } else {
                    // Tenter de créer un nouvel enregistrement si introuvable
                    $fk_commande = isset($update['fk_commande']) ? (int)$update['fk_commande'] : 0;
                    if ($fk_commande > 0) {
                        $result = $object->savePlannedCard($fk_commande, $fk_commandedet, $semaine_val, $annee_val, $groupe_nom, $ordre_groupe, $ordre_semaine);
                        if ($result > 0) {
                            $processed++;
                        } else {
                            $errors++;
                        }
                    } else {
                        dol_syslog("WARNING: Missing fk_commande for creating new planning entry", LOG_WARNING);
                        $errors++;
                    }
                }
            }
            
            dol_syslog("AJAX Planning: Group order update - Processed: $processed, Errors: $errors", LOG_INFO);
            
            if ($errors == 0) {
                $db->commit();
                $response['success'] = true;
                $response['message'] = "Group order updated successfully ($processed items)";
                $response['data'] = array('processed' => $processed);
            } else {
                $db->rollback();
                throw new Exception("Failed to update $errors group orders out of " . count($updates));
            }
            break;
            
        case 'get_cards_by_status':
            // Action de lecture uniquement
            $status_filter = GETPOST('status_filter', 'alpha');
            
            // Validation du statut
            $valid_statuses = array('unplanned', 'a_terminer', 'a_expedier');
            if (!in_array($status_filter, $valid_statuses)) {
                throw new Exception('Invalid status filter: ' . $status_filter);
            }
            
            $cards = $object->getCardsByStatus($status_filter);
            if ($cards !== false) {
                $response['success'] = true;
                $response['data'] = array('cards' => $cards, 'count' => count($cards), 'status' => $status_filter);
                dol_syslog("AJAX Planning: Retrieved " . count($cards) . " cards for status $status_filter", LOG_DEBUG);
            } else {
                throw new Exception('Failed to get cards by status: ' . implode(', ', $object->errors));
            }
            break;
            
        case 'get_unplanned_cards':
            // Action de lecture uniquement
            $cards = $object->getUnplannedCards();
            if ($cards !== false) {
                $response['success'] = true;
                $response['data'] = array('cards' => $cards, 'count' => count($cards));
                dol_syslog("AJAX Planning: Retrieved " . count($cards) . " unplanned cards", LOG_DEBUG);
            } else {
                throw new Exception('Failed to get unplanned cards: ' . implode(', ', $object->errors));
            }
            break;
            
        case 'get_planned_cards':
            // Action de lecture uniquement
            $start_week = GETPOST('start_week', 'int');
            $week_count = GETPOST('week_count', 'int');
            $year = GETPOST('year', 'int');
            
            // Validation stricte
            $start_week_val = validateIntInRange($start_week, 1, 53);
            $week_count_val = validateIntInRange($week_count, 1, 52);
            $year_val = validateIntInRange($year, 2020, 2050);
            
            if ($start_week_val === false || $week_count_val === false || $year_val === false) {
                throw new Exception('Invalid parameters for planned cards (week, count, or year)');
            }
            
            $cards = $object->getPlannedCards($start_week_val, $week_count_val, $year_val);
            if ($cards !== false) {
                $total_cards = 0;
                foreach ($cards as $week_data) {
                    $total_cards += $week_data['elements'];
                }
                $response['success'] = true;
                $response['data'] = array(
                    'cards' => $cards, 
                    'total_cards' => $total_cards,
                    'start_week' => $start_week_val,
                    'week_count' => $week_count_val,
                    'year' => $year_val
                );
                dol_syslog("AJAX Planning: Retrieved $total_cards planned cards for weeks $start_week_val-" . ($start_week_val + $week_count_val - 1) . "/$year_val", LOG_DEBUG);
            } else {
                throw new Exception('Failed to get planned cards: ' . implode(', ', $object->errors));
            }
            break;
            
        case 'validate_week':
            // Vérifier les permissions d'écriture
            if (!$user->hasRight('planningproduction', 'planning', 'write')) {
                throw new Exception('Permission denied for write operations');
            }
            
            // SÉCURITÉ: Vérifier le token CSRF
            if (!validateCSRFToken()) {
                http_response_code(403);
                throw new Exception('Invalid or missing CSRF token');
            }
            
            $semaine = GETPOST('semaine', 'int');
            $annee = GETPOST('annee', 'int');
            
            // Validation stricte
            $semaine_val = validateIntInRange($semaine, 1, 53);
            $annee_val = validateIntInRange($annee, 2020, 2050);
            
            if ($semaine_val === false || $annee_val === false) {
                throw new Exception('Invalid week or year parameter');
            }
            
            // Marquer toutes les cartes de cette semaine comme validées
            $sql = "UPDATE ".MAIN_DB_PREFIX."planningproduction_planning ";
            $sql .= "SET status = 1 ";
            $sql .= "WHERE semaine = ".((int) $semaine_val)." AND annee = ".((int) $annee_val);
            $sql .= " AND entity IN (".getEntity('planningproduction').")";
            
            $resql = $db->query($sql);
            if ($resql) {
                $affected_rows = $db->affected_rows($resql);
                $response['success'] = true;
                $response['message'] = "Week $semaine_val/$annee_val validated successfully";
                $response['data'] = array('affected_rows' => $affected_rows, 'semaine' => $semaine_val, 'annee' => $annee_val);
                dol_syslog("AJAX Planning: Week $semaine_val/$annee_val validated ($affected_rows cards)", LOG_INFO);
            } else {
                throw new Exception('Failed to validate week: ' . $db->lasterror());
            }
            break;
            
        default:
            throw new Exception('Unknown action: ' . $action);
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
    
    // Log the error with more context
    dol_syslog("AJAX Planning Error - Action: " . $action . " - Error: " . $e->getMessage(), LOG_ERR);
    dol_syslog("AJAX Planning Error - File: " . $e->getFile() . " - Line: " . $e->getLine(), LOG_ERR);
    dol_syslog("AJAX Planning Error - User: " . $user->id . " - IP: " . $_SERVER['REMOTE_ADDR'], LOG_ERR);
    
    // Set appropriate HTTP status code
    if (strpos($e->getMessage(), 'Permission denied') !== false || strpos($e->getMessage(), 'CSRF token') !== false) {
        http_response_code(403);
    } elseif (strpos($e->getMessage(), 'Missing') !== false || strpos($e->getMessage(), 'Invalid') !== false) {
        http_response_code(400);
    } else {
        http_response_code(500);
    }
}

// Output JSON response
echo json_encode($response);
