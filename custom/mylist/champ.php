<?php
/* Copyright (C) 2013-2022	Charlene BENKE		<charlene@patas-monkey.com>
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
 *	\file	   htdocs/mylist/champ.php
 *	\ingroup	mylist
 *	\brief	  Page of a list fields
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory

dol_include_once('/mylist/class/mylist.class.php');

require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbarcode.class.php';

$langs->load("mylist@mylist");

$mylistid 	= GETPOST("mylistid", 'int');
$rowid 		= GETPOST("rowid", 'int');
$action 	= GETPOST("action", 'string');


// Security check
$socid=0;
if (! $user->rights->mylist->lire) accessforbidden();

$object = new MyList($db);
$ret=$object->fetch($mylistid);
$object->getChampsArray();

/*
 * Actions
 */

if ($action == 'edit' && $user->rights->mylist->creer) {
	$error=0;
	if (empty($_POST["nameField"])) {
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentities("Name")).'</div>';
	}

	if (! $error) {
		$object->idfield	= $rowid;
		$object->name		= GETPOST("nameField");
		$object->field		= GETPOST("field");
		$object->alias		= GETPOST("alias");
		$object->type		= GETPOST("type");
		$object->param		= GETPOST("param");
		$object->alias		= GETPOST("alias");
		$object->align		= GETPOST("align");
		$object->enabled	= GETPOST("enabled");
		$object->visible	= GETPOST("visible");
		$object->filter		= GETPOST("filter");
		$object->width		= GETPOST("width");
		$object->widthpdf	= GETPOST("widthpdf");
		
		$object->sumreport	= GETPOST("sumreport");
		$object->pctreport	= GETPOST("pctreport");
		$object->avgreport	= GETPOST("avgreport");

		$object->cumreport	= GETPOST("cumreport");
		$object->cumpctreport	= GETPOST("cumpctreport");

		$object->filterinit	= GETPOST("filterinit");
		$object->barcode	= GETPOST("barcode");

		if ($object->updateField($user, $rowid) == 1)
			$mesg = '<div class="ok">'.$langs->trans('UpdateSucceeded').'</div>';
		else {
			$error++;
			$mesg='<div class="error">'.$langs->trans("SQLError").':'.$object->error.'</div>';
		}
	}
	$action='';
}

if ($action == 'add' && ! GETPOST("cancel") && $user->rights->mylist->creer) {
	$error=0;

	if (empty(GETPOST("field","alpha"))) {
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentities("fieldName")).'</div>';
	}

	if (empty(GETPOST("nameField","alpha"))) {
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentities("Name")).'</div>';
	}
	if (! $error) {
		$object->idfield	= $mylistid;
		$object->name		= GETPOST("nameField","alpha");
		$object->field		= GETPOST("field");
		$object->alias		= GETPOST("alias");
		$object->type		= GETPOST("type");
		$object->param		= GETPOST("param");
		$object->alias		= GETPOST("alias");
		$object->align		= GETPOST("align");
		$object->enabled	= GETPOST("enabled");
		$object->visible	= GETPOST("visible");
		$object->filter		= GETPOST("filter");
		$object->width		= GETPOST("width");
		$object->widthpdf	= GETPOST("widthpdf");

		$object->filterinit	= GETPOST("filterinit");

		// récupération de la position du dernier champ
		$object->rang	= $object->getlastpos($mylistid);

		$rowid = $object->addField($user, $mylistid);
		if ($rowid > 0) {
			header('Location: card.php?rowid='.$mylistid.'#row-'.$object->pos);
			exit;
		} else {
			$error++;
			$mesg='<div class="error">'.$langs->trans($object->error).'</div>';
			$action='';
		}
	} else
		$action='';
}

if ($action == 'confirm_delete' && GETPOST('confirm')== "yes" && $user->rights->mylist->supprimer) {
	if ($object->deleteField($user, $rowid) == 1) {
		header('Location: card.php?rowid='.$mylistid);
		exit;
	} else {
		$langs->load("errors");
		$mesg='<div class="error">'.$langs->trans($object->error).'</div>';
		$action='';
	}
}

$form = new Form($db);
$formbarcode = new FormBarCode($db);

if ($action == 'delete') {
	$ret=$form->form_confirm(
					$_SERVER["PHP_SELF"]."?mylistid=".$mylistid.'&rowid='.$rowid,
					$langs->trans("DeleteAField"), $langs->trans("ConfirmDeleteAField"),
					"confirm_delete"
	);
	if ($ret == 'html')
		print '<br>';
}

$help_url="https://wiki.patas-monkey.com/index.php?title=MyList#Champs_de_la_liste";

llxHeader("", $langs->trans("DetailField"), $help_url);

dol_htmloutput_mesg($mesg);


/*
 * View
 */

