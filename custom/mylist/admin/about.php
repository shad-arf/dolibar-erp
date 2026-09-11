<?php
/* Copyright (C) 2014-2021	  Charlene BENKE	 <charlene@patas-monkey.com>
 *
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
 * 	\file		htdocs/mylist/admin/about.php
 * 	\ingroup	mylist
 * 	\brief		about page
 */

// Dolibarr environment
$res=0;
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php"))
	$res=@include("../../../main.inc.php");	// For "custom" directory

// Libraries
dol_include_once("/mylist/core/lib/mylist.lib.php");
dol_include_once("/mylist/core/lib/patasmonkey.lib.php");


// Translations
$langs->load("mylist@mylist");

// Access control
if (!$user->admin)
	accessforbidden();

/*
 * View
 */
$page_name = $langs->trans("MylistSetup") ." - " . $langs->trans("About");
$help_url='https://wiki.patas-monkey.com/index.php?title=MyList#Configuration_des_fonctionnalit.C3.A9s_du_module';
llxHeader('', $page_name, $help_url);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($page_name, $linkback, 'title_setup');

// Configuration header
$head = mylist_admin_prepare_head();
dol_fiche_head($head, 'about', $langs->trans("MyList"), -1, "mylist.png@mylist");

print getChangeLog('mylist');

llxFooter();
$db->close();