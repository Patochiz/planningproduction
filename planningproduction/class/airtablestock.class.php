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
 * \file    class/airtablestock.class.php
 * \ingroup planningproduction
 * \brief   Lecture (seule) de la table Bobines d'Airtable pour comparer la surface restante au Stock Dolibarr.
 *
 * Aucune écriture n'est faite, ni dans Airtable ni dans Dolibarr (hors fichier de cache).
 * Le token n'est jamais renvoyé au navigateur, ni écrit dans les logs ou dans le cache.
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';

/**
 * Classe AirtableStock
 */
class AirtableStock
{
    /** URL de l'API Airtable */
    const API_URL = 'https://api.airtable.com/v0/';

    /** Base « Stock Bobine » et table « Bobines » par défaut */
    const DEFAULT_BASE = 'appLzWKIGDJrIoIBU';
    const DEFAULT_TABLE = 'tbll9m2YfOdwOCxxa';

    /** Champs de la table Bobines */
    const FIELD_FOURNISSEUR = 'fldIAkoFSsNkz2rwU';
    const FIELD_LARGEUR = 'flduA9iBzQnYvSq8H';
    const FIELD_COULEUR = 'fldna1MyP2IkYsYkz';
    const FIELD_PERFORATION = 'fld4UkR3uXW44NGAx';
    const FIELD_SURFACE = 'fld6ejSfOaQYHe4nw';
    const FIELD_LAST_MODIFIED = 'fldglxuG2PSTVLAS2';
    const FIELD_NUMERO = 'fldf41sYgtiRxUzXL';

    /** Nombre maximal de pages lues (sécurité contre une boucle infinie sur offset) */
    const MAX_PAGES = 50;

    /**
     * @var DoliDB Base de données
     */
    public $db;

    /**
     * @var string Dernière erreur
     */
    public $error = '';

    /**
     * @var int Nombre d'appels HTTP faits à Airtable pendant cette requête (diagnostic)
     */
    public $api_calls = 0;

    /**
     * @var array|null Données chargées (schéma + bobines), mémorisées pour la durée de la requête PHP
     */
    private $data = null;

    /**
     * @var array Informations sur le dernier chargement (fetched_at, from_cache, stale, error, status)
     */
    private $loadInfo = array();

    /**
     * Constructeur
     *
     * @param DoliDB $db Base de données
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Les 4 champs du filtre, dans l'ordre d'affichage
     *
     * @return array ['fldXXX' => 'Libellé', …]
     */
    public static function getFilterFields()
    {
        return array(
            self::FIELD_FOURNISSEUR => 'Fournisseur',
            self::FIELD_LARGEUR => 'Largeur',
            self::FIELD_COULEUR => 'Couleur',
            self::FIELD_PERFORATION => 'Perforation',
        );
    }

    /**
     * Valider et normaliser un filtre
     *
     * @param mixed $filtre ['fldXXX' => ['selA', …], …]
     * @return array|false Filtre nettoyé (champs sans sélection retirés), ou false si une clé ou une valeur est invalide
     */
    public static function sanitizeFilter($filtre)
    {
        if (!is_array($filtre)) {
            return false;
        }
        $fields = self::getFilterFields();
        $clean = array();
        foreach ($filtre as $field_id => $choice_ids) {
            if (!is_string($field_id) || !isset($fields[$field_id])) {
                return false;
            }
            if ($choice_ids === null || $choice_ids === '') {
                continue;
            }
            if (!is_array($choice_ids)) {
                return false;
            }
            $ids = array();
            foreach ($choice_ids as $choice_id) {
                if (!is_string($choice_id) || !preg_match('/^sel[A-Za-z0-9]{14}$/', $choice_id)) {
                    return false;
                }
                $ids[$choice_id] = $choice_id;
            }
            if (!empty($ids)) {
                $clean[$field_id] = array_values($ids);
            }
        }
        // Ordre stable des champs
        $ordered = array();
        foreach ($fields as $field_id => $label) {
            if (isset($clean[$field_id])) {
                $ordered[$field_id] = $clean[$field_id];
            }
        }
        return $ordered;
    }

