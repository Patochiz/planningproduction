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
 * Vérifier le token CSRF contre le token de session Dolibarr (actions d'administration)
 */
function checkSessionCSRFToken() {
    $token = GETPOST('token', 'alpha');
    $session_token = isset($_SESSION['token']) ? (string) $_SESSION['token'] : '';
    return ($token !== '' && $session_token !== '' && hash_equals($session_token, $token));
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
                unset($matiere);

                // Comparaison avec Airtable (lecture seule) : une panne n'empêche jamais l'affichage
                $airtable_info = array('nb_ecarts' => 0, 'fetched_at' => null, 'from_cache' => false, 'error' => null, 'configured' => false, 'tolerance' => 0.5);
                try {
                    dol_include_once('/planningproduction/class/airtablestock.class.php');
                    $airtable = new AirtableStock($db);
                    $totals = $airtable->getAirtableTotals($matieres, GETPOSTINT('force_airtable') === 1);
                    $tolerance = AirtableStock::getTolerance();
                    $info = $airtable->getLoadInfo();

                    foreach ($matieres as &$matiere) {
                        $t = isset($totals[(int) $matiere['rowid']]) ? $totals[(int) $matiere['rowid']] : array('status' => 'api_error', 'surface' => null, 'nb_bobines' => 0, 'last_modified' => null, 'message' => 'Non calculé');
                        $matiere['airtable_status'] = $t['status'];
                        $matiere['airtable_surface'] = $t['surface'];
                        $matiere['airtable_nb_bobines'] = $t['nb_bobines'];
                        $matiere['airtable_last_modified'] = $t['last_modified'];
                        $matiere['airtable_message'] = $t['message'];
                        if ($t['status'] === 'ok') {
                            $matiere['airtable_ecart'] = round($matiere['stock'] - $t['surface'], 2);
                            $matiere['is_airtable_diff'] = abs($matiere['airtable_ecart']) > $tolerance;
                        } else {
                            $matiere['airtable_ecart'] = null;
                            $matiere['is_airtable_diff'] = false;
                        }
                        if ($matiere['is_airtable_diff']) {
                            $airtable_info['nb_ecarts']++;
                        }
                    }
                    unset($matiere);

                    $airtable_info['configured'] = $airtable->isConfigured();
                    $airtable_info['tolerance'] = $tolerance;
                    $airtable_info['fetched_at'] = !empty($info['fetched_at']) ? date('c', $info['fetched_at']) : null;
                    $airtable_info['from_cache'] = (bool) $info['from_cache'];
                    $airtable_info['error'] = !empty($info['error']) ? $info['error'] : null;
                } catch (Throwable $e) {
                    dol_syslog("AJAX Matieres - Airtable : ".$e->getMessage(), LOG_ERR);
                    $airtable_info['error'] = 'Erreur interne Airtable';
                    foreach ($matieres as &$matiere) {
                        $matiere['airtable_status'] = 'api_error';
                        $matiere['airtable_surface'] = null;
                        $matiere['airtable_nb_bobines'] = 0;
                        $matiere['airtable_last_modified'] = null;
                        $matiere['airtable_message'] = 'Erreur interne Airtable';
                        $matiere['airtable_ecart'] = null;
                        $matiere['is_airtable_diff'] = false;
                    }
                    unset($matiere);
                }

                $response['success'] = true;
                $response['airtable'] = $airtable_info;
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

        case 'preview_airtable_filtre':
            // Aperçu « → n bobines, X m² » d'un filtre en cours de saisie (configuration)
            if (empty($user->admin)) {
                http_response_code(403);
                throw new Exception('Réservé aux administrateurs');
            }
            if (!checkSessionCSRFToken()) {
                http_response_code(403);
                throw new Exception('Token CSRF invalide ou manquant');
            }
            dol_include_once('/planningproduction/class/airtablestock.class.php');
            $filtre = json_decode(GETPOST('filtre', 'none'), true);
            $filtre = AirtableStock::sanitizeFilter(is_array($filtre) ? $filtre : array());
            if ($filtre === false) {
                throw new Exception('Filtre invalide');
            }
            $airtable = new AirtableStock($db);
            $result = $airtable->computeForFilter($filtre, false);
            $response['success'] = true;
            $response['data'] = $result;
            break;

        case 'parse_airtable_link':
            // Pré-remplissage : décoder le lien d'interface (rien n'est enregistré)
            if (empty($user->admin)) {
                http_response_code(403);
                throw new Exception('Réservé aux administrateurs');
            }
            if (!checkSessionCSRFToken()) {
                http_response_code(403);
                throw new Exception('Token CSRF invalide ou manquant');
            }
            dol_include_once('/planningproduction/class/airtablestock.class.php');
            $airtable = new AirtableStock($db);
            $filtre = $airtable->parseInterfaceLink(GETPOST('lien', 'none'));
            if ($filtre === false) {
                $response['success'] = false;
                $response['message'] = 'Lien non décodable, sélection manuelle';
            } else {
                $response['success'] = true;
                $response['data'] = array('filtre' => $filtre);
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