$linkback = '<a href="card.php?rowid='.$mylistid.'">'.$langs->trans("BackTomyList").'</a>';

$morehtmlref='<div class="refidno">';

$morehtmlref.='<br>'.$langs->trans('MenuTitle').' : ' . $object->titlemenu;
$morehtmlref.='<br>'.$langs->trans('MainMenu').'/'.$langs->trans('LeftMenu').' : '.$object->mainmenu."/".$object->leftmenu;
$morehtmlref.='<br>'.$langs->trans('ElementTab').' : ';
$morehtmlref.=($object->elementtab?$langs->trans($object->elementtab):'');
	
$morehtmlref.='</div>';
dol_banner_tab($object, 'id', $linkback, -1, 'rowid', 'label', $morehtmlref);
print '<div class="fichecenter">';
print '<div class="fichehalfleft">';
print '<div class="underbanner clearboth"></div>';

dol_fiche_end();

if (! empty($rowid))
	print_fiche_titre($langs->trans("EditField"));
else
	print_fiche_titre($langs->trans("AddField"));



$form = new Form($db);

print '<form action="'.$_SERVER["PHP_SELF"].'?mylistid='.$mylistid.'&rowid='.$rowid.'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

if (! empty($rowid)) {
	$arrayFields=$object->FetchChampArray($rowid);
	/*
	 * Fiche champ en mode edit
	 */

	print '<input type="hidden" name="action" value="edit">';
	print '<input type="hidden" name="mylistid" value="'.$mylistid.'">';

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="noborder tableforfield" width="100%">';

	// database fieldname = key
	print '<tr><td width=35% class="fieldrequired">'.$langs->trans("fieldName").'</td>';
	print '<td><input type="text" size="40" name="field" value="'.$arrayFields['field'].'"></td></tr>'."\n";

	// FieldName
	print '<tr><td class="fieldrequired">'.$langs->trans("LabelOfField").'</td>';
	print '<td><input type="text" name="nameField" value="'.$arrayFields['name'].'"></td></tr>'."\n";

	// database alias
	print '<tr><td >'.$langs->trans("Alias").'</td>';
	print '<td><input type="text" name="alias" value="'.$arrayFields['alias'].'"></td></tr>'."\n";


	// type of Fields
	print '<tr><td class="fieldrequired">'.$langs->trans("Type").'</td>';
	print '<td>'.$object->getSelectTypeFields($arrayFields['type']).'</td></tr>'."\n";

	if (in_array($arrayFields['type'], array('Number', 'Price', 'Percent'))) {
		
		if ($arrayFields['type'] != 'Percent') {
			// sum
			print '<tr><td >'.$langs->trans("SumReport").'</td>';
			print '<td>';
			print $form->selectyesno('sumreport', ($arrayFields['sumreport']=='1'?'yes':'no'), 1);
			print '</td></tr>'."\n";

			print '<tr><td >'.$langs->trans("PctReport").'</td>';
			print '<td>';
			print $form->selectyesno('pctreport', ($arrayFields['pctreport']=='1'?'yes':'no'), 1);
			print '</td></tr>'."\n";

		}

		// average
		print '<tr><td >'.$langs->trans("AvgReport").'</td>';
		print '<td>';
		print $form->selectyesno('avgreport', ($arrayFields['avgreport']=='1'?'yes':'no'), 1);
		print '</td></tr>'."\n";

		// cumul
		print '<tr><td >'.$langs->trans("CumReport").'</td>';
		print '<td>';
		print $form->selectyesno('cumreport', ($arrayFields['cumreport']=='1'?'yes':'no'), 1);
		print '</td></tr>'."\n";

		// percent cumul
		print '<tr><td >'.$langs->trans("CumPctReport").'</td>';
		print '<td>';
		print $form->selectyesno('cumpctreport', ($arrayFields['cumpctreport']=='1'?'yes':'no'), 1);
		print '</td></tr>'."\n";

	}


	print '</table>';
	print '</div>';

	print '<div class="fichehalfright"><div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';

	// champs un peu plus mis en avant
	print '<table class="noborder" width="100%">';

	print '<tr><td >'.$langs->trans("enabled").'</td>';
	print '<td>';
	print $form->selectyesno('enabled', ($arrayFields['enabled']=='1'?'yes':'no'), 1);
	print '</td></tr>'."\n";


	// element of Fields
	print '<tr><td valign=top>'.$langs->trans("ElementField").'</td>';
	print '<td>';
	print '<textarea name="param" rows=3 cols=50>'.$arrayFields['param'].'</textarea>';
	print '</td></tr>'."\n";


	// width cols
	print '<tr><td width=35%>'.$langs->trans("Width").' / '.$langs->trans("align").'</td>';
	print '<td><input type="text" size="6" name="width" value="'.$arrayFields['width'].'">&nbsp;';

	$arrayalign=array('left'=>'gauche', 'center'=>'milieu', 'right'=>'droite');
	print $form->selectarray('align', $arrayalign, $arrayFields['align']);
	print '</td></tr>'."\n";

	// le champs supplémentaire pour la suite
	print '<tr><td >'.$langs->trans("WidthPdf").'</td>';
	print '<td><input type="text" size="6" name="widthpdf" value="'.$arrayFields['widthpdf'].'"></td></tr>'."\n";

	// code barre seulement sur numéro et chaine de caractère
	if (in_array($arrayFields['type'], array('Number', 'Text'))) {
		print '<tr><td >'.$langs->trans("BarcodeType").'</td>';
		print '<td>'.$formbarcode->selectBarcodeType($arrayFields['barcode'], "barcode", 1).'</td></tr>'."\n";
	}

	print '<tr><td >'.$langs->trans("visible").'</td>';
	print '<td>';
	print $form->selectyesno('visible', ($arrayFields['visible']=='1'?'yes':'no'), 1);
	print '</td></tr>'."\n";


	// filter init
	print '<tr><td >'.$langs->trans("filter").' / '.$langs->trans("FilterInit").'</td>';
	print '<td>'.$form->selectyesno('filter', ($arrayFields['filter']=='1'?'yes':'no'), 1);
	print '&nbsp;<input type="text" size=15 name="filterinit" value="'.$arrayFields['filterinit'].'"></td></tr>'."\n";
	print '</table>';
} else {
	print '<input type="hidden" name="action" value="add">';

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="noborder tableforfield" width="100%">';

	// database fieldname = key
	print '<tr><td width=35% class="fieldrequired">'.$langs->trans("fieldName").'</td>';
	print '<td><input type="text" name="field" size="40" value=""></td></tr>'."\n";

	// FieldName
	print '<tr><td class="fieldrequired">'.$langs->trans("LabelOfField").'</td>';
	print '<td><input type="text" name="nameField" value=""></td></tr>'."\n";

	// database alias
	print '<tr><td >'.$langs->trans("Alias").'</td>';
	print '<td><input type="text" name="alias" value=""></td></tr>'."\n";

	// type of Fields
	print '<tr><td  class="fieldrequired">'.$langs->trans("Type").'</td>';
	print '<td>'.$object->getSelectTypeFields("").'</td></tr>'."\n";

	// element of Fields
	print '<tr><td valign=top>'.$langs->trans("ElementField").'</td>';
	print '<td>';
	print '<textarea name="param" rows=3 cols=50>'.'</textarea>';
	print '</td></tr>'."\n";
	print '</table>';
	print '</div>';

	print '<div class="fichehalfright"><div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';

	// champs un peu plus mis en avant
	print '<table class="noborder" width="100%">';

	// width cols
	print '<tr><td width=35% >'.$langs->trans("Width")." / ".$langs->trans("align").'</td>';
	print '<td><input type=text size=6 name="width" value="100">&nbsp;'."\n";

	$arrayAlign = array('left'=>$langs->trans("alignleft"),
						'center'=>$langs->trans("aligncenter"),
						'right'=>$langs->trans("alignright")
					);
	print $form->selectarray('align', $arrayAlign, 'left');
	print '</td></tr>'."\n";

	print '<tr><td >'.$langs->trans("enabled").'</td>';
	print '<td>'.$form->selectyesno('enabled', 'yes', 1).'</td></tr>'."\n";

	print '<tr><td >'.$langs->trans("visible").'</td>';
	print '<td>'.$form->selectyesno('visible', 'yes', 1).'</td></tr>'."\n";

	print '<tr><td >'.$langs->trans("filter").' / '.$langs->trans("FilterInit").'</td>';
	print '<td>'.$form->selectyesno('filter', 'yes', 1);
	print '&nbsp;<input type="text" size=15 name="filterinit" value=""></td></tr>'."\n";
	print '</table>';
}

print '</div></div>';

print '<div class=" clearboth"></div>';

dol_fiche_end();

/*
 * Actions
 */
print '<div class="tabsAction">';
print '<a class="butAction" href="card.php?rowid='.$mylistid.'">'.$langs->trans('Cancel').'</a>';
// Modify
if ($user->rights->mylist->creer)
	print '<input type="submit" class="butAction" name="save" value="'.$langs->trans("Modify").'">';
else {
	print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">';
	print $langs->trans('Modify').'</a>';
}

if (! empty($rowid)) {
	// Delete
	if ($user->rights->mylist->supprimer) {
		print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?rowid='.$rowid;
		print '&action=delete&mylistid='.$mylistid.'">';
	} else
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">';
	print $langs->trans('Delete').'</a>';
}
print '</div>';
print '</form>';

llxFooter();
$db->close();