    /**
     * Token Airtable déchiffré (usage serveur uniquement)
     *
     * @return string
     */
    private function getToken()
    {
        $value = getDolGlobalString('PLANNINGPRODUCTION_AIRTABLE_TOKEN');
        // dolDecrypt() ne fait rien sur une valeur non chiffrée ; deux passes au cas où la valeur aurait été chiffrée deux fois
        for ($i = 0; $i < 2 && strpos($value, 'dolcrypt:') === 0; $i++) {
            $value = dolDecrypt($value);
        }
        return trim((string) $value);
    }

    /**
     * Le token est-il configuré ?
     *
     * @return bool
     */
    public function isConfigured()
    {
        return $this->getToken() !== '';
    }

    /**
     * ID de la base Airtable
     *
     * @return string
     */
    public function getBaseId()
    {
        $base = getDolGlobalString('PLANNINGPRODUCTION_AIRTABLE_BASE', self::DEFAULT_BASE);
        return preg_match('/^app[A-Za-z0-9]{14}$/', $base) ? $base : self::DEFAULT_BASE;
    }

    /**
     * ID de la table des bobines
     *
     * @return string
     */
    public function getTableId()
    {
        $table = getDolGlobalString('PLANNINGPRODUCTION_AIRTABLE_TABLE', self::DEFAULT_TABLE);
        return preg_match('/^tbl[A-Za-z0-9]{14}$/', $table) ? $table : self::DEFAULT_TABLE;
    }

    /**
     * Écart toléré avant signalement
     *
     * @return float
     */
    public static function getTolerance()
    {
        $tol = str_replace(',', '.', getDolGlobalString('PLANNINGPRODUCTION_AIRTABLE_TOLERANCE', '0.5'));
        return is_numeric($tol) ? max(0, (float) $tol) : 0.5;
    }

    /**
     * Durée de vie du cache en secondes
     *
     * @return int
     */
    public static function getCacheTtl()
    {
        $ttl = getDolGlobalString('PLANNINGPRODUCTION_AIRTABLE_CACHE_TTL', '300');
        return is_numeric($ttl) ? max(0, (int) $ttl) : 300;
    }

    /**
     * Décode le lien d'interface Airtable d'une matière (pré-remplissage uniquement, §3.2).
     *
     * Le format n'est pas documenté par Airtable : en cas d'échec, renvoie false sans lever d'exception.
     *
     * @param string $url Lien de la page d'interface filtrée
     * @return array|false  ['fldXXX' => ['selA','selB'], …] limité aux 4 champs du filtre, ou false
     */
    public function parseInterfaceLink($url)
    {
        if (!is_string($url) || $url === '') {
            return false;
        }
        $query = parse_url($url, PHP_URL_QUERY);
        if (!is_string($query) || $query === '') {
            return false;
        }

        // Chercher le paramètre dont la valeur commence par « b: » (son nom varie d'une page à l'autre)
        $payload = null;
        foreach (explode('&', $query) as $pair) {
            $parts = explode('=', $pair, 2);
            if (count($parts) != 2) {
                continue;
            }
            // rawurldecode conserve les « + » du base64 standard
            $value = rawurldecode($parts[1]);
            if (strpos($value, 'b:') === 0) {
                $payload = substr($value, 2);
                break;
            }
        }
        if ($payload === null || $payload === '') {
            return false;
        }

        // Base64 URL-safe et padding manquant
        $b64 = strtr(trim($payload), '-_ ', '+/+');
        $pad = strlen($b64) % 4;
        if ($pad) {
            $b64 .= str_repeat('=', 4 - $pad);
        }
        $json = base64_decode($b64, true);
        if ($json === false) {
            return false;
        }
        $conditions = json_decode($json, true);
        if (!is_array($conditions)) {
            return false;
        }

        // Clés = 5 derniers caractères de l'ID de champ ; conditions [0, "sel…"] uniquement
        $filtre = array();
        foreach (self::getFilterFields() as $field_id => $label) {
            $suffix = substr($field_id, -5);
            if (!isset($conditions[$suffix]) || !is_array($conditions[$suffix])) {
                continue;
            }
            $ids = array();
            foreach ($conditions[$suffix] as $cond) {
                if (!is_array($cond) || !isset($cond[0]) || $cond[0] !== 0 || !array_key_exists(1, $cond)) {
                    continue;
                }
                $values = is_array($cond[1]) ? $cond[1] : array($cond[1]);
                foreach ($values as $v) {
                    if (is_string($v) && preg_match('/^sel[A-Za-z0-9]{14}$/', $v)) {
                        $ids[$v] = $v;
                    }
                }
            }
            if (!empty($ids)) {
                $filtre[$field_id] = array_values($ids);
            }
        }

        return empty($filtre) ? false : $filtre;
    }

