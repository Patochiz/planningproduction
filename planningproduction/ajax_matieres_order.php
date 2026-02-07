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
 * \file    ajax_matieres_order.php
 * \ingroup planningproduction
 * \brief   AJAX handler for materials order management.
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

// Seulement pour les requêtes AJAX
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(array('error' => 'Requête AJAX uniquement'));
    exit;
}

// Vérification du token CSRF
$token = GETPOST('token', 'alpha');
if (!$token || !checkToken($token)) {
    http_response_code(403);
    echo json_encode(array('error' => 'Token CSRF invalide'));
    exit;
}

// Vérification des permissions
if (!isModEnabled('planningproduction')) {
    http_response_code(403);
    echo json_encode(array('error' => 'Module non activé'));
    exit;
}

if (!$user->hasRight('planningproduction', 'planning', 'write')) {
    http_response_code(403);
    echo json_encode(array('error' => 'Permissions insuffisantes'));
    exit;
}

$action = GETPOST('action', 'alpha');
$response = array();

try {
    $planning = new PlanningProduction($db);
    
    switch ($action) {
        case 'reorder_matieres':
            // Récupérer l'ordre depuis les données POST
            $order_data = null;
            
            // Support FormData (multipart/form-data)
            if (isset($_POST['order'])) {
                if (is_array($_POST['order'])) {
                    $order_data = $_POST['order'];
                } else {
                    $order_data = json_decode($_POST['order'], true);
                }
            }
            // Support JSON (application/json)
            elseif ($input = file_get_contents('php://input')) {
                $json_data = json_decode($input, true);
                if (isset($json_data['order'])) {
                    $order_data = $json_data['order'];
                }
            }
            
            if (!$order_data || !is_array($order_data)) {
                throw new Exception('Données d\'ordre invalides');
            }
            
            // Valider que les IDs sont des entiers
            $ordered_ids = array();
            foreach ($order_data as $id) {
                $clean_id = intval($id);
                if ($clean_id <= 0) {
                    throw new Exception('ID invalide: ' . $id);
                }
                $ordered_ids[] = $clean_id;
            }
            
            if (empty($ordered_ids)) {
                throw new Exception('Aucun ID valide fourni');
            }
            
            // Log pour débogage
            dol_syslog("ajax_matieres_order: Reordering matières with IDs: " . implode(',', $ordered_ids), LOG_DEBUG);
            
            $result = $planning->reorderMatieres($ordered_ids);
            
            if ($result > 0) {
                $response = array(
                    'success' => true,
                    'message' => 'Ordre mis à jour avec succès'
                );
            } else {
                $errors = $planning->errors;
                throw new Exception('Erreur lors de la mise à jour de l\'ordre: ' . implode(', ', $errors));
            }
            break;
            
        case 'get_matieres_order':
            // Récupérer l'ordre actuel des matières premières
            $matieres = $planning->getAllMatieres(true);
            
            if ($matieres === false) {
                throw new Exception('Erreur lors de la récupération des matières premières');
            }
            
            $order_data = array();
            foreach ($matieres as $matiere) {
                $order_data[] = array(
                    'rowid' => $matiere['rowid'],
                    'code_mp' => $matiere['code_mp'],
                    'ordre' => $matiere['ordre']
                );
            }
            
            $response = array(
                'success' => true,
                'data' => $order_data
            );
            break;
            
        default:
            throw new Exception('Action non reconnue: ' . $action);
    }

} catch (Exception $e) {
    http_response_code(400);
    $response = array(
        'error' => $e->getMessage()
    );
    
    // Log l'erreur
    dol_syslog("ajax_matieres_order error: " . $e->getMessage(), LOG_ERR);
}

// Envoi de la réponse JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
