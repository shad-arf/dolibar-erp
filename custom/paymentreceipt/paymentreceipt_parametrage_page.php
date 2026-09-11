<?php

/* Copyright (C) 2014 	Abbes Bahfir 	<bafbes@gmail.com>
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

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");		// For root directory
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");	// For "custom" directory
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");
require_once("functions.inc.php");

$modname = 'paymentreceipt';
//Module constants files when parameterizing module is disabled
$backlink = GETPOST('backlink');
switch ($backlink) {
    case '/paymentreceipt/tabs/paymentreceipt.php':
    case '/paymentreceipt/admin/setup.php':
        $params = array('PAYMENTRECEIPT'); //array of parameter sections to include
        break;
    default :
        $params = array();
}
$outdef = array(); //array of parameters to exclude
$indef = array(); // array of parameters to include within the 'XXX' section
include 'parametrage_page.inc.php';
