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
 *  \file       htdocs/encoursclient/admin/setup.php
 *  \ingroup    encoursclient
 *  \brief      Admin page
 */


$res=@include("../../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");    // For "custom" directory

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
dol_include_once("/encoursclient/lib/encoursclient.lib.php");
dol_include_once("/encoursclient/class/setup_encoursclient.class.php");

// Translations
$langs->load("encoursclient@encoursclient");

// Access control
if (! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$reg = array();

/*
 * Actions
 */


if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	$value=(GETPOST($code) ? GETPOST($code) : 1);
	if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

else if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, $conf->entity) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if($action=='updatesirenfromsiret'){

	$setup =new SetupEncoursClient($db);
	$setup->update_siren_from_siret();
}

/*
 * View
 */

llxHeader('', $langs->trans('EncoursClientSetup'));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans('EncoursClientSetup'), $linkback);

// Configuration header
$head = encoursclient_prepare_admin_head();
dol_fiche_head(
	$head,
	'settings',
	$langs->trans("ModuleEncoursClientName"),
	0,
	"encoursclient@encoursclient"
);

// Setup page goes here
echo $langs->trans("EncoursClientSetupPage");

print load_fiche_titre($langs->trans("EncoursClientOptions"),'','');



print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center">'.$langs->trans("Action").'</td>';
print "</tr>\n";

//Vérifier l'encours entre les entités multicompagny
if($conf->global->MAIN_MODULE_MULTICOMPANY){
	print '<tr class="oddeven">';
	print '<td>';
	print $langs->trans('DescTAKE_ALL_ENTITY_IN_OUTSTANDING');
	print '</td>';
	print '<td>';
	if ($conf->use_javascript_ajax)
	{
		print ajax_constantonoff('TAKE_ALL_ENTITY_IN_OUTSTANDING');
	}
	else
	{
		if (empty($conf->global->TAKE_ALL_ENTITY_IN_OUTSTANDING))
		{
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_TAKE_ALL_ENTITY_IN_OUTSTANDING">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
		}
		else
		{
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_DISABLE_ORDER_CREATION_WHEN_OUTSTAND">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
		}
	}
	print '</td>';
	print '</tr>';
}

//Utiliser l'encours sur le siren (id prof. 1)
	print '<tr class="oddeven">';
	print '<td>';
	print $langs->trans('DescUSE_IDPROF1_FOR_OUTSTANDING');
	print '</td>';
	print '<td>';
	if ($conf->use_javascript_ajax)
	{
		print ajax_constantonoff('USE_IDPROF1_FOR_OUTSTANDING');
	}
	else
	{
		if (empty($conf->global->USE_IDPROF1_FOR_OUTSTANDING))
		{
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_USE_IDPROF1_FOR_OUTSTANDING">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
		}
		else
		{
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_USE_IDPROF1_FOR_OUTSTANDING">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
		}
	}
	print '</td>';
	print '</tr>';

//Calculer l'encours sur le TTC
	print '<tr class="oddeven">';
	print '<td>';
	print $langs->trans('DescUSE_TTC_FOR_OUTSTANDING');
	print '</td>';
	print '<td>';
	if ($conf->use_javascript_ajax)
	{
		print ajax_constantonoff('USE_TTC_FOR_OUTSTANDING');
	}
	else
	{
		if (empty($conf->global->USE_TTC_FOR_OUTSTANDING))
		{
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_USE_TTC_FOR_OUTSTANDING">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
		}
		else
		{
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_USE_TTC_FOR_OUTSTANDING">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
		}
	}
	print '</td>';
	print '</tr>';



//desactiver la creation des commandes
print '<tr class="oddeven">';
print '<td>';
print $langs->trans('DescDISABLE_ORDER_CREATION_WHEN_OUTSTAND');
print '</td>';
print '<td>';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('DISABLE_ORDER_CREATION_WHEN_OUTSTAND');
}
else
{
	if (empty($conf->global->DISABLE_ORDER_CREATION_WHEN_OUTSTAND))
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_DISABLE_ORDER_CREATION_WHEN_OUTSTAND">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
	}
	else
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_DISABLE_ORDER_CREATION_WHEN_OUTSTAND">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
	}
}
print '</td>';
print '</tr>';
print '</table>';


print load_fiche_titre($langs->trans("EncoursClientActions"),'','');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center">'.$langs->trans("Action").'</td>';
print "</tr>\n";

//siren depuis siret
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("EnCoursSirenFromSiret").'</td>';
print '<td align="center"><a href="'.$_SERVER['PHP_SELF'].'?action=updatesirenfromsiret" class="button">'.$langs->trans("EnCoursUpdateSirenFromSiret").'</a></td>';
print "</tr>\n";

print '</table>';



// Page end
dol_fiche_end();
llxFooter();
