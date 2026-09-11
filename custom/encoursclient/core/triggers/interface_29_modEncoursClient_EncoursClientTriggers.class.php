<?php
/* Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modEncoursClient_EncoursClientTriggers.class.php
 * \ingroup encoursclient
 * \brief   Example trigger.
 *
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
dol_include_once("/encoursclient/class/encoursclient.class.php");
dol_include_once("/gestionproduction/class/gestionproduction.class.php");

/**
 *  Class of triggers for EncoursClient module
 */
class InterfaceEncoursClientTriggers extends DolibarrTriggers
{
	/**
	 * @var DoliDB Database handler
	 */
	protected $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "crm";
		$this->description = "EncoursClient triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = '1.0.0';
		$this->picto = 'encoursclient@encoursclient';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{		
        if (empty($conf->encoursclient->enabled)) return 0;     // Module not active, we do nothing

	    // Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action

        $langs->load("other");

        switch ($action) {

		    case 'ORDER_CREATE':
   
		        dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
		        $langs->load("other");

				if (!empty($conf->gestionproduction->enabled))
				{
					$societe = new Societe($this->db);
					$societe->fetch($object->socid);
	
					if ($societe->outstanding_limit > 0)
					{
						$encoursclient = new EncoursClient($this->db);
						$amount = $encoursclient->getAmountOutstanding($societe->id);

						$gestion = new GestionProduction($this->db);
						if ($gestion->fetch(0, $object->id) == 0)
						{
							$gestion->fk_commande = $object->id;
							$result = $gestion->create($user);
						}

						$gestion->fk_cond_dep = dol_getIdFromCode($this->db, 'BC3','c_condition_depart', 'code', 'rowid');
						$gestion->update($user);

						setEventMessage($langs->trans("OrderSetInBC3Condition"), 'warnings');							
					}
						
				}

		    break;

		}

		return 0;
	}
}
