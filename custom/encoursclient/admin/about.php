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
 *  \file       htdocs/encoursclient/index.php
 *  \ingroup    encoursclient
 *  \brief      Page to show product set
 */


$res=@include("../../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");    // For "custom" directory


// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

dol_include_once("/encoursclient/lib/encoursclient.lib.php");

// Translations
$langs->load("encoursclient@encoursclient");

// Translations
$langs->load("errors");
$langs->load("admin");
$langs->load("other");

// Access control
if (! $user->admin) {
	accessforbidden();
}

$versions = array(
	array('version' => '1.0.0', 'date' => '25/01/2019', 'updates' => $langs->trans('EncoursClientFirstVersion')),
	array('version' => '1.0.1', 'date' => '06/01/2021', 'updates' => $langs->trans('20210106Update')),
	array('version' => '1.0.2', 'date' => '15/03/2022', 'updates' => $langs->trans('20220315Update')),
	array('version' => '1.0.3', 'date' => '15/04/2022', 'updates' => $langs->trans('20220415Update')),
	array('version' => '1.0.4', 'date' => '26/04/2022', 'updates' => $langs->trans('20220426Update')),
	array('version' => '1.0.5', 'date' => '28/04/2022', 'updates' => $langs->trans('20220428Update')),
);
/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans('EncoursClientAbout'));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'. $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans('EncoursClientAbout'), $linkback);

// Configuration header
$head = encoursclient_prepare_admin_head();
dol_fiche_head(
	$head,
	'about',
	$langs->trans("ModuleEncoursClientName"),
	0,
	'encoursclient@encoursclient'
);

// About page goes here
echo $langs->trans("EncoursClientAboutPage");

echo '<br />';

$url = 'http://www.iouston.com/systeme-gestion-entreprise-dolibarr/modules-dolibarr/module-dolibarr-encours-client';

print '<h2>'.$langs->trans("About").'</h2>';
print $langs->trans("EncoursClientAboutDescLong", $url, $url);

print '<h2>'.$langs->trans("MaintenanceAndSupportTitle").'</h2>';
print $langs->trans("MaintenanceAndSupportDescLong");

print '<h2>'.$langs->trans("UpdateTitle").'</h2>';
print $langs->trans("UpdateDescLong");

print '<h2>'.$langs->trans("ModulesTitle").'</h2>';
print $langs->trans("ModulesDescLong");

echo '<br />';

print '<a href="http://www.dolistore.com">'.img_picto('dolistore', dol_buildpath('/encoursclient/img/dolistore.png', 1), '', 1).'</a>';

print '<hr />';

print '<a href="http://www.iouston.com">'.img_picto('iouston', dol_buildpath('/encoursclient/img/iouston.png', 1), '', 1).'</a>';

echo '<br />';

print $langs->trans("IoustonDesc");

print '<hr />';
print '<h2>'.$langs->trans("ChangeLog").'</h2>';


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("ChnageLogVersion").'</td>';
print '<td>'.$langs->trans("ChnageLogDate").'</td>';
print '<td>'.$langs->trans("ChnageLogUpdates").'</td>';
print "</tr>\n";

foreach ($versions as $version)
{
	print '<tr class="oddeven">';
	print '<td>';
	print $version['version'];
	print '</td>';
	print '<td>';
	print $version['date'];
	print '</td>';
	print '<td>';
	print $version['updates'];
	print '</td>';
	print '</tr>';
}


print '</table>';

// Page end
dol_fiche_end();
llxFooter();
$db->close();