    /**
     * Choix disponibles pour les 4 champs (formulaire de configuration).
     *
     * @param bool $force Ignorer le cache
     * @return array|false  ['fldXXX' => ['label' => 'Fournisseur', 'choices' => [['id' => 'sel…', 'name' => 'TATA'], …]], …]
     */
    public function getFilterChoices($force = false)
    {
        if (!$this->loadData($force)) {
            return false;
        }
        $result = array();
        foreach (self::getFilterFields() as $field_id => $label) {
            $result[$field_id] = array(
                'label' => $label,
                'choices' => isset($this->data['schema'][$field_id]) ? $this->data['schema'][$field_id] : array(),
            );
        }
        return $result;
    }

    /**
     * Résumé lisible d'un filtre, par exemple « TPS · 400 · BLANC · 11%1,5mm »
     *
     * @param array      $filtre  Filtre ['fldXXX' => ['selA', …]]
     * @param array|null $choices Sortie de getFilterChoices() (null si indisponible)
     * @return array ['text' => string, 'invalid' => bool, 'message' => string]
     */
    public function describeFilter($filtre, $choices)
    {
        $filtre = self::sanitizeFilter($filtre);
        if (empty($filtre)) {
            return array('text' => '', 'invalid' => false, 'message' => '');
        }
        if (empty($choices)) {
            $nb = 0;
            foreach ($filtre as $ids) {
                $nb += count($ids);
            }
            return array('text' => $nb.' choix (schéma Airtable indisponible)', 'invalid' => false, 'message' => '');
        }
        $parts = array();
        $missing = array();
        foreach ($filtre as $field_id => $ids) {
            $names = array();
            foreach ($ids as $id) {
                $name = $this->findChoiceName($choices[$field_id]['choices'], $id);
                if ($name === null) {
                    $missing[] = $choices[$field_id]['label'];
                    $names[] = '?';
                } else {
                    $names[] = $name;
                }
            }
            $parts[] = implode(' / ', $names);
        }
        $message = '';
        if (!empty($missing)) {
            $message = 'Choix supprimé dans Airtable : '.implode(', ', array_unique($missing));
        }
        return array('text' => implode(' · ', $parts), 'invalid' => !empty($missing), 'message' => $message);
    }

