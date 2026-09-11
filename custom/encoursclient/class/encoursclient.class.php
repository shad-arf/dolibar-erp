<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
 * Copyright (C) 2022 Julien Marchand <julien.marchand@iouston.com>
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
 */

/**
 *  \file       htdocs/encoursclient/class/encoursclient.class.php
 *  \ingroup    encoursclient
 *  \brief      File of class to manage predefined products sets
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

/**
 * Class to manage products or services
 */
class EncoursClient extends CommonObject
{
	public $element='encoursclient';
	public $table_element='';
	public $fk_element='';
	public $picto = 'generic';
	public $ismultientitymanaged = 0;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	/**
	 * {@inheritdoc}
	 */
	protected $table_ref_field = '';

	/**
     * Thirdparty id
     * @var int
     */
	public $id = 0;

	/**
     * Invoices
     * @var array
     */
	public $invoices = array();

	/**
     * Orders
     * @var array
     */
	public $orders = array();

	/**
     * Discount
     * @var float
     */
	public $discounts = array();

	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $langs;

		$this->db = $db;
	}

	/**
	 *  Load invoices/orders/discount from database
	 *
	 *  @param	int		$id      			Id of client
	 *  @param	int		$siren      		Siren of client
	 *  @return int     					<0 if KO, 0 if not found, >0 if OK
	 */
	function fetch($id = 0, $siren =0)
	{
		global $langs, $conf;

		if($conf->global->TAKE_ALL_ENTITY_IN_OUTSTANDING && $conf->global->MAIN_MODULE_MULTICOMPANY){
		$all_entity=1;	
		}else{
		unset($all_entity);	
		}
		//si on se base sur l'utilisation du siren
		if($conf->global->USE_IDPROF1_FOR_OUTSTANDING){$usesiren=1;}else{$usesiren=0;}

		dol_syslog(get_class($this)."::fetch id=".$id);

		// Check parameters
		if (! $id)
		{
			$this->error='ErrorWrongParameters id needed';
			return -1;
		}
		$this->id = $id;
		
		if($usesiren==1 && (!$siren || $siren==0)){
			$this->error='ErrorWrongParameters siren needed';
			return -1;
		}
		$this->siren = $siren;
		
		$this->invoices = array();
		$this->orders = array();
		$this->discounts = array();

		// SELECT f.fk_soc, SUM(f.total), SUM(f.total_ttc), SUM(pf.amount), SUM(sr.amount_ttc) FROM `llx_facture` f LEFT JOIN `llx_paiement_facture` pf ON f.rowid = pf.fk_facture LEFT JOIN `llx_societe_remise_except` sr ON sr.fk_facture = f.rowid WHERE 1 GROUP BY f.fk_soc ORDER BY f.fk_soc
		
		// Factures
		if ((float) DOL_VERSION < 10.0) {
			$sql = "SELECT f.rowid as id, f.facnumber as ref, f.total as total_ht, f.total_ttc, f.paye as billed, f.fk_statut, f.datec, e.label as entite ";
		}elseif ((float) DOL_VERSION < 14.0) {
			$sql = "SELECT f.rowid as id, f.ref as ref, f.total as total_ht, f.total_ttc, f.paye as billed, f.fk_statut, f.datec, e.label as entite ";	
		}else{
			$sql = "SELECT f.rowid as id, f.ref as ref, f.total_ht as total_ht, f.total_ttc, f.paye as billed, f.fk_statut, f.datec, e.label as entite ";
		}
		if($usesiren==1){
			$sql .= " ,s.siren as siren, s.nom";	
		}
		$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entity as e on e.rowid=f.entity";
		if($usesiren==1){
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid=f.fk_soc";
		}
		if($usesiren==1){
			$sql.= " WHERE s.siren = ". $this->siren;
		}else{
			$sql.= " WHERE fk_soc = ". $this->id;
		}
		$sql.= " AND fk_statut = ".Facture::STATUS_VALIDATED." AND paye = 0";
		if(!isset($all_entity)){
		$sql.= " AND entity IN (".getEntity('facture').")";
		}

		$resql = $this->db->query($sql);
		if ( $resql )
		{
			if ($this->db->num_rows($resql) > 0)
			{
				while ($obj = $this->db->fetch_object($resql))
				{
					$this->invoices[] = $obj;
				}

				$this->db->free($resql);
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}

		// Commandes
		$sql = "SELECT c.rowid as id, c.ref, c.total_ht, c.total_ttc, c.facture as billed, c.fk_statut, c.date_creation as datec, e.label as entite ";
		if($usesiren==1){
			$sql .= " ,s.siren as siren, s.nom";	
		}
		$sql.= " FROM ".MAIN_DB_PREFIX."commande as c";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entity as e on e.rowid=c.entity";
		if($usesiren==1){
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid=c.fk_soc";
		}
		if($usesiren==1){
			$sql.= " WHERE s.siren = ". $this->siren;
		}else{
			$sql.= " WHERE fk_soc = ". $this->id;
		}
		$sql.= " AND facture = 0 AND fk_statut IN (".Commande::STATUS_VALIDATED.",".Commande::STATUS_ACCEPTED.",".Commande::STATUS_SHIPMENTONPROCESS.")";
		if(!isset($all_entity)){
		$sql.= " AND entity IN (".getEntity('commande').")";
		}
		$resql = $this->db->query($sql);
		if ( $resql )
		{
			if ($this->db->num_rows($resql) > 0)
			{
				while ($obj = $this->db->fetch_object($resql))
				{
					$this->orders[] = $obj;
				}

				$this->db->free($resql);
			}
		}
		else
		{
			return -1;
		}

		// Remises
		$sql = "SELECT sre.rowid as id, sre.amount_ttc, sre.datec, e.label as entite";
		if($usesiren==1){
			$sql .= " ,s.siren as siren, s.nom";	
		}
		$sql.= " FROM ".MAIN_DB_PREFIX."societe_remise_except as sre";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entity as e on e.rowid=sre.entity";
		if($usesiren==1){
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid=sre.fk_soc";
		}
		if($usesiren==1){
			$sql.= " WHERE s.siren = ". $this->siren;
		}else{
			$sql.= " WHERE sre.fk_soc = ". $this->id;
		}
		$sql.= " AND (sre.fk_facture IS NULL AND sre.fk_facture_line IS NULL)";	// Available
		if(!isset($all_entity)){
		$sql.= " AND entity IN (".getEntity('commande').")";
		}

		$resql = $this->db->query($sql);
		if ( $resql )
		{
			if ($this->db->num_rows($resql) > 0)
			{
				while ($obj = $this->db->fetch_object($resql))
				{
					$this->discounts[] = $obj;
				}

				$this->db->free($resql);
			}
		}
		else
		{
			return -1;
		}

		return 1;
	}


	function getAmountOutstanding($id = 0, $siren = 0)
	{
		$this->fetch($id,$siren);

		$invoices = $this->invoices;
		$orders = $this->orders;
		$discounts = $this->discounts;

		$amount = 0;

		if (sizeof($invoices))
		{
			foreach ($invoices as $invoice)
			{
				$staticinvoice = new Facture($this->db);
				$staticinvoice->id = $invoice->id;

				$paiement = $staticinvoice->getSommePaiement();
				$creditnotes = $staticinvoice->getSumCreditNotesUsed();
				$deposits = $staticinvoice->getSumDepositsUsed();
				$amount += $invoice->total_ttc - $paiement - $creditnotes - $deposits;
			}
		}

		if (sizeof($orders))
		{
			foreach ($orders as $order)
			{
				$staticorder = new Commande($this->db);
				$staticorder->id = $order->id;

				$amount += $order->total_ttc;
			}
		}

		if (sizeof($discounts))
		{
			foreach ($discounts as $discount)
			{
				$amount -= $discount->amount_ttc;
			}
		}

		return $amount;
	}

	/**
	 * return the outstanding limit for a societe
	 * @param  int $id rowid of societe
	 * @return value of outstandinglimit
	 */
	function getOutstandingLimit($id){
		$sql = "SELECT s.rowid, s.outstanding_limit ";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql.= " WHERE s.rowid =".$id;

		$resql = $this->db->query($sql);
		if ( $resql )
		{
			if ($this->db->num_rows($resql) > 0)
			{
				while ($obj = $this->db->fetch_object($resql))
				{
					$outstandinglimit = $obj->outstanding_limit;
				}

				$this->db->free($resql);
			}
		return $outstandinglimit;
		}
		else
		{
			return -1;
		}
	}

	function getNumberSocieteForThisSiren($siren){
		$sql = "SELECT COUNT(rowid) as nbsociete";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql.= " WHERE s.siren =".$siren;

		$resql = $this->db->query($sql);
		if ( $resql )
		{
			if ($this->db->num_rows($resql) > 0)
			{
				$obj = $this->db->fetch_object($resql);
			}
		}
		
		return $obj->nbsociete;
		
	}
}

