<?php

	/**
	 * Return total amount total_ttc and total_ht of unbilled orders
	 * @param $refs = array('id'=>'ref') List of orders ids and refs
	 */
	function getTotalOrdersUnbilled($refs){
		global $db;
		dol_include_once('/commande/class/commande.class.php');
		$objc = new Commande($db);
		$total_ttc = $total_ht = 0;
		foreach($refs as $id=>$ref){
			$objc->fetch($id);
			if($objc->billed == 0){
				$total_ht += $objc->total_ht;
				$total_ttc += $objc->total_ttc;
			}
		}
		return array('total_ttc'=>$total_ttc, 'total_ht'=>$total_ht);
	}

	/**
	 * Return list of payements on customer invoice
	 * @param $id = int id of invoice to check
	 */
	function getPaymentsOnInvoice($id){
		global $db;
		$paymentsList = [];
		
		$sql = 'SELECT p.datep as dp, p.ref, p.num_paiement as num_payment, p.rowid, p.fk_bank,';
		$sql .= ' c.code as payment_code, c.libelle as payment_label,';
		$sql .= ' pf.amount,';
		$sql .= ' ba.rowid as baid, ba.ref as baref, ba.label, ba.number as banumber, ba.account_number, ba.fk_accountancy_journal';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf, '.MAIN_DB_PREFIX.'paiement as p';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as c ON p.fk_paiement = c.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON b.fk_account = ba.rowid';
		$sql .= ' WHERE pf.fk_facture = '.$id.' AND pf.fk_paiement = p.rowid';
		$sql .= ' AND p.entity IN ('.getEntity('invoice').')';
		$sql .= ' ORDER BY p.datep, p.tms';

		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);
			$i = 0;

			if ($num > 0) {
				while ($i < $num) {
					$objp = $db->fetch_object($result);
					$paymentsList[] = $objp;
					$i++;
				}
			}
		}
		return $paymentsList;
	}
?>