    /**
     * Applique un filtre aux bobines (aperçu en configuration et calcul).
     *
     * @param array $filtre Filtre ['fldXXX' => ['selA', …]]
     * @param bool  $force  Ignorer le cache
     * @return array  ['status' => …, 'surface' => float|null, 'nb_bobines' => int, 'last_modified' => …, 'message' => …]
     */
    public function computeForFilter(array $filtre, $force = false)
    {
        $result = array('status' => 'ok', 'surface' => null, 'nb_bobines' => 0, 'last_modified' => null, 'message' => '');

        $filtre = self::sanitizeFilter($filtre);
        if ($filtre === false) {
            $result['status'] = 'invalid_filter';
            $result['message'] = 'Filtre Airtable mal formé';
            return $result;
        }
        if (empty($filtre)) {
            // Jamais de comparaison sans filtre : on additionnerait toutes les bobines
            $result['status'] = 'no_filter';
            $result['message'] = 'Filtre Airtable non configuré';
            return $result;
        }

        if (!$this->loadData($force)) {
            $result['status'] = $this->loadInfo['status'];
            $result['message'] = $this->loadInfo['error'];
            return $result;
        }

        // Traduire les IDs de choix en noms (l'API des enregistrements renvoie les noms)
        $fields = self::getFilterFields();
        $wanted = array();
        foreach ($filtre as $field_id => $ids) {
            $names = array();
            foreach ($ids as $id) {
                $name = $this->findChoiceName(isset($this->data['schema'][$field_id]) ? $this->data['schema'][$field_id] : array(), $id);
                if ($name === null) {
                    $result['status'] = 'invalid_filter';
                    $result['message'] = 'Choix supprimé dans Airtable pour le champ '.$fields[$field_id].' ('.$id.')';
                    return $result;
                }
                $names[$name] = true;
            }
            $wanted[$field_id] = $names;
        }

        $surface = 0.0;
        $nb = 0;
        $last = null;
        foreach ($this->data['bobines'] as $bobine) {
            $f = $bobine['fields'];
            $match = true;
            foreach ($wanted as $field_id => $names) {
                $value = isset($f[$field_id]) ? $f[$field_id] : null;
                if (!is_string($value) || !isset($names[$value])) {
                    $match = false;
                    break;
                }
            }
            if (!$match) {
                continue;
            }
            $surface += isset($f[self::FIELD_SURFACE]) ? (float) $f[self::FIELD_SURFACE] : 0;
            $nb++;
            if (!empty($f[self::FIELD_LAST_MODIFIED]) && ($last === null || strtotime($f[self::FIELD_LAST_MODIFIED]) > strtotime($last))) {
                $last = $f[self::FIELD_LAST_MODIFIED];
            }
        }

        $result['surface'] = round($surface, 2);
        $result['nb_bobines'] = $nb;
        $result['last_modified'] = $last;
        $result['message'] = $this->loadInfo['stale'] ? $this->loadInfo['error'] : '';
        return $result;
    }

    /**
     * Calcule, pour chaque matière, la somme Airtable.
     *
     * Les bobines ne sont chargées qu'une fois ; le champ « lien » n'est jamais lu.
     *
     * @param array $matieres  sortie de PlanningProduction::getAllMatieres()
     * @param bool  $force     ignorer le cache
     * @return array  indexé par rowid de matière :
     *   ['status' => 'ok'|'no_filter'|'invalid_filter'|'not_configured'|'api_error',
     *    'surface' => float|null, 'nb_bobines' => int,
     *    'last_modified' => 'ISO8601'|null, 'message' => string]
     */
    public function getAirtableTotals($matieres, $force = false)
    {
        $totals = array();
        if (!is_array($matieres)) {
            return $totals;
        }

        $configured = $this->isConfigured();
        $loaded = false;
        foreach ($matieres as $matiere) {
            $rowid = (int) $matiere['rowid'];
            $filtre = isset($matiere['airtable_filtre']) && is_array($matiere['airtable_filtre']) ? $matiere['airtable_filtre'] : array();

            if (!$configured) {
                $totals[$rowid] = array('status' => 'not_configured', 'surface' => null, 'nb_bobines' => 0, 'last_modified' => null, 'message' => 'Airtable non configuré');
                continue;
            }
            // Le forçage ne s'applique qu'au premier chargement : un seul appel par calcul
            $totals[$rowid] = $this->computeForFilter($filtre, $force && !$loaded);
            if (!empty($filtre)) {
                $loaded = true;
            }
        }
        if (!$configured) {
            $this->loadInfo = array('status' => 'not_configured', 'error' => 'Airtable non configuré', 'fetched_at' => null, 'from_cache' => false, 'stale' => false);
        }
        return $totals;
    }

