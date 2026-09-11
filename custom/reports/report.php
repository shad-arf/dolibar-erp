<?php
/* Copyright (C) 2011-2012 Juanjo Menent <jmenent@2byte.es>
 * Copyright (C) 2012 	   Regis Houssin <regis@dolibarr.fr>
 * Copyright (C) 2013-2014 Ferran Marcet <fmarcet@2byte.es>
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
 *
 */

/**
 *     \file       /reports/report.php
 *     \ingroup    2report
 *     \brief      Page to launch a report
 *     \author	   Juanjo Menent
 */


require("./pre.inc.php");
require_once("./includes/reportico/reportico.php");
require_once ("./lib/reports.lib.php");

global $user, $langs, $db;
$langs->load("reports@reports");

$mode = GETPOST("execute_mode");
$report = GETPOST("xmlin");
$leftmenu = GETPOST("leftmenu");

/**********************************************************/
/*   Populate llx_reports_config before loading reports   */
/**********************************************************/


// Populate pricelevel in llx_reports_config
populate_pricelevel_info();

$helpurl='EN:Module_Reports|FR:Module_Reports_FR|ES:M&oacute;dulo_Reports';
llxHeader('','',$helpurl);

$a = new reportico();
$a->embedded_report = true;
$a->allow_maintain = "FULL";
$a->forward_url_get_parameters = "leftmenu=".$leftmenu."&execute_mode=".$mode."&project=Dolibarr&xmlin=".$report;
$_SESSION['reportico']['forward_url_get_parameters']= "leftmenu=".$leftmenu."&execute_mode=".$mode."&project=Dolibarr&xmlin=".$report;
$_SESSION['reportico']['admin_password'] = $user->rights->reports->admin;
$a->execute();

llxFooter();
$db->close();

?>
