<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2021       Marcello Gribaudo <marcello.gribaudo@opigi.com>
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
 * 	\defgroup   lowstockalert     Module LowStockAlert
 *  \brief      LowStockAlert module descriptor.
 *
 *  \file       htdocs/lowstockalert/core/modules/modLowStockAlert.class.php
 *  \ingroup    lowstockalert
 *  \brief      Description and activation file for module LowStockAlert
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module LowStockAlert
 */
class modLowStockAlert extends DolibarrModules {
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db) {
        global $langs, $conf;
        $this->db = $db;

        // Id for module (must be unique).
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 2208130; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module

        $this->rights_class = 'lowstockalert';
        $this->family = "products";
        $this->module_position = '90';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "LowStockAlertDescription";
        $this->descriptionlong = "LowStockAlertDescription";
        $this->editor_name = 'Marcello Gribaudo';
        $this->editor_url = 'http://www.opigi.com';
        $this->version = '1.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'lowstockalert@lowstockalert';
        $this->module_parts = array('triggers' => 1,
                                    'login' => 0,
                                    'substitutions' => 0,
                                    'menus' => 0,
                                    'tpl' => 0,
                                    'barcode' => 0,
                                    'models' => 0,
                                    'printing' => 0,
                                    'theme' => 0,
                                    'css' => array(),
                                    'js' => array(),
                                    'hooks' => array(                ),
                                    'moduleforexternal' => 0,
        );

        $this->dirs = array();

        // Config pages. Put here list of php page, stored into lowstockalert/admin directory, to use to setup module.
        $this->config_page_url = array("setup.php@lowstockalert");

        $this->hidden = false;
        $this->depends = array();
        $this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
        $this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

        $this->langfiles = array("lowstockalert@lowstockalert");
        $this->phpmin = array(5, 6); // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(11, -3); // Minimum version of Dolibarr required by module
        $this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
        $this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
        $this->const = array();

        /*if (!isset($conf->lowstockalert) || !isset($conf->lowstockalert->enabled)) {
                $conf->lowstockalert = new stdClass();
                $conf->lowstockalert->enabled = 0;
        }*/

        // Array to add new pages in new tabs
        $this->tabs = array();

        // Dictionaries
        $this->dictionaries = array();
        $this->boxes = array();

        // Cronjobs (List of cron jobs entries to add when module is enabled)
        // unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
        $this->cronjobs = array();

        // Permissions provided by this module
        $this->rights = array();
        $r = 0;
        // Add here entries to declare new permissions
        /* BEGIN MODULEBUILDER PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = 'Read objects of LowStockAlert'; // Permission label
        $this->rights[$r][4] = 'myobject';
        $this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->lowstockalert->myobject->read)
        $r++;
        /* END MODULEBUILDER PERMISSIONS */

        // Main menu entries to add
        $this->menu = array();
        $r = 0;
        // Add here entries to declare new menus
        //$this->menu[$r++] = array();
        /* END MODULEBUILDER TOPMENU */
        // Exports profiles provided by this module
    }

    /**
     *  Function called when module is enabled.
     *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *  It also creates data directories
     *
     *  @param      string  $options    Options when enabling module ('', 'noboxes')
     *  @return     int             	1 if OK, 0 if KO
     */
    public function init($options = '') {
        global $conf, $langs;

        $result = $this->_load_tables('/lowstockalert/sql/');
        if ($result < 0) {
            return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
        }

        // Permissions
        $this->remove($options);

        $sql = array();
        
        // Create extrafields
        include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($this->db);
        $result1 = $extrafields->addExtraField('lowstock', 'AlertWhenLow', 'boolean', 100, "","entrepot", 0, 0, true, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', true, 1, 1, false, false, 1, "lowstockalert@lowstockalert", true);
        //$result1 = $extrafields->addExtraField($_attrname,  $_label,         $_type,  $_pos, $_size, $_elementtype, $_unique, $_required, $_default_value, $_param,                           $_alwayseditable, $_perms, $_list, $_notused, $_computed, $_entity, $_langfile, $_enabled);		    

        return $this->_init($sql, $options);
    }

    /**
     *  Function called when module is disabled.
     *  Remove from database constants, boxes and permissions from Dolibarr database.
     *  Data directories are not deleted
     *
     *  @param      string	$options    Options when enabling module ('', 'noboxes')
     *  @return     int                 1 if OK, 0 if KO
     */
    public function remove($options = '') {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