    /**
     * Informations sur le dernier chargement des données
     *
     * @return array ['status' => …, 'error' => string|null, 'fetched_at' => int|null, 'from_cache' => bool, 'stale' => bool, 'nb_bobines' => int]
     */
    public function getLoadInfo()
    {
        $info = array_merge(array('status' => null, 'error' => '', 'fetched_at' => null, 'from_cache' => false, 'stale' => false), $this->loadInfo);
        $info['nb_bobines'] = ($this->data !== null) ? count($this->data['bobines']) : 0;
        return $info;
    }

    /**
     * Supprimer le fichier de cache (après changement de configuration)
     *
     * @return void
     */
    public function clearCache()
    {
        $file = $this->getCacheFile();
        if (file_exists($file)) {
            @unlink($file);
        }
        $this->data = null;
        $this->loadInfo = array();
    }

    // ========== MÉTHODES PRIVÉES ==========

    /**
     * Trouver le nom d'un choix à partir de son ID
     *
     * @param array  $choices [['id' => 'sel…', 'name' => '…'], …]
     * @param string $id      ID de choix
     * @return string|null
     */
    private function findChoiceName($choices, $id)
    {
        foreach ((array) $choices as $choice) {
            if ($choice['id'] === $id) {
                return $choice['name'];
            }
        }
        return null;
    }

    /**
     * Charger schéma + bobines (mémoire, puis cache fichier, puis API)
     *
     * En cas d'erreur de l'API, un cache existant, même expiré, est utilisé.
     *
     * @param bool $force Ignorer le cache
     * @return bool true si des données sont disponibles
     */
    private function loadData($force = false)
    {
        if ($this->data !== null && !$force) {
            return true;
        }

        if (!$this->isConfigured()) {
            $this->loadInfo = array('status' => 'not_configured', 'error' => 'Airtable non configuré', 'fetched_at' => null, 'from_cache' => false, 'stale' => false);
            return false;
        }

        $baseId = $this->getBaseId();
        $tableId = $this->getTableId();
        $cache = $this->readCache($baseId, $tableId);

        // Cache valide
        if (!$force && $cache !== null && (dol_now() - $cache['fetched_at']) < self::getCacheTtl()) {
            dol_syslog(get_class($this)."::loadData - données lues depuis le cache", LOG_DEBUG);
            $this->data = $cache;
            $this->loadInfo = array('status' => 'ok', 'error' => '', 'fetched_at' => $cache['fetched_at'], 'from_cache' => true, 'stale' => false);
            return true;
        }

        // Appel à Airtable
        dol_syslog(get_class($this)."::loadData - lecture Airtable (force=".($force ? 1 : 0).")", LOG_INFO);
        $schema = $this->fetchSchema($baseId);
        $bobines = ($schema !== false) ? $this->fetchAllBobines($baseId) : false;

        if ($schema !== false && $bobines !== false) {
            $this->data = array(
                'fetched_at' => dol_now(),
                'base' => $baseId,
                'table' => $tableId,
                'schema' => $schema,
                'bobines' => $bobines,
            );
            $this->writeCache($this->data);
            $this->loadInfo = array('status' => 'ok', 'error' => '', 'fetched_at' => $this->data['fetched_at'], 'from_cache' => false, 'stale' => false);
            return true;
        }

        $error = $this->error ? $this->error : 'Erreur Airtable';
        dol_syslog(get_class($this)."::loadData - ".$error, LOG_WARNING);

        // Repli sur le cache expiré
        if ($cache !== null) {
            $this->data = $cache;
            $this->loadInfo = array(
                'status' => 'ok',
                'error' => $error.' – données du '.dol_print_date($cache['fetched_at'], '%d/%m %H:%M', 'tzuser'),
                'fetched_at' => $cache['fetched_at'],
                'from_cache' => true,
                'stale' => true,
            );
            return true;
        }

        $this->loadInfo = array('status' => 'api_error', 'error' => $error, 'fetched_at' => null, 'from_cache' => false, 'stale' => false);
        return false;
    }

