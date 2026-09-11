<?php
/* Copyright (C) 2008		Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2014	Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012		Regis Houssin 	   <regis@dolibarr.fr>
 * Copyright (C) 2013-2014	Ferran Marcet 	   <fmarcet@2byte.es>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *  \file       /reports/pre.inc.php
 *  \brief      File to manage left menu by default
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res) $res=@include("../../main.inc.php");	// For "custom" directory

/**
 *	\brief		Function called by page to show menus (top and left)
 */
function llxHeader($head = "")
{
	global $db, $user, $conf, $langs, $menumanager;
	require_once(DOL_DOCUMENT_ROOT."/core/class/menu.class.php");

	$langs->load("reports@reports");
	$langs->load("bills");
	$langs->load("companies");
	$langs->load("compta");
	$langs->load("stocks");
	$langs->load("orders");
	$langs->load("productbatch");
	$langs->load("sendings");
	$langs->load("propal");
	$langs->load("categories");
	$langs->load("contracts");
	$langs->load("interventions");
	$langs->load("projects");
	$langs->load("agenda");
	$langs->load("other");

	//top_menu($head);

	$menu = new Menu();
	
	$leftmenu=GETPOST('leftmenu','alpha');
	
	$sql = "SELECT m.rowid";
	$sql.= " FROM ".MAIN_DB_PREFIX."menu as m WHERE module = 'reports' AND entity IN (".getEntity('user').")";
	$sql.= " ORDER BY rowid DESC LIMIT 1";
	$resql = $db->query($sql);
	$objr = $db->fetch_object($resql);
	$menu_report_id = $objr->rowid;
	
	$sql = "SELECT m.rowid";
	$sql.= " FROM ".MAIN_DB_PREFIX."menu as m";
	$sql.= " ORDER BY rowid DESC LIMIT 1";
	$resql = $db->query($sql);
	$obji = $db->fetch_object($resql);
	$start_id = $obji->rowid;
	$start_id++;
	
	$sql = "SELECT rowid, code, name, active FROM ".MAIN_DB_PREFIX."reports_group";
	$resql = $db->query($sql);
	if ($resql)
	{
		$cont = count($menumanager->tabMenu);
		$numr = $db->num_rows($resql);
		$i = 0;
		while ($i < $numr)
		{
			$objp = $db->fetch_object($resql);
			if ($objp->active)
			{
				if($objp->name=='noAssigned')
					$name=$langs->trans("NoAssigned");
				else
				{
					$key=$langs->trans("group".strtoupper($objp->code));
					$namegroup=($objp->code && $key != "group".strtoupper($objp->code))?$key:$objp->name;
				}
					
				$sql2 = "SELECT code, name, xmlin FROM ".MAIN_DB_PREFIX."reports_report";
				$sql2.= " WHERE active = 1 AND fk_group=".$objp->rowid;
				//$sql2.= " AND entity =".$conf->entity;
				
				$resql2 = $db->query($sql2);
				if ($resql2)
				{
					$numg = $db->num_rows($resql2);
					$j = 0;
					
					if ($numg){
						//$menu->add('/reports/index.php?leftmenu='.$objp->name.'&amp;mainmenu=Reports', $namegroup,0,1,'',$objp->name);
						// We complete tabMenu
						$menumanager->tabMenu[$cont]['rowid']       = $start_id;
						$menumanager->tabMenu[$cont]['module']      = 'reports';
						$menumanager->tabMenu[$cont]['fk_menu']     = $menu_report_id;
						$menumanager->tabMenu[$cont]['url']         = '/reports/index.php?leftmenu='.$objp->name.'&amp;mainmenu=reports';
						$menumanager->tabMenu[$cont]['titre']       = $namegroup;
						$menumanager->tabMenu[$cont]['target']      = '';
						$menumanager->tabMenu[$cont]['mainmenu']    = 'reports';
						$menumanager->tabMenu[$cont]['leftmenu']    = '';
						$menumanager->tabMenu[$cont]['perms']       = $user->rights->reports->use;
						$menumanager->tabMenu[$cont]['enabled']     = $conf->reports->enabled;
						$menumanager->tabMenu[$cont]['type']        = 'left';
						$menumanager->tabMenu[$cont]['fk_mainmenu'] = '';
						$menumanager->tabMenu[$cont]['fk_leftmenu'] = '';
						$menumanager->tabMenu[$cont]['position']    = 100;
						$rowid_group = $start_id;
						$cont++;$start_id++;
						
					}
					while ($j < $numg)
					{
						$objr=$db->fetch_object($resql2);
						
						$name=$langs->trans($objr->name);
						$code = $objr->code;
						
						if ($user->rights->reports->$code){
							if ($leftmenu==$objp->name || $menumanager->name == 'oblyon'){
								// We complete tabMenu
								$menumanager->tabMenu[$cont]['rowid']       = $start_id;
								$menumanager->tabMenu[$cont]['module']      = 'reports';
								$menumanager->tabMenu[$cont]['fk_menu']     = $rowid_group;
								$menumanager->tabMenu[$cont]['url']         = '/reports/report.php?leftmenu='.$objp->name.'&execute_mode=PREPARE&project=Dolibarr&target_output=HTML&xmlin='.$objr->xmlin;
								$menumanager->tabMenu[$cont]['titre']       = $name;
								$menumanager->tabMenu[$cont]['target']      = '';
								$menumanager->tabMenu[$cont]['mainmenu']    = 'reports';
								$menumanager->tabMenu[$cont]['leftmenu']    = '';
								$menumanager->tabMenu[$cont]['perms']       = '$user->rights->reports->'.$objr->code;
								$menumanager->tabMenu[$cont]['enabled']     = $conf->reports->enabled;
								$menumanager->tabMenu[$cont]['type']        = 'left';
								$menumanager->tabMenu[$cont]['fk_mainmenu'] = '';
								$menumanager->tabMenu[$cont]['fk_leftmenu'] = '';
								$menumanager->tabMenu[$cont]['position']    = 100;
								$cont++;$start_id++;
							} //$menu->add('/reports/report.php?leftmenu='.$objp->name.'&execute_mode=PREPARE&project=Dolibarr&target_output=HTML&xmlin='.$objr->xmlin, $name,1);
						}
						$j++;
					}
				}	
			}
			$i++;
		}
		$menumanager->tabMenu[$cont]['rowid']       = $start_id;
		$menumanager->tabMenu[$cont]['module']      = 'reports';
		$menumanager->tabMenu[$cont]['fk_menu']     = $menu_report_id;
		$menumanager->tabMenu[$cont]['url']         = '/reports/askreport.php';
		$menumanager->tabMenu[$cont]['titre']       = $langs->trans("MoreReports");
		$menumanager->tabMenu[$cont]['target']      = '';
		$menumanager->tabMenu[$cont]['mainmenu']    = 'reports';
		$menumanager->tabMenu[$cont]['leftmenu']    = '';
		$menumanager->tabMenu[$cont]['perms']       = $user->rights->reports->use;
		$menumanager->tabMenu[$cont]['enabled']     = $conf->reports->enabled;
		$menumanager->tabMenu[$cont]['type']        = 'left';
		$menumanager->tabMenu[$cont]['fk_mainmenu'] = '';
		$menumanager->tabMenu[$cont]['fk_leftmenu'] = '';
		$menumanager->tabMenu[$cont]['position']    = 100;
		//$menu->add('/reports/askreport.php', $langs->trans("MoreReports"));
				
	}
	top_menu($head);
	$helpurl='EN:Module_Reports|FR:Module_Reports_FR|ES:M&oacute;dulo_Reports';
	left_menu($menu->liste,$helpurl);  
}
?>