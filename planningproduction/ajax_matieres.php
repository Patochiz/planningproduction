<?php
/* Copyright (C) 2024 Patrick Delcroix - VERSION SÉCURISÉE avec gestion CDE EN COURS à date */

// Initialize $res variable
$res = 0;

// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
dol_include_once('/planningproduction/class/planningproduction.class.php');

// Security check
if (!$user->hasRight('planningproduction', 'planning', 'read')) {
    accessforbidden();
    exit;
}

$action = GETPOST('action', 'alpha');
top_httphead('application/json');

$response = array('success' => false, 'message' => '', 'data' => null, 'error_code' => null);

/**
 * Valider le token CSRF de manière stricte
 */
function validateStrictCSRFToken() {
    $token = GETPOST('token', 'alpha');
    if (empty($token) || strlen($token) < 20) return false;
    if (!preg_match('/^[a-zA-Z0-9]+$/', $token)) return false;
    return true;
}

/**
 * Valider un code MP
 */
function validateCodeMP($code_mp) {
    $code_mp = trim($code_mp);
    if (empty($code_mp) || strlen($code_mp) > 50) return false;
    if (preg_match('/[<>"\']/', $code_mp)) return false;
    return $code_mp;
}

/**
 * Valider une valeur de stock ou de commandes
 */
function validateStock($stock) {
    $stock = str_replace(',', '.', trim($stock));
    if (!is_numeric($stock)) return false;
    $stock_value = floatval($stock);
    if ($stock_value < 0 || $stock_value > 1000000) return false;
    return $stock_value;
}

try {
    $planning = new PlanningProduction($db);

    switch($action) {
        case 'get_matieres':
            // Récupérer toutes les matières premières
            $matieres = $planning->getAllMatieres(true);
            
            if ($matieres !== false) {
                // Calculer les commandes en cours pour chaque matière
                foreach ($matieres as &$matiere) {
                    $matiere['cde_en_cours'] = $planning->calculateCdeEnCours($matiere['code_mp']);
                    // Le reste est maintenant calculé avec cde_en_cours_date dans la classe
                    $matiere['reste'] = $matiere['stock'] - $matiere['cde_en_cours_date'];
                    
                    // Ajouter un flag de désynchronisation si les deux valeurs diffèrent
                    $matiere['is_desync'] = abs($matiere['cde_en_cours'] - $matiere['cde_en_cours_date']) > 0.01;
                }
                
                $response['success'] = true;
                $response['data'] = $matieres;
                $response['message'] = count($matieres) . ' matière(s) chargée(s)';
            } else {
                throw new Exception('Erreur lors de la récupération des matières premières');
            }
            break;

        case 'update_stock':
            // Vérifier les droits d'écriture
            if (!$user->hasRight('planningproduction', 'planning', 'write')) {
                throw new Exception('Droits insuffisants pour modifier le stock');
            }
            
            // Vérifier le token CSRF
            if (!validateStrictCSRFToken()) {
                http_response_code(403);
                throw new Exception('Token CSRF invalide ou manquant');
            }
            
            // Récupérer et valider les paramètres
            $rowid = GETPOST('rowid', 'int');
            $stock_value = validateStock(GETPOST('stock', 'alpha'));
            
            if (!$rowid || $rowid <= 0) {
                throw new Exception('ID de ligne invalide');
            }
            
            if ($stock_value === false) {
                throw new Exception('Valeur de stock invalide');
            }
            
            // Mettre à jour le stock
            $result = $planning->updateMatiereStock($rowid, $stock_value);
            
            if ($result > 0) {
                $response['success'] = true;
                $response['message'] = 'Stock mis à jour avec succès';
                $response['data'] = array('stock' => $stock_value);
            } else {
                throw new Exception('Erreur lors de la mise à jour du stock');
            }
            break;

        case 'update_cde_en_cours_date':
            // Vérifier les droits d'écriture
            if (!$user->hasRight('planningproduction', 'planning', 'write')) {
                throw new Exception('Droits insuffisants pour modifier les commandes en cours à date');
            }
            
            // Vérifier le token CSRF
            if (!validateStrictCSRFToken()) {
                http_response_code(403);
                throw new Exception('Token CSRF invalide ou manquant');
            }
            
            // Récupérer et valider les paramètres
            $rowid = GETPOST('rowid', 'int');
            $cde_en_cours_date_value = validateStock(GETPOST('cde_en_cours_date', 'alpha'));
            
            if (!$rowid || $rowid <= 0) {
                throw new Exception('ID de ligne invalide');
            }
            
            if ($cde_en_cours_date_value === false) {
                throw new Exception('Valeur de commandes en cours à date invalide');
            }
            
            // Mettre à jour les commandes en cours à date
            $result = $planning->updateMatiereCdeEnCoursDate($rowid, $cde_en_cours_date_value);
            
            if ($result > 0) {
                $response['success'] = true;
                $response['message'] = 'Commandes en cours à date mises à jour avec succès';
                $response['data'] = array('cde_en_cours_date' => $cde_en_cours_date_value);
            } else {
                throw new Exception('Erreur lors de la mise à jour des commandes en cours à date');
            }
            break;

        case 'sync_cde_en_cours':
            // Cette action copie la valeur de cde_en_cours calculée vers cde_en_cours_date
            // Utilisée par le bouton "MàJ"
            
            // Vérifier les droits d'écriture
            if (!$user->hasRight('planningproduction', 'planning', 'write')) {
                throw new Exception('Droits insuffisants');
            }
            
            $code_mp_valid = validateCodeMP(GETPOST('code_mp', 'alpha'));
            $rowid = GETPOST('rowid', 'int');
            
            if ($code_mp_valid === false) {
                throw new Exception('Code MP invalide');
            }
            
            if (!$rowid || $rowid <= 0) {
                throw new Exception('ID de ligne invalide');
            }
            
            // Calculer les commandes en cours
            $cde = $planning->calculateCdeEnCours($code_mp_valid);
            
            if ($cde !== false) {
                // Mettre à jour cde_en_cours_date avec la valeur calculée
                $result = $planning->updateMatiereCdeEnCoursDate($rowid, $cde);
                
                if ($result > 0) {
                    $response['success'] = true;
                    $response['data'] = array(
                        'cde_en_cours' => $cde,
                        'cde_en_cours_date' => $cde
                    );
                    $response['message'] = 'Synchronisation effectuée';
                } else {
                    throw new Exception('Erreur lors de la mise à jour');
                }
            } else {
                throw new Exception('Erreur lors du calcul des commandes en cours');
            }
            break;

        default:
            throw new Exception('Action inconnue: ' . $action);
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    dol_syslog("AJAX Matieres Error [action=$action]: " . $e->getMessage(), LOG_ERR);
}

// Renvoyer la réponse en JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
