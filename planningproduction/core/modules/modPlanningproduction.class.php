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
 * \defgroup   planningproduction     Module PlanningProduction
 * \brief      Planning de production hybride avec timeline et groupement
 * \file       core/modules/modPlanningproduction.class.php
 * \ingroup    planningproduction
 * \brief      Description and activation file for module PlanningProduction
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module PlanningProduction
 */
class modPlanningproduction extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf;
        $this->db = $db;

        // Id for module (must be unique).
        $this->numero = 543210;
        $this->rights_class = 'planningproduction';
        $this->family = "other";
        $this->module_position = '90';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Planning de production hybride avec timeline et groupement";
        $this->descriptionlong = "Module de gestion de planning de production avec interface hybride timeline et groupement des tâches par semaines";

        // Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
        $this->version = '1.0.0';
        $this->url_last_version = '';
        $this->editor_name = 'Patrick Delcroix';
        $this->editor_url = '';

        // Key used in llx_const table to save module status enabled/disabled (where PLANNINGPRODUCTION is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

        // Name of image file used for this module.
        $this->picto = 'fa-calendar';

        // Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
        $this->module_parts = array(
            // Set this to 1 if module has its own trigger directory (core/triggers)
            'triggers' => 0,
            // Set this to 1 if module has its own login method file (core/login)
            'login' => 0,
            // Set this to 1 if module has its own substitution function file (core/substitutions)
            'substitutions' => 0,
            // Set this to 1 if module has its own menus handler directory (core/menus)
            'menus' => 0,
            // Set this to 1 if module overwrite template dir (core/tpl)
            'tpl' => 0,
            // Set this to 1 if module has its own barcode directory (core/modules/barcode)
            'barcode' => 0,
            // Set this to 1 if module has its own models directory (core/modules/xxx)
            'models' => 0,
            // Set this to 1 if module has its own printing directory (core/modules/printing)
            'printing' => 0,
            // Set this to 1 if module has its own theme directory (theme)
            'theme' => 0,
            // Set this to relative path of css file if module has its own css file
            'css' => array(
                '/planningproduction/css/planning.css'
            ),
            // Set this to relative path of js file if module must load a js on all pages
            'js' => array(),
            // Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
            'hooks' => array(),
            // Set this to 1 if features of module are opened to external users
            'moduleforexternal' => 0,
        );

        // Data directories to create when module is enabled.
        $this->dirs = array("/planningproduction/temp");

        // Config pages
        $this->config_page_url = array("setup.php@planningproduction");

        // Dependencies
        $this->hidden = false; // A condition to hide module
        $this->depends = array('modCommande'); // List of module class names as string that must be enabled if this module is enabled. Example: array('always'=>array('modModuleToEnable1','modModuleToEnable2'), 'FR'=>array('modModuleToEnableFR'...))
        $this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
        $this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

        // The language file dedicated to your module
        $this->langfiles = array("planningproduction@planningproduction");

        // Prerequisites
        $this->phpmin = array(5, 6); // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(11, -3); // Minimum version of Dolibarr required by module

        // Messages at activation
        $this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
        $this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
        //$this->automatic_activation = array('FR'=>'PlanningproductionWasAutomaticallyActivatedBecauseOfYourCountryChoice');
        //$this->always_enabled = true;								// If true, can't be disabled

        // Constants
        $this->const = array(
            // PLANNINGPRODUCTION_MYCONSTANT1
            1 => array(
                'PLANNINGPRODUCTION_CARD_WIDTH',
                'chaine',
                '260',
                'Largeur des cartes en pixels (défaut: 260px, min: 200px, max: 500px)',
                0
            ),
        );

        // Some keys to add into the overwriting translation tables
        /*$this->overwrite_translation = array(
            'en_US:ParentCompany'=>'Parent company or reseller',
            'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
        )*/

        if (!isset($conf->planningproduction) || !isset($conf->planningproduction->enabled)) {
            $conf->planningproduction = new stdClass();
            $conf->planningproduction->enabled = 0;
        }

        // Array to add new pages in new tabs
        $this->tabs = array();

        // Dictionaries
        $this->dictionaries = array();

        // Boxes/Widgets
        $this->boxes = array(
            //  0 => array(
            //      'file' => 'planningproductionwidget1.php@planningproduction',
            //      'note' => 'Widget provided by PlanningProduction',
            //      'enabledbydefaulton' => 'Home'
            //  ),
        );

        // Cronjobs (List of cron jobs entries to add when module is enabled)
        $this->cronjobs = array(
            //  0 => array(
            //      'label' => 'MyJob label',
            //      'jobtype' => 'method',
            //      'class' => '/planningproduction/class/planningproduction.class.php',
            //      'objectname' => 'PlanningProduction',
            //      'method' => 'doScheduledJob',
            //      'parameters' => '',
            //      'comment' => 'Comment',
            //      'frequency' => 2,
            //      'unitfrequency' => 3600,
            //      'status' => 0,
            //      'test' => '$conf->planningproduction->enabled',
            //      'priority' => 50,
            //  ),
        );

        // Permissions provided by this module
        $this->rights = array();
        $r = 0;
        // Add here entries to declare new permissions
        /* BEGIN MODULEBUILDER PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = 'Lire les plannings de production'; // Permission label
        $this->rights[$r][4] = 'planning';
        $this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->planningproduction->planning->read)
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = 'Créer/modifier les plannings de production'; // Permission label
        $this->rights[$r][4] = 'planning';
        $this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->planningproduction->planning->write)
        $r++;
        /* END MODULEBUILDER PERMISSIONS */

        // Main menu entries to add
        $this->menu = array();
        $r = 0;

        // Add here entries to declare new menus
        /* BEGIN MODULEBUILDER TOPMENU */
        $this->menu[$r++] = array(
            'fk_menu'=>'', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'top', // This is a Top menu entry
            'titre'=>'Planning Production',
            'mainmenu'=>'planningproduction',
            'leftmenu'=>'',
            'url'=>'/custom/planningproduction/export_planning.php?type=global',
            'langs'=>'planningproduction@planningproduction', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1000 + $r,
            'enabled'=>'isModEnabled("planningproduction")', // Define condition to show or hide menu entry. Use 'isModEnabled("planningproduction")' if entry must be visible if module is enabled.
            'perms'=>'$user->hasRight("planningproduction", "planning", "read")', // Use 'perms'=>'$user->rights->planningproduction->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
        );
        /* END MODULEBUILDER TOPMENU */

        // Exports profiles provided by this module
        $r = 1;
        /* BEGIN MODULEBUILDER EXPORT PLANNINGPRODUCTION */
        /*
        $langs->load("planningproduction@planningproduction");
        $this->export_code[$r]=$this->rights_class.'_'.$r;
        $this->export_label[$r]='PlanningProductionLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
        $this->export_icon[$r]='planningproduction@planningproduction';
        // Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
        $keyforclass = 'PlanningProduction'; $keyforclassfile='/planningproduction/class/planningproduction.class.php'; $keyforelement='planningproduction@planningproduction';
        include DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';
        //$this->export_dependencies_array[$r] = array('mysubobject'=>array('ts.rowid','t.myfield')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for performance)
        //$this->export_special_array[$r] = array('t.field'=>'...');
        //$this->export_examplevalues_array[$r] = array('t.field'=>'Example');
        //$this->export_help_array[$r] = array('t.field'=>'FieldDescHelp');
        $this->export_sql_start[$r]='SELECT DISTINCT ';
        $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'planningproduction_planningproduction as t';
        //$this->export_sql_end[$r]  =' LEFT JOIN '.MAIN_DB_PREFIX.'planningproduction_planningproduction_extrafields as e ON e.fk_object = t.rowid';
        $this->export_sql_end[$r] .=' WHERE 1 = 1';
        $this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('planningproduction').')';
        $r++; */
        /* END MODULEBUILDER EXPORT PLANNINGPRODUCTION */

        // Imports profiles provided by this module
        $r = 1;
        /* BEGIN MODULEBUILDER IMPORT PLANNINGPRODUCTION */
        /*
        $langs->load("planningproduction@planningproduction");
        $this->import_code[$r]=$this->rights_class.'_'.$r;
        $this->import_label[$r]='PlanningProductionLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
        $this->import_icon[$r]='planningproduction@planningproduction';
        $this->import_entities_array[$r] = array();		// We define here only fields that use another icon that the one defined into import_icon
        $this->import_tables_array[$r] = array('t'=>MAIN_DB_PREFIX.'planningproduction_planningproduction'); // List of tables to insert into (insert done in same order)
        //$this->import_tables_creator_array[$r] = array('t'=>'fk_user_author'); // Fields to store import user id
        $this->import_datetime_array[$r] = array('t'=>'datec');
        $this->import_fields_array[$r] = array('t.ref'=>'Ref', 't.label'=>'Label', 't.description'=>'Description', 't.note_public'=>'NotePublic', 't.note_private'=>'NotePrivate');
        //$this->import_fields_array[$r]['t.field'] = 'Field label'
        //$this->import_fieldshidden_array[$r] = array('t.fk_user_author' => 'user->id');
        $this->import_regex_array[$r] = array();
        $this->import_examplevalues_array[$r] = array();
        $this->import_updatekeys_array[$r] = array('t.ref' => 'Ref');
        $this->import_convertvalue_array[$r] = array();
        $this->import_run_sql_after_array[$r] = array();
        $r++; */
        /* END MODULEBUILDER IMPORT PLANNINGPRODUCTION */
    }

    /**
     * Function called when module is enabled.
     * The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     * It also creates data directories
     *
     * @param string $options Options when enabling module ('', 'noboxes')
     * @return int             1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        global $conf, $langs;

        //$result = $this->_load_tables('/install/mysql/', 'planningproduction');
        // Load SQL files
        $this->sql = array(
            'llx_planningproduction_planning.sql',
            'llx_planningproduction_planning.key.sql',
            'llx_planningproduction_matieres.sql',
            'llx_planningproduction_matieres.key.sql'
        );
        
        $result = $this->_load_tables('/planningproduction/sql/');
        if ($result < 0) {
            return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
        }

        // Create extrafields during init
        //include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        //$extrafields = new ExtraFields($this->db);
        //$result1=$extrafields->addExtraField('planningproduction_myattr1', "New Attr 1 label", 'boolean', 1,  3, 'thirdparty',   0, 0, '', '', 1, '', 0, 0, '', '', 'planningproduction@planningproduction', 'isModEnabled("planningproduction")');
        //$result2=$extrafields->addExtraField('planningproduction_myattr2', "New Attr 2 label", 'varchar', 1, 10, 'project',      0, 0, '', '', 1, '', 0, 0, '', '', 'planningproduction@planningproduction', 'isModEnabled("planningproduction")');
        //$result3=$extrafields->addExtraField('planningproduction_myattr3', "New Attr 3 label", 'varchar', 1, 10, 'bank_account', 0, 0, '', '', 1, '', 0, 0, '', '', 'planningproduction@planningproduction', 'isModEnabled("planningproduction")');
        //$result4=$extrafields->addExtraField('planningproduction_myattr4', "New Attr 4 label", 'select',  1,  3, 'thirdparty',   0, 1, '', array('options'=>array('code1'=>'Val1','code2'=>'Val2','code3'=>'Val3')), 1,'', 0, 0, '', '', 'planningproduction@planningproduction', 'isModEnabled("planningproduction")');
        //$result5=$extrafields->addExtraField('planningproduction_myattr5', "New Attr 5 label", 'text',    1, 10, 'user',         0, 0, '', '', 1, '', 0, 0, '', '', 'planningproduction@planningproduction', 'isModEnabled("planningproduction")');

        // Permissions
        $this->remove($options);

        $sql = array();

        // Document templates
        $moduledir = dol_buildpath('/planningproduction', 0);
        $myTmpObjects = array();
        $myTmpObjects['PlanningProduction'] = array('includerefgeneration'=>0, 'includedocgeneration'=>0);

        foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
            if ($myTmpObjectKey == 'PlanningProduction') {
                continue;
            }
            if ($myTmpObjectArray['includerefgeneration']) {
                $src = DOL_DOCUMENT_ROOT.'/install/doctemplates/'.$this->rights_class.'/'.$myTmpObjectKey.'_template_webportal.odt';
                $dirodt = DOL_DATA_ROOT.'/doctemplates/'.$this->rights_class;
                $dest = $dirodt.'/'.$myTmpObjectKey.'_template_webportal.odt';
                if (file_exists($src) && !file_exists($dest)) {
                    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
                    dol_mkdir($dirodt);
                    $result = dol_copy($src, $dest, 0, 0);
                    if ($result < 0) {
                        $langs->load("errors");
                        $this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
                        return 0;
                    }
                }

                $sql = array_merge($sql, array(
                    "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'standard_".strtolower($myTmpObjectKey)."' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
                    "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('standard_".strtolower($myTmpObjectKey)."', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).")",
                    "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'generic_".strtolower($myTmpObjectKey)."_odt' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
                    "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('generic_".strtolower($myTmpObjectKey)."_odt', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).")"
                ));
            }
        }

        return $this->_init($sql, $options);
    }

    /**
     * Function called when module is disabled.
     * Remove from database constants, boxes and permissions from Dolibarr database.
     * Data directories are not deleted
     *
     * @param string $options Options when enabling module ('', 'noboxes')
     * @return int                1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
