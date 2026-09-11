<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
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
 *  \file       htdocs/encoursclient/class/actions_encoursclient.class.php
 *  \ingroup    encoursclient
 *  \brief      File of class to manage actions on propal
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
dol_include_once("/encoursclient/class/encoursclient.class.php");


class ActionsEncoursClient
{ 
	
	function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $db, $mysoc, $conf;
		
		$langs->load('encoursclient@encoursclient');
		$result = 0;

		if ($object->element == 'commande' && ($action == 'create' || $action == 'add'))
		{
			$socid = $parameters['socid'];

			if ($socid > 0)
			{
				$societe = new Societe($db);
				$societe->fetch($socid);

				$encoursclient = new EncoursClient($db);
				$amount = $encoursclient->getAmountOutstanding($societe->id);

				if ($amount > $societe->outstanding_limit && !empty($societe->outstanding_limit) && !empty($conf->global->DISABLE_ORDER_CREATION_WHEN_OUTSTAND))
				{	
					$url = DOL_URL_ROOT.'/comm/card.php?socid='.$societe->id;
					setEventMessage($langs->trans("OrderCannotBeCreated"), 'errors');
					header('Location: '.$url);
					exit;
				}				
			}		
		}

		$this->resprints = '';
		return 0;			
	}

	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $db, $mysoc, $conf, $user;
		
		$langs->load('encoursclient@encoursclient');

		if ($object->element == 'societe')
		{
			$encoursclient = new EncoursClient($db);
			
		$amount = $encoursclient->getAmountOutstanding($object->id,$object->idprof1);
		
		if($conf->global->USE_IDPROF1_FOR_OUTSTANDING && !empty($object->parent)){
		$amountlimit= $encoursclient->getOutstandingLimit($object->parent);		
		}else{
		$amountlimit= $encoursclient->getOutstandingLimit($object->id);	
		}
			
			
			$warn = '';
			if ($amount > $amountlimit)
			{
				$warn = ' '.img_warning($langs->trans("OutstandingBillReached"));
			}
	
			$html = '<a href="'.dol_buildpath('encoursclient/client.php?socid='.$object->id, 2).'" class="boxstatsindicator thumbstat nobold nounderline">';
			$html.='<div class="boxstats">';
			$html.='<span class="boxstatstext">'.img_object("", 'bill').' '.$langs->trans("CurrentOutstandingBill").'</span><br />';
			$html.='<span class="boxstatsindicator'.($amount > 0 ?' amountremaintopay': '').'">'.price($amount, 1, $langs, 1, -1, -1, $conf->currency).$warn.'</span>';
			$html.='</div>';
			$html.='</a>';
	
			print '<script type="text/javascript">'."\r\n";
			print '$(document).ready(function() {'."\r\n";
			print '		$("a.boxstatsindicator").each(function(){'."\r\n";
			print '			var url = $(this).attr("href");'."\r\n";	
			print '			if (url) {'."\r\n";
			print '				if (url.indexOf("recap-compta") >= 0) {'."\r\n";
			print '					$(this).remove();'."\r\n";	
			print '				}'."\r\n";
			print '			}'."\r\n";
			print '		});'."\r\n";
			print '		var box = $( "'.addslashes($html).'" );'."\r\n";
			print '		$("td.tdboxstats").append(box);'."\r\n";
			print '});'."\r\n";
			print '</script>'."\r\n";
		}

		if ($object->element == 'commande' || $object->element == 'propal')
		{
			$societe = new Societe($db);
			$societe->fetch($object->socid);

			$encoursclient = new EncoursClient($db);
			$amount = $encoursclient->getAmountOutstanding($societe->id);
	
			$search = price($societe->outstanding_limit, 0, '', 1, - 1, - 1, $conf->currency);

			$html = price($amount) . ' / ';
			$html.= price($societe->outstanding_limit, 0, '', 1, - 1, - 1, $conf->currency);	

			print '<script type="text/javascript">'."\r\n";
			print '$(document).ready(function() {'."\r\n";
			print '		$(".fichehalfleft > table > tbody > tr").each(function(){'."\r\n";
			print '			if ($(this).find("td:nth-child(2)").html().indexOf("'.$search.'") >= 0) {'."\r\n";
			print '				$(this).find("td:nth-child(2)").html("'.addslashes($html).'");'."\r\n";
			print '			}'."\r\n";
			print '		});'."\r\n";
			print '});'."\r\n";
			print '</script>'."\r\n";
		}

		$this->resprints = '';
		return 0;		
	}

}


