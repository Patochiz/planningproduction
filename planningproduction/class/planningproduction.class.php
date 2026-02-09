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
 * \file        class/planningproduction.class.php
 * \ingroup     planningproduction
 * \brief       This file is a CRUD class file for PlanningProduction (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

/**
 * Class for PlanningProduction
 */
class PlanningProduction extends CommonObject
{
    /**
     * @var string ID of module.
     */
    public $module = 'planningproduction';

    /**
     * @var string ID to identify managed object.
     */
    public $element = 'planningproduction';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
     */
    public $table_element = 'planningproduction_planning';

    /**
     * @var int  Does this object support multicompany module ?
     * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
     */
    public $ismultientitymanaged = 0;

    /**
     * @var int  Does object support extrafields ? 0=No, 1=Yes
     */
    public $isextrafieldmanaged = 1;

    /**
     * @var string String with name of icon for planningproduction. Must be the part after the 'object_' into object_planningproduction.png
     */
    public $picto = 'fa-calendar-alt';

    /**
     *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter]]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
     *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
     *  'label' the translation key.
     *  'picto' is code of a picto to show before value in forms
     *  'enabled' is a condition when the field must be managed (Example: 1 or 'isModEnabled("accounting")' or 'getDolGlobalString("MYMODULE_MYOPTION")==1')
     *  'position' is the sort order of field.
     *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
     *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list but not create/update/view forms, 5=Visible on list and view only (not create/not update). 5 is used by action 'add'). Using a negative value means field is not shown by default on list but can be selected for viewing (used in ajax mode for example).
     *  'noteditable' says if field is not editable (1 or 0)
     *  'alwayseditable' says if field can be modified also when status is not draft ('1' or '0')
     *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
     *  'index' if we want an index in database.
     *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
     *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
     *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
     *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
     *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
     *  'showoncombobox' if value of the field must be visible into the label of the combobox that show list of record
     *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
     *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Canceled"). Note that type can be 'integer' or 'varchar'
     *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
     *  'comment' is not used. You can store here any text of your choice. It is not used by application.
     *	'validate' is 1 if need to validate with $this->validateField()
     *  'copylastvalue' is 1 if we want to have field pre-filled with last record value on create mode (require to have the field visible on create form)
     *
     *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
     */
    public $fields=array(
        'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'css'=>'left', 'comment'=>"Id"),
        'fk_commande' => array('type'=>'integer:Commande:commande/class/commande.class.php:1', 'label'=>'Commande', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>1, 'noteditable'=>'0', 'index'=>1, 'foreignkey'=>'llx_commande.rowid',),
        'fk_commandedet' => array('type'=>'integer', 'label'=>'LigneCommande', 'enabled'=>'1', 'position'=>15, 'notnull'=>1, 'visible'=>1, 'noteditable'=>'0', 'index'=>1, 'foreignkey'=>'llx_commandedet.rowid',),
        'semaine' => array('type'=>'integer', 'label'=>'Semaine', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>1, 'noteditable'=>'0', 'index'=>1,),
        'annee' => array('type'=>'integer', 'label'=>'Annee', 'enabled'=>'1', 'position'=>25, 'notnull'=>1, 'visible'=>1, 'noteditable'=>'0', 'index'=>1,),
        'groupe_nom' => array('type'=>'varchar(255)', 'label'=>'GroupeNom', 'enabled'=>'1', 'position'=>30, 'notnull'=>0, 'visible'=>1, 'noteditable'=>'0',),
        'ordre_groupe' => array('type'=>'integer', 'label'=>'OrdreGroupe', 'enabled'=>'1', 'position'=>35, 'notnull'=>0, 'visible'=>0, 'noteditable'=>'0', 'default'=>'0',),
        'ordre_semaine' => array('type'=>'integer', 'label'=>'OrdreSemaine', 'enabled'=>'1', 'position'=>40, 'notnull'=>0, 'visible'=>0, 'noteditable'=>'0', 'default'=>'0',),
        'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>0,),
        'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>0,),
        'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>510, 'notnull'=>1, 'visible'=>0, 'foreignkey'=>'llx_user.rowid',),
        'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>511, 'notnull'=>-1, 'visible'=>0,),
        'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>'1', 'position'=>1000, 'notnull'=>-1, 'visible'=>-2,),
        'status' => array('type'=>'integer', 'label'=>'Status', 'enabled'=>'1', 'position'=>2000, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'default'=>'0', 'arrayofkeyval'=>array('0'=>'Draft', '1'=>'Validated', '9'=>'Canceled')),
    );
    public $rowid;
    public $fk_commande;
    public $fk_commandedet;
    public $semaine;
    public $annee;
    public $groupe_nom;
    public $ordre_groupe;
    public $ordre_semaine;
    public $date_creation;
    public $tms;
    public $fk_user_creat;
    public $fk_user_modif;
    public $import_key;
    public $status;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        global $conf, $langs;

        $this->db = $db;

        if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) {
            $this->fields['rowid']['visible'] = 0;
        }
        if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
            $this->fields['entity']['enabled'] = 0;
        }

        // Unset fields that are disabled
        foreach ($this->fields as $key => $val) {
            if (isset($val['enabled']) && empty($val['enabled'])) {
                unset($this->fields[$key]);
            }
        }

        // Translate some data of arrayofkeyval
        if (is_object($langs)) {
            foreach ($this->fields as $key => $val) {
                if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
                    foreach ($val['arrayofkeyval'] as $key2 => $val2) {
                        $this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
                    }
                }
            }
        }
    }

    /**
     * Récupérer les cartes par statut de production
     *
     * @param string $statut_filter Filtre sur le statut ('unplanned', 'a_terminer', 'a_expedier')
     * @return array Tableau des cartes
     */
    public function getCardsByStatus($statut_filter = 'unplanned')
    {
        $cards = array();
        
        $sql = "SELECT DISTINCT cd.rowid as commandedet_id, c.rowid as commande_id, c.ref as commande_ref, ";
        $sql .= "c.fk_soc, s.nom as societe_nom, c.date_creation, ";
        $sql .= "cd.description as produit_description, cd.qty, cd.product_type, ";
        $sql .= "p.ref as produit_ref, p.label as produit_label, ";
        $sql .= "u.short_label as unite, ";
        // Extrafields de commande
        $sql .= "c_ef.version, c_ef.delai_liv, c_ef.statut_ar, ";
        // Extrafields de ligne
        $sql .= "cd_ef.matiere, cd_ef.statut_mp, cd_ef.statut_prod, cd_ef.postlaquage, ";
        // Sous-requête pour récupérer le ref_chantier du service (ID=361) directement au-dessus
        $sql .= "(SELECT cd_titre_ef.ref_chantier ";
        $sql .= " FROM ".MAIN_DB_PREFIX."commandedet cd_titre ";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commandedet_extrafields cd_titre_ef ON cd_titre.rowid = cd_titre_ef.fk_object ";
        $sql .= " WHERE cd_titre.fk_commande = cd.fk_commande ";
        $sql .= " AND cd_titre.fk_product = 361 ";
        $sql .= " AND cd_titre.rang < cd.rang ";
        $sql .= " ORDER BY cd_titre.rang DESC ";
        $sql .= " LIMIT 1 ";
        $sql .= ") as titre_ref_chantier, ";
        // Sous-requête pour détecter si la ligne suivante est le produit Vernis (ID=299)
        $sql .= "(SELECT CASE WHEN cd_next.fk_product = 299 THEN 1 ELSE 0 END ";
        $sql .= " FROM ".MAIN_DB_PREFIX."commandedet cd_next ";
        $sql .= " WHERE cd_next.fk_commande = cd.fk_commande ";
        $sql .= " AND cd_next.rang > cd.rang ";
        $sql .= " ORDER BY cd_next.rang ASC ";
        $sql .= " LIMIT 1 ";
        $sql .= ") as has_vn ";

        $sql .= "FROM ".MAIN_DB_PREFIX."commande c ";
        $sql .= "INNER JOIN ".MAIN_DB_PREFIX."commandedet cd ON c.rowid = cd.fk_commande ";
        $sql .= "LEFT JOIN ".MAIN_DB_PREFIX."societe s ON c.fk_soc = s.rowid ";
        $sql .= "LEFT JOIN ".MAIN_DB_PREFIX."product p ON cd.fk_product = p.rowid ";
        $sql .= "LEFT JOIN ".MAIN_DB_PREFIX."c_units u ON cd.fk_unit = u.rowid ";
        $sql .= "LEFT JOIN ".MAIN_DB_PREFIX."commande_extrafields c_ef ON c.rowid = c_ef.fk_object ";
        $sql .= "LEFT JOIN ".MAIN_DB_PREFIX."commandedet_extrafields cd_ef ON cd.rowid = cd_ef.fk_object ";

        // Conditions de base
        $sql .= "WHERE c.fk_statut = 1 "; // Commandes validées
        $sql .= "AND c.facture = 0 "; // Non facturées (non expédiées)
        $sql .= "AND p.finished = 1 "; // Produits manufacturés uniquement
        $sql .= "AND cd.fk_product != 299 "; // Exclure le produit Vernis
        $sql .= "AND c.entity IN (".getEntity('commande').")";
        
        // Filtrer selon le statut demandé
        switch ($statut_filter) {
            case 'unplanned':
                // Non planifiées (comme avant)
                $sql .= " AND NOT EXISTS (SELECT 1 FROM ".MAIN_DB_PREFIX."planningproduction_planning pp WHERE pp.fk_commandedet = cd.rowid)";
                $sql .= " AND (cd_ef.statut_prod IS NULL OR cd_ef.statut_prod = '' OR cd_ef.statut_prod = 'À PRODUIRE' OR cd_ef.statut_prod = 'EN COURS')";
                break;
                
            case 'a_terminer':
                // À terminer
                $sql .= " AND cd_ef.statut_prod = 'À TERMINER'";
                break;
                
            case 'a_expedier':
                // À expédier 
                $sql .= " AND cd_ef.statut_prod = 'BON POUR EXPÉDITION'";
                break;
        }
        
        $sql .= " ORDER BY c.date_creation DESC, cd.rang ASC";

        dol_syslog(get_class($this)."::getCardsByStatus(".  $statut_filter .")", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                
                // Construire l'adresse de livraison
                $delivery_address = $this->getDeliveryAddress($obj->commande_id);
                
                $card = array(
                    'id' => 'card_' . $statut_filter . '_' . $obj->commandedet_id,
                    'fk_commande' => $obj->commande_id,
                    'fk_commandedet' => $obj->commandedet_id,
                    'commande_ref' => $obj->commande_ref,
                    'version' => $obj->version ?: 'V1',
                    'client' => $obj->societe_nom,
                    'fk_soc' => $obj->fk_soc,
                    'ref_chantier' => $obj->titre_ref_chantier ?: '-',
                    'delivery' => $delivery_address,
                    'deadline' => $obj->delai_liv ?: '-',
                    'produit' => $obj->produit_label ?: $obj->produit_description,
                    'produit_ref' => $obj->produit_ref,
                    'quantity' => $obj->qty,
                    'unite' => $obj->unite ?: 'u',
                    'matiere' => $obj->matiere ?: '-',
                    'statut_mp' => $obj->statut_mp,
                    'statut_ar' => $obj->statut_ar,
                    'statut_prod' => $obj->statut_prod ?: 'À PRODUIRE',
                    'postlaquage' => $obj->postlaquage,
                    'has_vn' => !empty($obj->has_vn)
                );

                $cards[] = $card;
                $i++;
            }
            $this->db->free($resql);
        } else {
            $this->errors[] = "Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::getCardsByStatus ".$this->db->lasterror(), LOG_ERR);
            return false;
        }
        
        return $cards;
    }

    /**
     * Récupérer toutes les cartes non planifiées (méthode de compatibilité)
     *
     * @return array Tableau des cartes non planifiées
     */
    public function getUnplannedCards()
    {
        return $this->getCardsByStatus('unplanned');
    }

    /**
     * Récupérer les cartes à terminer
     *
     * @return array Tableau des cartes à terminer
     */
    public function getCardsToFinish()
    {
        return $this->getCardsByStatus('a_terminer');
    }

    /**
     * Récupérer les cartes à expédier
     *
     * @return array Tableau des cartes à expédier
     */
    public function getCardsToShip()
    {
        return $this->getCardsByStatus('a_expedier');
    }

    /**
     * Récupérer l'adresse de livraison d'une commande
     * 1. Chercher le contact "Livraison commande" de la commande
     * 2. Si pas trouvé, prendre l'adresse du tiers
     * 3. S'il y en a plusieurs, prendre le premier
     *
     * @param int $fk_commande ID de la commande
     * @return string Adresse de livraison formatée
     */
    private function getDeliveryAddress($fk_commande)
    {
        // 1. Chercher le contact de type "Livraison commande" (SHIPPING)
        $sql = "SELECT sp.zip, sp.town ";
        $sql .= "FROM ".MAIN_DB_PREFIX."element_contact ec ";
        $sql .= "INNER JOIN ".MAIN_DB_PREFIX."c_type_contact tc ON ec.fk_c_type_contact = tc.rowid ";
        $sql .= "INNER JOIN ".MAIN_DB_PREFIX."socpeople sp ON ec.fk_socpeople = sp.rowid ";
        $sql .= "WHERE ec.element_id = ".((int) $fk_commande)." ";
        $sql .= "AND ec.statut = 4 "; // Statut actif
        $sql .= "AND tc.element = 'commande' ";
        $sql .= "AND tc.source = 'external' ";
        $sql .= "AND tc.code = 'SHIPPING' "; // Code pour "Livraison commande"
        $sql .= "ORDER BY ec.datecreate ASC "; // Prendre le premier si plusieurs
        $sql .= "LIMIT 1";
        
        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql)) {
            $obj = $this->db->fetch_object($resql);
            if ($obj->zip && $obj->town) {
                return $obj->zip . ' ' . $obj->town;
            }
        }
        
        // 2. Si pas de contact livraison, prendre l'adresse du tiers
        $sql = "SELECT s.zip, s.town ";
        $sql .= "FROM ".MAIN_DB_PREFIX."commande c ";
        $sql .= "LEFT JOIN ".MAIN_DB_PREFIX."societe s ON c.fk_soc = s.rowid ";
        $sql .= "WHERE c.rowid = ".((int) $fk_commande);
        
        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql)) {
            $obj = $this->db->fetch_object($resql);
            if ($obj->zip && $obj->town) {
                return $obj->zip . ' ' . $obj->town;
            }
        }
        
        return '-';
    }

    /**
     * Récupérer les cartes planifiées pour une plage de semaines
     *
     * @param int $start_week Semaine de début
     * @param int $nb_weeks Nombre de semaines
     * @param int $year Année
     * @return array Tableau des cartes planifiées par semaine
     */
    public function getPlannedCards($start_week, $nb_weeks, $year)
    {
        $planned_cards = array();
        
        for ($i = 0; $i < $nb_weeks; $i++) {
            $week = $start_week + $i;
            if ($week > 52) break;
            
            $planned_cards[$week] = array(
                'elements' => 0,
                'groups' => 0,
                'cards' => array()
            );
        }
        
        $sql = "SELECT pp.*, cd.rowid as commandedet_id, c.rowid as commande_id, c.ref as commande_ref, ";
        $sql .= "c.fk_soc, s.nom as societe_nom, c.date_creation, ";
        $sql .= "cd.description as produit_description, cd.qty, cd.product_type, ";
        $sql .= "p.ref as produit_ref, p.label as produit_label, ";
        $sql .= "u.short_label as unite, ";
        // Extrafields de commande
        $sql .= "c_ef.version, c_ef.delai_liv, c_ef.statut_ar, ";
        // Extrafields de ligne
        $sql .= "cd_ef.matiere, cd_ef.statut_mp, cd_ef.statut_prod, cd_ef.postlaquage, ";
        // Sous-requête pour récupérer le ref_chantier du service (ID=361) directement au-dessus
        $sql .= "(SELECT cd_titre_ef.ref_chantier ";
        $sql .= " FROM ".MAIN_DB_PREFIX."commandedet cd_titre ";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commandedet_extrafields cd_titre_ef ON cd_titre.rowid = cd_titre_ef.fk_object ";
        $sql .= " WHERE cd_titre.fk_commande = cd.fk_commande ";
        $sql .= " AND cd_titre.fk_product = 361 ";
        $sql .= " AND cd_titre.rang < cd.rang ";
        $sql .= " ORDER BY cd_titre.rang DESC ";
        $sql .= " LIMIT 1 ";
        $sql .= ") as titre_ref_chantier, ";
        // Sous-requête pour détecter si la ligne suivante est le produit Vernis (ID=299)
        $sql .= "(SELECT CASE WHEN cd_next.fk_product = 299 THEN 1 ELSE 0 END ";
        $sql .= " FROM ".MAIN_DB_PREFIX."commandedet cd_next ";
        $sql .= " WHERE cd_next.fk_commande = cd.fk_commande ";
        $sql .= " AND cd_next.rang > cd.rang ";
        $sql .= " ORDER BY cd_next.rang ASC ";
        $sql .= " LIMIT 1 ";
        $sql .= ") as has_vn ";

        $sql .= "FROM ".MAIN_DB_PREFIX."planningproduction_planning pp ";
        $sql .= "INNER JOIN ".MAIN_DB_PREFIX."commande c ON pp.fk_commande = c.rowid ";
        $sql .= "INNER JOIN ".MAIN_DB_PREFIX."commandedet cd ON pp.fk_commandedet = cd.rowid ";
        $sql .= "LEFT JOIN ".MAIN_DB_PREFIX."societe s ON c.fk_soc = s.rowid ";
        $sql .= "LEFT JOIN ".MAIN_DB_PREFIX."product p ON cd.fk_product = p.rowid ";
        $sql .= "LEFT JOIN ".MAIN_DB_PREFIX."c_units u ON cd.fk_unit = u.rowid ";
        $sql .= "LEFT JOIN ".MAIN_DB_PREFIX."commande_extrafields c_ef ON c.rowid = c_ef.fk_object ";
        $sql .= "LEFT JOIN ".MAIN_DB_PREFIX."commandedet_extrafields cd_ef ON cd.rowid = cd_ef.fk_object ";
        
        $sql .= "WHERE pp.annee = ".((int) $year)." ";
        $sql .= "AND pp.semaine >= ".((int) $start_week)." ";
        $sql .= "AND pp.semaine < ".((int) ($start_week + $nb_weeks))." ";
        $sql .= "AND cd.fk_product != 299 "; // Exclure le produit Vernis

        // MODIFICATION IMPORTANTE : Exclure les cartes avec statut "À TERMINER" ou "BON POUR EXPÉDITION"
        // Ces cartes ne doivent apparaître que dans les onglets, pas dans le planning des semaines
        $sql .= "AND (cd_ef.statut_prod IS NULL OR cd_ef.statut_prod = '' OR cd_ef.statut_prod NOT IN ('À TERMINER', 'BON POUR EXPÉDITION')) ";
        
        $sql .= "AND c.entity IN (".getEntity('commande').")";
        
        $sql .= " ORDER BY pp.semaine ASC, pp.ordre_semaine ASC, pp.ordre_groupe ASC";

        dol_syslog(get_class($this)."::getPlannedCards", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            $groups_by_week = array();
            
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                
                // Construire l'adresse de livraison
                $delivery_address = $this->getDeliveryAddress($obj->commande_id);
                
                $card = array(
                    'id' => 'card_planned_' . $obj->commandedet_id,
                    'fk_commande' => $obj->commande_id,
                    'fk_commandedet' => $obj->commandedet_id,
                    'commande_ref' => $obj->commande_ref,
                    'version' => $obj->version ?: 'V1',
                    'client' => $obj->societe_nom,
                    'ref_chantier' => $obj->titre_ref_chantier ?: '-',
                    'delivery' => $delivery_address,
                    'deadline' => $obj->delai_liv ?: '-',
                    'produit' => $obj->produit_label ?: $obj->produit_description,
                    'produit_ref' => $obj->produit_ref,
                    'quantity' => $obj->qty,
                    'unite' => $obj->unite ?: 'u',
                    'matiere' => $obj->matiere ?: '-',
                    'statut_mp' => $obj->statut_mp,
                    'statut_ar' => $obj->statut_ar,
                    'statut_prod' => $obj->statut_prod,
                    'postlaquage' => $obj->postlaquage,
                    'has_vn' => !empty($obj->has_vn),
                    'groupe' => $obj->groupe_nom ?: 'Groupe par défaut',
                    'semaine' => $obj->semaine
                );
                
                if (isset($planned_cards[$obj->semaine])) {
                    $planned_cards[$obj->semaine]['cards'][] = $card;
                    $planned_cards[$obj->semaine]['elements']++;
                    
                    // Compter les groupes uniques par semaine
                    if (!isset($groups_by_week[$obj->semaine])) {
                        $groups_by_week[$obj->semaine] = array();
                    }
                    if (!in_array($obj->groupe_nom, $groups_by_week[$obj->semaine])) {
                        $groups_by_week[$obj->semaine][] = $obj->groupe_nom;
                        $planned_cards[$obj->semaine]['groups']++;
                    }
                }
                
                $i++;
            }
            $this->db->free($resql);
        } else {
            $this->errors[] = "Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::getPlannedCards ".$this->db->lasterror(), LOG_ERR);
            return false;
        }
        
        return $planned_cards;
    }

    /**
     * Sauvegarder une carte dans le planning
     *
     * @param int $fk_commande ID de la commande
     * @param int $fk_commandedet ID de la ligne de commande
     * @param int $semaine Numéro de semaine
     * @param int $annee Année
     * @param string $groupe_nom Nom du groupe
     * @param int $ordre_groupe Ordre dans le groupe
     * @param int $ordre_semaine Ordre du groupe dans la semaine
     * @return int ID de l'enregistrement créé ou -1 si erreur
     */
    public function savePlannedCard($fk_commande, $fk_commandedet, $semaine, $annee, $groupe_nom = '', $ordre_groupe = 0, $ordre_semaine = 0)
    {
        global $user;
        
        // Vérifier si la carte n'est pas déjà planifiée
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."planningproduction_planning ";
        $sql .= "WHERE fk_commandedet = ".((int) $fk_commandedet);
        
        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql)) {
            // Mettre à jour l'enregistrement existant
            $obj = $this->db->fetch_object($resql);
            return $this->updatePlannedCard($obj->rowid, $semaine, $annee, $groupe_nom, $ordre_groupe, $ordre_semaine);
        }
        
        // Créer un nouvel enregistrement
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."planningproduction_planning (";
        $sql .= "fk_commande, fk_commandedet, semaine, annee, groupe_nom, ";
        $sql .= "ordre_groupe, ordre_semaine, date_creation, fk_user_creat, status";
        $sql .= ") VALUES (";
        $sql .= ((int) $fk_commande).", ";
        $sql .= ((int) $fk_commandedet).", ";
        $sql .= ((int) $semaine).", ";
        $sql .= ((int) $annee).", ";
        $sql .= "'".$this->db->escape($groupe_nom)."', ";
        $sql .= ((int) $ordre_groupe).", ";
        $sql .= ((int) $ordre_semaine).", ";
        $sql .= "'".$this->db->idate(dol_now())."', ";
        $sql .= ((int) $user->id).", ";
        $sql .= "0";
        $sql .= ")";
        
        dol_syslog(get_class($this)."::savePlannedCard", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            return $this->db->last_insert_id(MAIN_DB_PREFIX."planningproduction_planning");
        } else {
            $this->errors[] = "Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::savePlannedCard ".$this->db->lasterror(), LOG_ERR);
            return -1;
        }
    }

    /**
     * Mettre à jour une carte planifiée
     *
     * @param int $rowid ID de l'enregistrement
     * @param int $semaine Numéro de semaine
     * @param int $annee Année
     * @param string $groupe_nom Nom du groupe
     * @param int $ordre_groupe Ordre dans le groupe
     * @param int $ordre_semaine Ordre du groupe dans la semaine
     * @return int 1 si OK, -1 si erreur
     */
    public function updatePlannedCard($rowid, $semaine, $annee, $groupe_nom = '', $ordre_groupe = 0, $ordre_semaine = 0)
    {
        global $user;
        
        $sql = "UPDATE ".MAIN_DB_PREFIX."planningproduction_planning SET ";
        $sql .= "semaine = ".((int) $semaine).", ";
        $sql .= "annee = ".((int) $annee).", ";
        $sql .= "groupe_nom = '".$this->db->escape($groupe_nom)."', ";
        $sql .= "ordre_groupe = ".((int) $ordre_groupe).", ";
        $sql .= "ordre_semaine = ".((int) $ordre_semaine).", ";
        $sql .= "fk_user_modif = ".((int) $user->id)." ";
        $sql .= "WHERE rowid = ".((int) $rowid);
        
        dol_syslog(get_class($this)."::updatePlannedCard", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            return 1;
        } else {
            $this->errors[] = "Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::updatePlannedCard ".$this->db->lasterror(), LOG_ERR);
            return -1;
        }
    }

    /**
     * Supprimer une carte du planning
     *
     * @param int $fk_commandedet ID de la ligne de commande
     * @return int 1 si OK, -1 si erreur
     */
    public function removePlannedCard($fk_commandedet)
    {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."planningproduction_planning ";
        $sql .= "WHERE fk_commandedet = ".((int) $fk_commandedet);
        
        dol_syslog(get_class($this)."::removePlannedCard", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            return 1;
        } else {
            $this->errors[] = "Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::removePlannedCard ".$this->db->lasterror(), LOG_ERR);
            return -1;
        }
    }

    /**
     * Mettre à jour les extrafields d'une ligne de commande
     *
     * @param int $fk_commandedet ID de la ligne de commande
     * @param array $fields Champs à mettre à jour
     * @return int 1 si OK, -1 si erreur
     */
    public function updateCommandedetExtrafields($fk_commandedet, $fields)
    {
        $sql_parts = array();
        
        if (isset($fields['matiere'])) {
            $sql_parts[] = "matiere = '".$this->db->escape($fields['matiere'])."'";
        }
        if (isset($fields['statut_mp'])) {
            $sql_parts[] = "statut_mp = '".$this->db->escape($fields['statut_mp'])."'";
        }
        if (isset($fields['postlaquage'])) {
            $sql_parts[] = "postlaquage = '".$this->db->escape($fields['postlaquage'])."'";
        }
        if (isset($fields['statut_prod'])) {
            $sql_parts[] = "statut_prod = '".$this->db->escape($fields['statut_prod'])."'";
        }
        
        if (empty($sql_parts)) {
            return 1; // Rien à faire
        }
        
        // Vérifier si l'enregistrement extrafields existe
        $sql_check = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."commandedet_extrafields ";
        $sql_check .= "WHERE fk_object = ".((int) $fk_commandedet);
        
        $resql = $this->db->query($sql_check);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj->nb > 0) {
                // Mise à jour
                $sql = "UPDATE ".MAIN_DB_PREFIX."commandedet_extrafields SET ";
                $sql .= implode(', ', $sql_parts);
                $sql .= " WHERE fk_object = ".((int) $fk_commandedet);
            } else {
                // Insertion
                $fields_list = array('fk_object' => $fk_commandedet);
                if (isset($fields['matiere'])) $fields_list['matiere'] = $fields['matiere'];
                if (isset($fields['statut_mp'])) $fields_list['statut_mp'] = $fields['statut_mp'];
                if (isset($fields['statut_prod'])) $fields_list['statut_prod'] = $fields['statut_prod'];
                if (isset($fields['postlaquage'])) $fields_list['postlaquage'] = $fields['postlaquage'];
                
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."commandedet_extrafields (";
                $sql .= implode(', ', array_keys($fields_list));
                $sql .= ") VALUES (";
                $sql .= implode(', ', array_map(function($v) { return is_numeric($v) ? $v : "'".$this->db->escape($v)."'"; }, $fields_list));
                $sql .= ")";
            }
        } else {
            return -1;
        }
        
        dol_syslog(get_class($this)."::updateCommandedetExtrafields", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            return 1;
        } else {
            $this->errors[] = "Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::updateCommandedetExtrafields ".$this->db->lasterror(), LOG_ERR);
            return -1;
        }
    }

    // ========== MÉTHODES POUR LA GESTION DES MATIÈRES PREMIÈRES ==========

    /**
     * Récupérer toutes les matières premières
     *
     * @param bool $order_by_position Trier par ordre de position (true) ou par code MP (false)
     * @return array Tableau des matières premières
     */
    public function getAllMatieres($order_by_position = true)
    {
        $matieres = array();
        
        $sql = "SELECT rowid, code_mp, stock, cde_en_cours_date, tms, ordre ";
        $sql .= "FROM ".MAIN_DB_PREFIX."planningproduction_matieres ";
        $sql .= "WHERE entity IN (".getEntity('planningproduction').') ';
        if ($order_by_position) {
            $sql .= "ORDER BY ordre ASC, code_mp ASC";
        } else {
            $sql .= "ORDER BY code_mp ASC";
        }
        
        dol_syslog(get_class($this)."::getAllMatieres - SQL: ".$sql, LOG_DEBUG);
        dol_syslog(get_class($this)."::getAllMatieres - Entity: ".getEntity('planningproduction'), LOG_DEBUG);
        
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            dol_syslog(get_class($this)."::getAllMatieres - Found ".$num." rows", LOG_DEBUG);
            
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                
                $cde_en_cours_date = isset($obj->cde_en_cours_date) ? (float)$obj->cde_en_cours_date : 0;
                
                $matiere = array(
                    'rowid' => $obj->rowid,
                    'code_mp' => $obj->code_mp,
                    'stock' => (float)$obj->stock,
                    'cde_en_cours_date' => $cde_en_cours_date,
                    'ordre' => (int)$obj->ordre,
                    'cde_en_cours' => 0, // Sera calculé séparément
                    'reste' => (float)$obj->stock - $cde_en_cours_date, // Calculé avec cde_en_cours_date
                    'date_maj' => $obj->tms
                );
                
                $matieres[] = $matiere;
                $i++;
            }
            $this->db->free($resql);
        } else {
            $error_msg = "Error ".$this->db->lasterror();
            $this->errors[] = $error_msg;
            dol_syslog(get_class($this)."::getAllMatieres ".$error_msg, LOG_ERR);
            return false;
        }
        
        dol_syslog(get_class($this)."::getAllMatieres - Returning ".count($matieres)." matières", LOG_DEBUG);
        return $matieres;
    }

    /**
     * Calculer les commandes en cours pour un code MP donné
     *
     * @param string $code_mp Code de la matière première
     * @return float Quantité en cours de commande
     */
    public function calculateCdeEnCours($code_mp)
    {
        $total_qty = 0;
        
        // Récupérer toutes les cartes sauf celles À TERMINER et BON POUR EXPÉDITION
        $sql = "SELECT SUM(cd.qty) as total_qty ";
        $sql .= "FROM ".MAIN_DB_PREFIX."commande c ";
        $sql .= "INNER JOIN ".MAIN_DB_PREFIX."commandedet cd ON c.rowid = cd.fk_commande ";
        $sql .= "LEFT JOIN ".MAIN_DB_PREFIX."product p ON cd.fk_product = p.rowid ";
        $sql .= "LEFT JOIN ".MAIN_DB_PREFIX."commandedet_extrafields cd_ef ON cd.rowid = cd_ef.fk_object ";
        
        $sql .= "WHERE c.fk_statut = 1 "; // Commandes validées
        $sql .= "AND c.facture = 0 "; // Non facturées (non expédiées)
        $sql .= "AND p.finished = 1 "; // Produits manufacturés uniquement
        $sql .= "AND c.entity IN (".getEntity('commande').") ";
        
        // Exclure les statuts À TERMINER et BON POUR EXPÉDITION
        $sql .= "AND (cd_ef.statut_prod IS NULL OR cd_ef.statut_prod = '' OR cd_ef.statut_prod NOT IN ('À TERMINER', 'BON POUR EXPÉDITION')) ";
        
        // Rechercher le code MP dans le champ matiere
        $sql .= "AND cd_ef.matiere LIKE '%".$this->db->escape($code_mp)."%' ";
        
        dol_syslog(get_class($this)."::calculateCdeEnCours(".$code_mp.")", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);
                $total_qty = (float) $obj->total_qty;
            }
            $this->db->free($resql);
        } else {
            $this->errors[] = "Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::calculateCdeEnCours ".$this->db->lasterror(), LOG_ERR);
            return false;
        }
        
        return $total_qty;
    }

    /**
     * Créer une nouvelle matière première
     *
     * @param string $code_mp Code de la matière première
     * @param float $stock Stock initial
     * @return int ID de l'enregistrement créé ou -1 si erreur
     */
    public function createMatiere($code_mp, $stock = 0)
    {
        global $user, $conf;
        
        // Vérifier que le code n'existe pas déjà
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."planningproduction_matieres ";
        $sql .= "WHERE code_mp = '".$this->db->escape($code_mp)."' ";
        $sql .= "AND entity IN (".getEntity('planningproduction').")";
        
        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql)) {
            $this->errors[] = "Le code MP existe déjà";
            return -1;
        }
        
        // Récupérer le prochain ordre
        $next_ordre = $this->getNextMatiereOrdre();
        
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."planningproduction_matieres (";
        $sql .= "code_mp, stock, ordre, date_creation, fk_user_creat, entity";
        $sql .= ") VALUES (";
        $sql .= "'".$this->db->escape($code_mp)."', ";
        $sql .= ((float) $stock).", ";
        $sql .= ((int) $next_ordre).", ";
        $sql .= "'".$this->db->idate(dol_now())."', ";
        $sql .= ((int) $user->id).", ";
        $sql .= ((int) $conf->entity);
        $sql .= ")";
        
        dol_syslog(get_class($this)."::createMatiere", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            return $this->db->last_insert_id(MAIN_DB_PREFIX."planningproduction_matieres");
        } else {
            $this->errors[] = "Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::createMatiere ".$this->db->lasterror(), LOG_ERR);
            return -1;
        }
    }

    /**
     * Mettre à jour le stock d'une matière première
     *
     * @param int $rowid ID de la matière première
     * @param float $stock Nouveau stock
     * @return int 1 si OK, -1 si erreur
     */
    public function updateMatiereStock($rowid, $stock)
    {
        global $user;
        
        $sql = "UPDATE ".MAIN_DB_PREFIX."planningproduction_matieres SET ";
        $sql .= "stock = ".((float) $stock).", ";
        $sql .= "fk_user_modif = ".((int) $user->id)." ";
        $sql .= "WHERE rowid = ".((int) $rowid);
        
        dol_syslog(get_class($this)."::updateMatiereStock", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            return 1;
        } else {
            $this->errors[] = "Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::updateMatiereStock ".$this->db->lasterror(), LOG_ERR);
            return -1;
        }
    }

    /**
     * Mettre à jour les commandes en cours à date d'une matière première
     *
     * @param int $rowid ID de la matière première
     * @param float $cde_en_cours_date Nouvelle valeur des commandes en cours à date
     * @return int 1 si OK, -1 si erreur
     */
    public function updateMatiereCdeEnCoursDate($rowid, $cde_en_cours_date)
    {
        global $user;
        
        $sql = "UPDATE ".MAIN_DB_PREFIX."planningproduction_matieres SET ";
        $sql .= "cde_en_cours_date = ".((float) $cde_en_cours_date).", ";
        $sql .= "fk_user_modif = ".((int) $user->id)." ";
        $sql .= "WHERE rowid = ".((int) $rowid);
        
        dol_syslog(get_class($this)."::updateMatiereCdeEnCoursDate", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            return 1;
        } else {
            $this->errors[] = "Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::updateMatiereCdeEnCoursDate ".$this->db->lasterror(), LOG_ERR);
            return -1;
        }
    }

    /**
     * Supprimer une matière première
     *
     * @param int $rowid ID de la matière première
     * @return int 1 si OK, -1 si erreur
     */
    public function deleteMatiere($rowid)
    {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."planningproduction_matieres ";
        $sql .= "WHERE rowid = ".((int) $rowid);
        
        dol_syslog(get_class($this)."::deleteMatiere", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            return 1;
        } else {
            $this->errors[] = "Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::deleteMatiere ".$this->db->lasterror(), LOG_ERR);
            return -1;
        }
    }

    /**
     * Mettre à jour une matière première complète
     *
     * @param int $rowid ID de la matière première
     * @param string $code_mp Code de la matière première
     * @param float $stock Stock
     * @return int 1 si OK, -1 si erreur
     */
    public function updateMatiere($rowid, $code_mp, $stock)
    {
        global $user;
        
        // Vérifier que le code n'existe pas déjà pour un autre enregistrement
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."planningproduction_matieres ";
        $sql .= "WHERE code_mp = '".$this->db->escape($code_mp)."' ";
        $sql .= "AND rowid != ".((int) $rowid)." ";
        $sql .= "AND entity IN (".getEntity('planningproduction').")";
        
        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql)) {
            $this->errors[] = "Le code MP existe déjà";
            return -1;
        }
        
        $sql = "UPDATE ".MAIN_DB_PREFIX."planningproduction_matieres SET ";
        $sql .= "code_mp = '".$this->db->escape($code_mp)."', ";
        $sql .= "stock = ".((float) $stock).", ";
        $sql .= "fk_user_modif = ".((int) $user->id)." ";
        $sql .= "WHERE rowid = ".((int) $rowid);
        
        dol_syslog(get_class($this)."::updateMatiere", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            return 1;
        } else {
            $this->errors[] = "Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::updateMatiere ".$this->db->lasterror(), LOG_ERR);
            return -1;
        }
    }

    /**
     * Récupérer le prochain numéro d'ordre pour une nouvelle matière première
     *
     * @return int Prochain numéro d'ordre
     */
    public function getNextMatiereOrdre()
    {
        $sql = "SELECT MAX(ordre) as max_ordre ";
        $sql .= "FROM ".MAIN_DB_PREFIX."planningproduction_matieres ";
        $sql .= "WHERE entity IN (".getEntity('planningproduction').")";
        
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            return ((int) $obj->max_ordre) + 1;
        }
        
        return 1;
    }

    /**
     * Mettre à jour l'ordre d'une matière première
     *
     * @param int $rowid ID de la matière première
     * @param int $ordre Nouvel ordre
     * @return int 1 si OK, -1 si erreur
     */
    public function updateMatiereOrdre($rowid, $ordre)
    {
        global $user;
        
        $sql = "UPDATE ".MAIN_DB_PREFIX."planningproduction_matieres SET ";
        $sql .= "ordre = ".((int) $ordre).", ";
        $sql .= "fk_user_modif = ".((int) $user->id)." ";
        $sql .= "WHERE rowid = ".((int) $rowid);
        
        dol_syslog(get_class($this)."::updateMatiereOrdre", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            return 1;
        } else {
            $this->errors[] = "Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::updateMatiereOrdre ".$this->db->lasterror(), LOG_ERR);
            return -1;
        }
    }

    /**
     * Réorganiser l'ordre de toutes les matières premières
     *
     * @param array $ordered_ids Tableau des IDs dans le nouvel ordre
     * @return int 1 si OK, -1 si erreur
     */
    public function reorderMatieres($ordered_ids)
    {
        global $user;
        
        if (empty($ordered_ids) || !is_array($ordered_ids)) {
            return -1;
        }
        
        // Transaction pour éviter les incohérences
        $this->db->begin();
        
        $success = true;
        $ordre = 1;
        
        foreach ($ordered_ids as $rowid) {
            $sql = "UPDATE ".MAIN_DB_PREFIX."planningproduction_matieres SET ";
            $sql .= "ordre = ".((int) $ordre).", ";
            $sql .= "fk_user_modif = ".((int) $user->id)." ";
            $sql .= "WHERE rowid = ".((int) $rowid);
            
            $resql = $this->db->query($sql);
            if (!$resql) {
                $this->errors[] = "Error ".$this->db->lasterror();
                dol_syslog(get_class($this)."::reorderMatieres ".$this->db->lasterror(), LOG_ERR);
                $success = false;
                break;
            }
            
            $ordre++;
        }
        
        if ($success) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }
}