    /**
     * Appel GET à l'API Airtable
     *
     * @param string $path  Chemin après /v0/ (ex. « meta/bases/appXXX/tables »)
     * @param array  $query Paramètres sous forme de paires [[clé, valeur], …] (clés répétables, ex. fields[])
     * @return array|false  Réponse JSON décodée, ou false (message dans $this->error)
     */
    private function apiGet($path, $query = array())
    {
        $token = $this->getToken();
        if ($token === '') {
            $this->error = 'Airtable non configuré';
            return false;
        }

        $parts = array();
        foreach ($query as $pair) {
            $parts[] = rawurlencode($pair[0]).'='.rawurlencode((string) $pair[1]);
        }
        $url = self::API_URL.$path.(count($parts) ? '?'.implode('&', $parts) : '');
        $headers = array('Authorization: Bearer '.$token, 'Accept: application/json');

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $this->api_calls++;
            // Le token est dans l'en-tête, jamais dans l'URL (getURLContent journalise l'URL)
            dol_syslog(get_class($this)."::apiGet ".$path." (appel ".$this->api_calls.")", LOG_DEBUG);
            $rep = getURLContent($url, 'GET', '', 1, $headers, array('https'), 0);
            $code = isset($rep['http_code']) ? (int) $rep['http_code'] : 0;

            // Limite de débit : attendre 1 s et réessayer une seule fois
            if ($code == 429 && $attempt == 1) {
                dol_syslog(get_class($this)."::apiGet HTTP 429, nouvel essai dans 1 s", LOG_WARNING);
                sleep(1);
                continue;
            }
            break;
        }

        if ($code == 200) {
            $json = json_decode($rep['content'], true);
            if (!is_array($json)) {
                $this->error = 'Réponse Airtable invalide';
                return false;
            }
            return $json;
        }

