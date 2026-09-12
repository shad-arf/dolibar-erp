<?php
 /* Copyright (C) 2012-2015	Charlie BENKE	 <charlie@patas-monkey.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\defgroup   paymentreceipt	 Module paymentreceipt cards
 *	\brief	  Module to manage paymentreceipt cards
 *	\file	   htdocs/core/modules/modpaymentreceipt.class.php
 *	\ingroup	Matériels
 *	\brief	  Fichier de description et activation du module paymentreceipt
 */

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 *	\class	  modpaymentreceipt
 *	\brief	  Classe de description et activation du module paymentreceipt
 */
class modpaymentreceipt extends DolibarrModules
{
	/**
	*   Constructor. Define names, constants, directories, boxes, permissions
	*
	*   @param	  DoliDB		$db	  Database handler
	*/
	function modpaymentreceipt($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 161060;
		$this->rights_class = 'paymentreceipt';//nécessaire pour compatibilité avec gma

		$this->family = "Ab1 Consulting";
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des documents de type recus de paiement";

		$this->version = '2.2';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
        $this->picto = 'product';

		// Dependencies
		$this->depends = array("modFacture");
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array("paymentreceipt@paymentreceipt");

		// Config pages
		$this->config_page_url = array("setup.php@paymentreceipt");

		$this->module_parts = array(
            'models' => 1,
            'js' => 'paymentreceipt/js/paymentreceipt.js.php'
        );
        $this->menu=array();

        // Constantes
		$this->const = array();
        $r=0;
		$this->const[$r][0] = "PAYMENT_RECEIPT_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "orge";
		$this->const[$r][4] = 1;

        // Permissions
        $this->rights = array();
        $r = 0;

        $r++;
        $this->rights[$r][0] = 51201;
        $this->rights[$r][1] = 'lire les pdf';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'lire';

        // Additionnals paymentreceipt tabs in other modules
        $this->tabs = array(
				'payment:+paymentreceipt:paymentreceipt:paymentreceipt@paymentreceipt:$user->rights->paymentreceipt->lire:/paymentreceipt/tabs/paymentreceipt.php?id=__ID__'
			);

	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
	 *	  @param	  string	$options	Options when enabling module ('', 'noboxes')
	 *	  @return	 int			 	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		global $conf;
		// Permissions
		$this->remove($options);

		$sql = array();

		$result=$this->load_tables();

		return $this->_init($sql,$options);
	}

	/**
	 *		Function called when module is disabled.
	 *	  Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
	 *	  @param	  string	$options	Options when enabling module ('', 'noboxes')
	 *	  @return	 int			 	1 if OK, 0 if KO
	 */
	function remove($options='')
	{
		$sql = array();
		return $this->_remove($sql,$options);
	}

	/**
	 *		Create tables, keys and data required by module
	 * 		Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * 		and create data commands must be stored in directory /mymodule/sql/
	 *		This function is called by this->init.
	 *
	 * 		@return		int		<=0 if KO, >0 if OK
	 */
	function load_tables()
	{
		return $this->_load_tables('');
	}
}
?>
