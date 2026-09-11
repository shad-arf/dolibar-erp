<?php
/* Copyright (C) 2001-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012 	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2016	 	Ferran Marcet			<fmarcet@2byte.es>
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
 *	\file       htdocs/index.php
 *	\brief      Dolibarr home page
 */

define('NOCSRFCHECK',1);	// This is login page. We must be able to go on it from another web site.


require("./pre.inc.php");
global $db, $user, $conf, $langs;

llxHeader('');

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

//***********************
// List
//***********************
print load_fiche_titre($langs->trans("ReportsArea"));

//print $langs->trans("Module400005Desc")."<br>";


$sql = "SELECT rowid, code, name, active FROM ".MAIN_DB_PREFIX."reports_group";
$resql = $db->query($sql);

if ($resql)
{

    $numr = $db->num_rows($resql);
    $i = 0;
    while ($i < $numr)
    {
        $objp = $db->fetch_object($resql);
        if ($objp->active){

            if($objp->name==='noAssigned')
                $name=$langs->trans('NoAssigned');
            else
            {
                $key=$langs->trans("group".strtoupper($objp->code));
                $namegroup=($objp->code && $key != "group".strtoupper($objp->code))?$key:$objp->name;
            }

            if($objp->code !== '0000'){
                print '<div class="float tabBar tabactive"><div><a class="tab" href="'.dol_buildpath('/reports/index.php',1).'?leftmenu='.$objp->name.'&amp;mainmenu=reports">'.$namegroup.'</a></div>';

                $sql2 = "SELECT code, name, xmlin FROM ".MAIN_DB_PREFIX."reports_report";
                $sql2.= " WHERE active = 1 AND fk_group=".$objp->rowid;
                //$sql2.= " AND entity =".$conf->entity;

                $resql2 = $db->query($sql2);
                if ($resql2)
                {
                    $numg = $db->num_rows($resql2);
                    $j = 0;
                    while ($j < $numg) {
                        $objr = $db->fetch_object($resql2);

                        $key=$langs->trans("report".strtoupper($objr->code));
                        $name=$langs->trans($objr->name);
                        $code = $objr->code;
                        if ($user->rights->reports->$code) {
                            print '<div><a class="vsmenu" href="' . dol_buildpath('/reports/report.php',
                                    1) . '?leftmenu=' . $objp->name . '&execute_mode=PREPARE&project=Dolibarr&target_output=HTML&xmlin=' . $objr->xmlin . '">' . $name . '</a></div>';
                        }
                        $j++;
                    }

                }
                print '</div>';
            }

        }
        $i++;

    }

}


llxFooter();
