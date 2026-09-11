<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2016      Bahfir Abbes           <dolipar@dolipar.org>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	\file       htdocs/paymentreceipt/core/modules/paymentreceipt/modules_paymentreceipt.php
 *	\ingroup    paymentreceipt
 *	\brief      Fichier contenant la classe mere de generation des paiements en PDF
 */


require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';   // Requis car utilise dans les classes qui heritent


/**
 *	\class      ModelePayment_receipt
 *	\brief      Classe mere des modeles de reçus de paiement
 */
class ModelePayment_receipt extends CommonDocGenerator
{
	var $error='';

	/**
	 *  Return list of active generation modules
	 * 	@param		$db		Database handler
	 */
	function liste_modeles($db)
	{
		global $conf;

		$type='paymentreceipt';
		$liste=array();

		include_once(DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php');
		$liste=getListOfModels($db,$type,'');

		return $liste;
	}
}


/**
 *	Cree une paiement sur le disque en fonction du modele de PAYMENT_RECEIPT_ADDON_PDF
 *	@param   	db  			objet base de donnees
 *	@param   	object			Object invoice
 *	@param	    message			message
 *	@param	    modele			force le modele a utiliser ('' to not force)
 *	@param		outputlangs		objet lang a utiliser pour traduction
 *  @param      hidedetails     Hide details of lines
 *  @param      hidedesc        Hide description
 *  @param      hideref         Hide ref
 *	@return  	int        		<0 if KO, >0 if OK
 */
function paiement_pdf_create($db, $object, $message, $modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0)
{
	global $conf,$user,$langs;
	
	$langs->load("bills");

	// Increase limit for PDF build
    $err=error_reporting();
    error_reporting(0);
    @set_time_limit(120);
    error_reporting($err);

	$dir = "/paymentreceipt/core/modules/paymentreceipt/";
    $srctemplatepath='';

	// Positionne le modele sur le nom du modele a utiliser
	if (! dol_strlen($modele))
	{
		if (! empty($conf->global->PAYMENT_RECEIPT_ADDON_PDF))
		{
			$modele = $conf->global->PAYMENT_RECEIPT_ADDON_PDF;
		}
		else
		{
			//print $langs->trans("Error")." ".$langs->trans("Error_FACTURE_ADDON_PDF_NotDefined");
			//return 0;
			$modele = 'orge';
		}
	}

    // If selected modele is a filename template (then $modele="modelname:filename")
	$tmp=explode(':',$modele,2);
    if (! empty($tmp[1]))
    {
        $modele=$tmp[0];
        $srctemplatepath=$tmp[1];
    }

	// Search template file
	$file=''; $classname=''; $filefound=0;
	foreach(array('doc','pdf') as $prefix)
	{
        $file = $prefix."_".$modele.".modules.php";
        
        // On verifie l'emplacement du modele
        $file = dol_buildpath($dir.'doc/'.$file);
	    
        if (file_exists($file))
	    {
	        $filefound=1;
	        $classname=$prefix.'_'.$modele;
	        break;
	    }
	}

	// Charge le modele
	if ($filefound)
	{
		require_once($file);

		$obj = new $classname($db);
		$obj->message = $message;

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($object, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc) > 0)
		{

			$outputlangs->charset_output=$sav_charset_output;
			
			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
			$interface=new Interfaces($db);
			$result=$interface->run_triggers('BILL_BUILDDOC',$object,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers
			
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_print_error($db,"paiement_pdf_create Error: ".$obj->error);
			return -1;
		}

	}
	else
	{
		dol_print_error('',$langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$dir.$file));
		return -1;
	}
}

?>