        if ($code == 0) {
            $this->error = 'Airtable injoignable'.(!empty($rep['curl_error_msg']) ? ' ('.dol_trunc($rep['curl_error_msg'], 80).')' : '');
        } elseif ($code == 401) {
            $this->error = 'Token Airtable invalide (HTTP 401)';
        } elseif ($code == 403) {
            $this->error = 'Accès refusé par Airtable (HTTP 403) : vérifier les droits du token';
        } elseif ($code == 404) {
            $this->error = 'Base ou table Airtable introuvable (HTTP 404)';
        } elseif ($code == 429) {
            $this->error = 'Limite de débit Airtable atteinte (HTTP 429)';
        } else {
            $this->error = 'Erreur Airtable (HTTP '.$code.')';
        }
        return false;
    }

    /**
     * Lire le schéma de la table des bobines : choix des 4 champs du filtre
     *
     * @param string $baseId ID de la base
     * @return array|false ['fldXXX' => [['id' => 'sel…', 'name' => '…'], …], …]
     */
    private function fetchSchema($baseId)
    {
        $json = $this->apiGet('meta/bases/'.$baseId.'/tables');
        if ($json === false) {
            return false;
        }
        $tableId = $this->getTableId();
        $table = null;
        foreach ((isset($json['tables']) ? $json['tables'] : array()) as $t) {
            if (isset($t['id']) && $t['id'] === $tableId) {
                $table = $t;
                break;
            }
        }
        if ($table === null) {
            $this->error = 'Table '.$tableId.' introuvable dans la base Airtable';
            return false;
        }

        $schema = array();
        $filterFields = self::getFilterFields();
        foreach ($filterFields as $field_id => $label) {
            $schema[$field_id] = array();
        }
        foreach ((isset($table['fields']) ? $table['fields'] : array()) as $field) {
            if (!isset($field['id']) || !isset($filterFields[$field['id']])) {
                continue;
            }
            $choices = isset($field['options']['choices']) ? $field['options']['choices'] : array();
            foreach ($choices as $choice) {
                if (!isset($choice['id']) || !isset($choice['name'])) {
                    continue;
                }
                // Le nom est conservé tel quel pour la comparaison avec les enregistrements
                $schema[$field['id']][] = array('id' => (string) $choice['id'], 'name' => (string) $choice['name']);
            }
        }
        return $schema;
    }

    /**
     * Lire toutes les bobines dont la surface restante est > 0 (avec pagination)
     *
     * @param string $baseId ID de la base
     * @return array|false [['id' => 'rec…', 'fields' => ['fldXXX' => valeur, …]], …]
     */
    private function fetchAllBobines($baseId)
    {
        $tableId = $this->getTableId();
        // Taille de page réglable pour tester la pagination en développement (ex. 2)
        $pageSize = getDolGlobalInt('PLANNINGPRODUCTION_AIRTABLE_PAGESIZE', 100);
        if ($pageSize < 1 || $pageSize > 100) {
            $pageSize = 100;
        }
        $fields = array(
            self::FIELD_FOURNISSEUR, self::FIELD_LARGEUR, self::FIELD_COULEUR, self::FIELD_PERFORATION,
            self::FIELD_SURFACE, self::FIELD_LAST_MODIFIED, self::FIELD_NUMERO,
        );

        $bobines = array();
        $offset = null;
        $page = 0;
        do {
            $query = array(
                array('returnFieldsByFieldId', 'true'),
                array('pageSize', $pageSize),
                array('filterByFormula', '{'.self::FIELD_SURFACE.'}>0'),
            );
            foreach ($fields as $f) {
                $query[] = array('fields[]', $f);
            }
            if ($offset !== null) {
                $query[] = array('offset', $offset);
            }

            $json = $this->apiGet($baseId.'/'.$tableId, $query);
            if ($json === false) {
                return false;
            }
            foreach ((isset($json['records']) ? $json['records'] : array()) as $record) {
                $recFields = isset($record['fields']) && is_array($record['fields']) ? $record['fields'] : array();
                $keep = array();
                foreach ($fields as $f) {
                    if (isset($recFields[$f]) && (is_scalar($recFields[$f]))) {
                        $keep[$f] = $recFields[$f];
                    }
                }
                $bobines[] = array('id' => isset($record['id']) ? (string) $record['id'] : '', 'fields' => $keep);
            }
            $offset = (isset($json['offset']) && is_string($json['offset']) && $json['offset'] !== '') ? $json['offset'] : null;
            $page++;
        } while ($offset !== null && $page < self::MAX_PAGES);

        if ($offset !== null) {
            dol_syslog(get_class($this)."::fetchAllBobines - limite de ".self::MAX_PAGES." pages atteinte", LOG_WARNING);
        }
        dol_syslog(get_class($this)."::fetchAllBobines - ".count($bobines)." bobines lues en ".$page." page(s)", LOG_DEBUG);
        return $bobines;
    }

    /**
     * Chemin du fichier de cache
     *
     * @return string
     */
    private function getCacheFile()
    {
        global $conf;
        $suffix = (!empty($conf->entity) && $conf->entity > 1) ? '_'.((int) $conf->entity) : '';
        return DOL_DATA_ROOT.'/planningproduction/temp/airtable_cache'.$suffix.'.json';
    }

    /**
     * Lire le cache (même expiré) s'il correspond à la base et à la table configurées
     *
     * @param string $baseId  ID de la base
     * @param string $tableId ID de la table
     * @return array|null
     */
    private function readCache($baseId, $tableId)
    {
        $file = $this->getCacheFile();
        if (!is_readable($file)) {
            return null;
        }
        $data = json_decode((string) @file_get_contents($file), true);
        if (!is_array($data) || empty($data['fetched_at']) || !isset($data['schema']) || !isset($data['bobines'])
            || !isset($data['base']) || $data['base'] !== $baseId || !isset($data['table']) || $data['table'] !== $tableId) {
            return null;
        }
        $data['fetched_at'] = (int) $data['fetched_at'];
        return $data;
    }

    /**
     * Écrire le cache (schéma et bobines brutes, horodatés ; jamais le token)
     *
     * @param array $data Données à mettre en cache
     * @return void
     */
    private function writeCache($data)
    {
        $file = $this->getCacheFile();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            dol_mkdir($dir);
        }
        if (@file_put_contents($file, json_encode($data), LOCK_EX) === false) {
            dol_syslog(get_class($this)."::writeCache - écriture impossible dans ".$dir, LOG_WARNING);
        }
    }
}
