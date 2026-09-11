<?php
/* Copyright (C) 2013-2022	Charlene BENKE	<charlene@patas-monkey.com>
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
 *	\file	   htdocs/mylist/card.php
 *	\ingroup	listes
 *	\brief	  List card
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory

dol_include_once('/mylist/class/mylist.class.php');

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';


$langs->load("mylist@mylist");

$code=GETPOST('code', 'alpha');
$rowid=GETPOST('rowid', 'alpha');
$action=GETPOST('action', 'alpha');
$backtopage=GETPOST('backtopage', 'alpha');

if (!$user->rights->mylist->lire) accessforbidden();

$object = new Mylist($db);

/*
 * Actions
 */


if ($action == 'add' && $user->rights->mylist->creer) {
	$mesg="";
	$error=0;
	if (empty($_POST["label"])) {
		$mesg.='<div class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentities("label")).'</div>';
		$error++;
	}
	if (empty($_POST["querylist"])) {
		$mesg.='<div class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentities("querylist")).'</div>';
		$error++;
	}

	if (! $error) {
		$object->code		= $_POST["code"];
		$object->label		= $_POST["label"];
		$object->description = $_POST["description"];

		$object->titlemenu	= $_POST["titlemenu"];
		$object->mainmenu	= $_POST["mainmenu"];
		$object->leftmenu	= $_POST["leftmenu"];
		$object->elementtab	= $_POST["elementtab"];
		$object->perms		= $_POST["perms"];
		$object->langs		= $_POST["langs"];
		$object->author		= $_POST["author"];
		$object->datatable	= $_POST["datatable"];
		$object->active		= $_POST["active"];
		$object->export		= $_POST["export"];
		$object->shownumline= $_POST["shownumline"];
		$object->model_pdf	= $_POST["model_pdf"];
		// to bypass the sqlinjection
		$object->querylist=str_replace("#SEL#", "SELECT", $_POST["querylist"]);
		$querydo=str_replace("#SEL#", "SELECT", GETPOST("querydo"));
		$querydo=str_replace("#UPD#", "UPDATE", $querydo);
		$querydo=str_replace("#INS#", "INSERT", $querydo);
		$querydo=str_replace("#DEL#", "DELETE", $querydo);
		$object->querydo=$querydo;
		$object->fieldinit	= $_POST["fieldinit"];

		$result = $object->create($user);

		if ($result == 0) {
			$langs->load("errors");
			$mesg='<div class="error">'.$langs->trans($object->error).'</div>';
			$error++;
		}

		if (! $error) {
			header("Location:card.php?rowid=".$result);
			exit;
		} else
			$action = 'create';
	} else
		$action = 'create';
} elseif ($action == 'validate' && $user->rights->mylist->creer) {
	// met à jour la liste
	$object->code =			GETPOST("code");
	$object->rowid =		GETPOST("rowid");
	$object->label =		GETPOST("label");
	$object->description =	GETPOST("description");
	$object->titlemenu =	GETPOST("titlemenu");
	$object->mainmenu =		GETPOST("mainmenu");
	$object->leftmenu =		GETPOST("leftmenu");
	$object->elementtab =	GETPOST("elementtab");
	$object->perms =		GETPOST("perms");
	$object->langs =		GETPOST("langs");
	$object->author =		GETPOST("author");
	$object->datatable =	GETPOST("datatable");
	$object->shownumline =	GETPOST("shownumline");
	$object->active =		GETPOST("active");
	$object->export =		GETPOST("export");
	$object->forceall =		GETPOST("forceall");
	$object->model_pdf = 	GETPOST("model_pdf");

	// to bypass the sqlinjection
	$querylist=str_replace("#SEL#", "SELECT", GETPOST("querylist"));
	$object->querylist=	$querylist;
	$querydo=GETPOST("querydo");
	$querydo=str_replace("#SEL#", "SELECT", GETPOST("querydo"));
	$querydo=str_replace("#UPD#", "UPDATE", $querydo);
	$querydo=str_replace("#INS#", "INSERT", $querydo);
	$querydo=str_replace("#DEL#", "DELETE", $querydo);
	$object->querydo=	$querydo;
	$object->fieldinit=	GETPOST("fieldinit");

	$object->update();
} elseif ($action == 'importation' && $user->rights->mylist->creer) {
	if (GETPOST("importexport")) {
		$object->importlist(GETPOST("importexport", 'none'));
		$rowid=$object->rowid;
		if ($rowid == 0) {
			$langs->load("errors");
			$mesg='<div class="error">'.$langs->trans($object->error).'</div>';
			$error++;
		}
		// si tous va bien on affiche la liste
		if (! $error) {
			header("Location:card.php?rowid=".$rowid);
			exit;
		} else
			$action = 'importexport';
		//$object->fetch($object->code);
	} else {
		header("Location:list.php");
		exit;
	}
} elseif ($action == 'delete' && $user->rights->mylist->supprimer) {
	$object->rowid=	GETPOST("rowid");
	$object->delete($user);
	header("Location:list.php");
	exit;
}

/*
 *	View
 */

$form = new Form($db);
$formfile = new FormFile($db);


$help_url="https://wiki.patas-monkey.com/index.php?title=MyList";
llxHeader("", $langs->trans("Mylist"), $help_url);

if ($action == 'create' && $user->rights->mylist->creer) {
	/*
	 * Create
	 */
	print_fiche_titre($langs->trans("NewMylist"));

	dol_htmloutput_mesg($mesg);

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="noborder tableforfield" width="100%">';

	// label
	print '<tr><td><span class="fieldrequired">'.$langs->trans("Label").'</span></td>';
	print '<td><input size="30" type="text" name="label" value="'.GETPOST("label", 'alpha').'"></td></tr>';

	// description new
	print '<tr><td><span >'.$langs->trans("Description").'</span></td>';
	print '<td><textarea name="description" cols=40 rows=3>'.GETPOST("description", 'alpha').'</textarea></td></tr>';

	// TitleMenu
	print '<tr><td><span class="fieldrequired">'.$langs->trans("TitleMenu").'</span></td>';
	print '<td><input size="30" type="text" name="titlemenu" value="'.GETPOST("titlemenu","alpha").'"></td></tr>';

	// Mainmenu
	print '<tr><td>'.$langs->trans("MainMenu").'</td>';
	print '<td><input size="30" type="text" name="mainmenu" value="'.GETPOST("mainmenu","alpha").'"></td></tr>';

	// Leftmenu
	print '<tr><td>'.$langs->trans("LeftMenu").'</td>';
	print '<td><input size="30" type="text" name="leftmenu" value="'.GETPOST("leftmenu","alpha").'"></td></tr>';

	// elementtab
	print '<tr><td>'.$langs->trans("ElementTab").'</td><td>'.$object->getSelectelementTab("").'</td></tr>';

	// perms
	print '<tr><td>'.$langs->trans("perms").'</td>';
	print '<td><input size="30" type="text" name="perms" value="'.GETPOST("perms", "alpha").'"></td></tr>';


	// langs
	print '<tr><td>'.$langs->trans("langs").'</td>';
	print '<td><input size="30" type="text" name="langs" value="'.GETPOST("langs","alpha").'"></td></tr>';

	print '</table>';
	print '</div>';

	print '<div class="fichehalfright"><div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';

	// champs un peu plus mis en avant
	print '<table class="noborder" width="100%">';

	// author
	print '<tr><td>'.$langs->trans("author").'</td>';
	print '<td><input size="30" type="text" name="author" value="'.GETPOST("author","alpha").'"></td></tr>';


	if ($conf->global->MYLIST_CSV_EXPORT =="1") {
		print '<tr><td>'.$langs->trans("ExportListCSV").'</td><td align=left >';
		print $form->selectyesno('export', "", 1);
		print '</td></tr>';
	}

	if ($conf->global->MYLIST_ADDON_PDF) {
		// Load array def with activated templates
		$def = array();
		$sql = "SELECT nom, libelle";
		$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
		$sql.= " WHERE type = 'mylist'";
		$sql.= " AND entity = ".$conf->entity;
		$resql=$db->query($sql);
		if ($resql) {
			$i = 0;
			$num_rows=$db->num_rows($resql);
			while ($i < $num_rows) {
				$array = $db->fetch_array($resql);
				$def[$array[0]]=$array[1];
				$i++;
			}
		}
		if (count($def)	> 0) {
			print '<tr><td>'.$langs->trans("DocumentModel").'</td><td align=left >';
			print $form->selectarray('model_pdf', $def, "", 1, 0, 0);
			print '</td></tr>';
		}
	}

	// DatatableMode
	print '<tr><td>'.$langs->trans("DatatableMode").'</td><td>';
	print $form->selectyesno('datatable', "", 1);
	print '</td></tr>';


	print '</table>';
	print '<br>';

	print '</div>';

	print '</div></div>';
	print '<div style="clear:both"></div>';

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	// champs un peu plus mis en avant
	print '<table class="noborder" width="100%">';
	// querylist
	print '<tr class="liste_titre"><td colspan=2  valign=top><span class="fieldrequired">';
	print $form->textwithpicto($langs->trans("QueryList"), $langs->trans("explainbypassSQLinjection"),1,"info");
	print '</span></td></tr>';

	print '<tr><td colspan=2><textarea name="querylist" cols=80 rows=10></textarea></td></tr>';
	print '</table>';
	print '</div>';

	print '<div class="fichehalfright"><div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="noborder" width="100%">';

	// querydo
	print '<tr  class="liste_titre"><td colspan=2 valign=top>';
	print $form->textwithpicto($langs->trans("QueryDo"), $langs->trans("explainbypassSQLinjectionDo"),1,"info");
	print '</td></tr>';

	print '<tr><td colspan=2><textarea name="querydo" cols=80 rows=5></textarea></td></tr>';

	// fieldinit
	print '<tr class="liste_titre"><td colspan=2 valign=top>'.$langs->trans("DefaultInitFields").'</td></tr>';
	print '<tr><td colspan=2 ><textarea name="fieldinit" cols=80 rows=3></textarea></td></tr>';


	print '</table>';

	print '<div class="tabsAction">';
	print '<input type="submit" class="butAction" value="'.$langs->trans("Create").'">';
	print '<input type="submit" class="butAction" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';
	print '</form>';
} elseif ($action == 'importexport' && $user->rights->mylist->creer) {
	/*
	 * Import/export list data
	 */
	print_fiche_titre($langs->trans("ImportExport"));
	print "<h3>".$langs->trans("ImportExportFolderInfo")."</h3><br>";

	dol_htmloutput_mesg($mesg);

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="importation">';
	print '<input type="hidden" name="code" value="'.GETPOST("code").'">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	print '<table class="border" width="100%">';

	print '<tr><td><span class="fieldrequired">'.$langs->trans("FillImportExportData").'</span></td></tr>';
	print '<td><textarea name=importexport cols=132 rows=20>';
	if ($rowid)
		print $object->getexporttable($rowid);
	print '</textarea></td></tr>';
	print '</table>';
	print '<div class="tabsAction">';
	print '<input type="submit" class="butAction" value="'.$langs->trans("ImportMyList").'">';
	print '</div>';
	print '</form>';
} elseif ($action == 'update' && $user->rights->mylist->creer) {
	print_fiche_titre($langs->trans("UpdateMylist"));

	dol_htmloutput_mesg($mesg);

	$ret=$object->fetch($rowid);

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="validate">';
	print '<input type="hidden" name="rowid" value="'.$rowid.'">';

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="noborder tableforfield" width="100%">';

	// label
	print '<tr><td><span class="fieldrequired">'.$langs->trans("Label").'</span></td>';
	print '<td><input size="30" type="text" name="label" value="'.$object->label.'"></td></tr>';

	// description
	print '<tr><td><span >'.$langs->trans("Description").'</span></td>';
	print '<td><textarea name="description" cols=45 rows=3>'.$object->description.'</textarea></td></tr>';

	// TitleMenu
	print '<tr><td><span class="fieldrequired">'.$langs->trans("TitleMenu").'</span></td>';
	print '<td><input size="30" type="text" name="titlemenu" value="'.$object->titlemenu.'"></td></tr>';

	// Mainmenu
	print '<tr><td><span class="fieldrequired">'.$langs->trans("MainMenu").'</span></td>';
	print '<td><input size="30" type="text" name="mainmenu" value="'.$object->mainmenu.'"></td></tr>';

	// Leftmenu
	print '<tr><td><span class="fieldrequired">'.$langs->trans("LeftMenu").'</span></td>';
	print '<td><input size="30" type="text" name="leftmenu" value="'.$object->leftmenu.'"></td></tr>';

	// elementtab
	print '<tr><td>'.$langs->trans("ElementTab").'</td>';
	print '<td>'.$object->getSelectelementTab($object->elementtab).'</td></tr>';

	// perms
	print '<tr><td>'.$langs->trans("perms").'</td>';
	print '<td><input size="30" type="text" name="perms" value="'.$object->perms.'"></td></tr>';

	// langs
	print '<tr><td>'.$langs->trans("langs").'</td>';
	print '<td><input size="30" type="text" name="langs" value="'.$object->langs.'"></td></tr>';


	print '</table>';
	print '</div>';

	print '<div class="fichehalfright"><div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';

	// champs un peu plus mis en avant
	print '<table class="noborder" width="100%">';
	// author
	print '<tr><td width=50%>'.$langs->trans("author").'</td><td>';
	// non modifiable si il est renseigné
	if ($object->author)
		print '<input type="hidden" name="author" value="'.$object->author.'">'.$object->author;
	else
		print '<input size="30" type="text" name="author" value="'.$object->author.'">';
	print '</td></tr>';

	if ($conf->global->MYLIST_CSV_EXPORT =="1") {
		print '<tr><td>'.$langs->trans("ExportListCSV").'</td><td align=left >';
		print $form->selectyesno('export', $object->export, 1);
		print '</td></tr>';
	}

	if ($conf->global->MYLIST_ADDON_PDF) {
		// Load array def with activated templates
		$def = array();
		$sql = "SELECT nom, libelle";
		$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
		$sql.= " WHERE type = 'mylist'";
		$sql.= " AND entity = ".$conf->entity;
		$resql=$db->query($sql);
		if ($resql) {
			$i = 0;
			$num_rows=$db->num_rows($resql);
			while ($i < $num_rows) {
				$array = $db->fetch_array($resql);
				$def[$array[0]]=$array[1];
				$i++;
			}
		}
		if (count($def) > 0 ) {
			print '<tr><td>'.$langs->trans("DocumentModel").'</td><td align=left>';
			print $form->selectarray('model_pdf', $def, $object->model_pdf, 1, 0, 0);
			print '</td></tr>';
		}
	}

	print '<tr><td >'.$langs->trans("active").'</td><td align=left>';
	print $form->selectyesno('active', $object->active, 1);
	print '</td></tr>';

	print '<tr><td >'.$langs->trans("forceall").'</td><td align=left>';
	print $form->selectyesno('forceall', $object->forceall, 1);
	print '</td></tr>';

	// DatatableMode
	print '<tr><td>'.$langs->trans("DatatableMode").'</td><td>';
	print $form->selectyesno('datatable', $object->datatable, 1);
	print '</td></tr>';

	print '<tr ><td >'.$langs->trans("shownumline").'</td><td>';
	print $form->selectyesno('shownumline', $object->shownumline, 1);
	print '</td></tr>';

	print '</table>';
	print '<br>';

	print '</div>';

	print '</div></div>';
	print '<div style="clear:both"></div>';

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	// champs un peu plus mis en avant
	print '<table class="noborder" width="100%">';
	// querylist
	print '<tr class="liste_titre"><td colspan=2  valign=top><span class="fieldrequired">';
	print $form->textwithpicto($langs->trans("QueryList"), $langs->trans("explainbypassSQLinjection"),1,"info");
	print '</span></td></tr>';
	$querylist=str_replace("SELECT", "#SEL#", $object->querylist);
	print '<tr><td colspan=2><textarea name="querylist" cols=80 rows=10>'.$querylist.'</textarea></td></tr>';


	$querydo=str_replace("SELECT", "#SEL#", $object->querydo);
	$querydo=str_replace("UPDATE", "#UPD#", $querydo);
	$querydo=str_replace("INSERT", "#INS#", $querydo);
	$querydo=str_replace("DELETE", "#DEL#", $querydo);
	print '</table>';
	print '</div>';

	print '<div class="fichehalfright"><div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="noborder" width="100%">';

	// querydo
	print '<tr  class="liste_titre"><td colspan=2 valign=top>';
	print $form->textwithpicto($langs->trans("QueryDo"), $langs->trans("explainbypassSQLinjectionDo"),1,"info");
	print '</td></tr>';

	print '<tr><td colspan=2><textarea name="querydo" cols=80 rows=5>'.$querydo.'</textarea></td></tr>';

	// fieldinit
	print '<tr class="liste_titre"><td colspan=2 valign=top>'.$langs->trans("DefaultInitFields").'</td></tr>';
	print '<tr><td colspan=2 ><textarea name="fieldinit" cols=80 rows=3>'.$object->fieldinit.'</textarea></td></tr>';


	print '</table>';

	print '<div class="tabsAction">';
	print '<input type="submit" class="butAction" value="'.$langs->trans("Update").'">';
	print '<a href="'.$_SERVER["PHP_SELF"].'?rowid='.$rowid.'" style="line-height: 1.3em;" class="butAction">'.$langs->trans("Cancel").'</a>';
	print "</div>";

	print '</form>';
} else {
	// affichage 
	$ret=$object->fetch($rowid);
	// charge les langues
	if ($object->langs)
		foreach (explode(":", $object->langs) as $newlang)
			$langs->load($newlang);
	/*
	 * Show
	 */

	dol_htmloutput_mesg($mesg);
	$linkback = '<a href="list.php">'.$langs->trans("BackToList").'</a>';

	$morehtmlref='<div class="refidno">';
	// description (si présente)
	if ($object->description)
		$morehtmlref.='<br>'.$langs->trans('Description') . ' : ' . $object->description;

	// on ajoute un lien pour aller directement sur la liste

	$morehtmlref.='<br>'. "<a href='mylist".(($object->datatable == 1)?"dt":'').".php?rowid=".$object->rowid."&token=".newtoken()."' target='_blank'>";
	$morehtmlref.=img_picto("","external-link-alt")." ".$langs->trans('TestListLink')."</a>\n";
	$morehtmlref.='</div>';
	
	dol_banner_tab($object, 'rowid', $linkback, -1, 'rowid', 'label', $morehtmlref);
	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="noborder tableforfield" width="100%">';

	// Menu
	print '<tr ><td width=30%>'.$langs->trans("TitleMenu").'</td><td >'.$object->titlemenu.'</td></tr>';
	print '<tr ><td>'.$langs->trans("MainMenu").'</td><td >'.$object->mainmenu.'</td></tr>';
	print '<tr ><td>'.$langs->trans("LeftMenu").'</td><td >'.$object->leftmenu.'</td></tr>';
	print '<tr ><td>'.$langs->trans("ElementTab").'</td>';
	print '<td >'.($object->elementtab?$langs->trans($object->elementtab):'').'</td></tr>';
	print '<tr ><td>'.$langs->trans("perms").'</td><td >'.$object->perms.'</td></tr>';
	print '<tr ><td>'.$langs->trans("langs").'</td><td >'.$object->langs.'</td></tr>';

	print '</table>';
	print '</div>';


	print '<div class="fichehalfright"><div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="noborder tableforfield" width="100%">';
	print '<tr ><td width=50%>'.$langs->trans("author").'</td><td >'.$object->author.'</td></tr>';
	if ($conf->global->MYLIST_CSV_EXPORT =="1")
		print '<tr ><td>'.$langs->trans("ExportListCSV").'</td><td >'.yn($object->export).'</td></tr>';

	if (!empty($conf->global->MYLIST_ADDON_PDF) && !empty($object->model_pdf) ) {
		$sql = "SELECT libelle ";
		$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
		$sql.= " WHERE nom = '".$object->model_pdf."'";

		$libelle="";
		
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			$libelle=!empty($obj->libelle)?$obj->libelle:"";
		}
		print '<tr ><td>'.$langs->trans("DocumentModel").'</td><td >'.$libelle.'</td></tr>';
	}

	print '<tr ><td>'.$langs->trans("forceall").'</td><td align=left>'.yn($object->forceall).'</td></tr>';
	print '<tr ><td >'.$langs->trans("DatatableMode").'</td><td align=left>'.yn($object->datatable).'</td></tr>';
	print '<tr ><td >'.$langs->trans("shownumline").'</td><td align=left>'.yn($object->shownumline).'</td></tr>';

	print '</table>';
	print '<br>';

	print '</div>';

	print '</div></div>';
	print '<div style="clear:both"></div>';

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	// champs un peu plus mis en avant
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td colspan=2 >'.$langs->trans("querylist").'</td></tr>';
	print '<tr><td colspan=2>'.$object->querylist.'</td></tr>';
	print '</table>';

	print '</div>';


	print '<div class="fichehalfright"><div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td colspan=2 >'.$langs->trans("querydo").'</td></tr>';
	print '<tr><td colspan=2>'.$object->querydo.'</td></tr>';
	print '<tr class="liste_titre"><td colspan=2 >'.$langs->trans("DefaultInitFields").'</td></tr>';
	print '<tr><td colspan=2>'.$object->fieldinit.'</td></tr>';
	print '</tbody>';
	print '</table>';

	print "</div></div></div>";

	print '<div style="clear:both"></div>';

	
	/*
	 * Boutons actions de la liste
	 */
	print '<div class="tabsAction">';

	if ($user->rights->mylist->creer)
		print '<a class="butAction" href="card.php?rowid='.$object->rowid.'&token='.newToken().'&action=update">';
	else
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">';
	print $langs->trans('Update').'</a>';

	if ($user->rights->mylist->creer)
		print '<a class="butAction" href="card.php?rowid='.$object->rowid.'&token='.newToken().'&action=importexport">';
	else
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">';
	print $langs->trans('ImportExport').'</a>';

	// on autorise la suppression que sur les listes désactivés
	if ($user->rights->mylist->supprimer && !$object->active)
		print '<a class="butActionDelete" href="card.php?rowid='.$object->rowid.'&token='.newToken().'&action=delete">';
	else
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">';
	print $langs->trans('Delete').'</a>';
	print "</div>";

	// display fields list
	$object->getChampsArray($rowid);

	$nboflines=count($object->listsUsed);
	//var_dump($object->listsUsed);
	if (!empty($conf->use_javascript_ajax)) {
		include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
	}

	$permissiontoadd=$user->rights->mylist->creer;
	if ((int) DOL_VERSION > 10)
		$newcardbutton = dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', dol_buildpath('/mylist/champ.php', 1).'?mylistid='.$object->rowid.'&token='.newtoken().'&backtopage='.urlencode($_SERVER['PHP_SELF']), '', $permissiontoadd);
	else {
		if ($permissiontoadd)
			$newcardbutton = '<a class="butAction" href="champ.php?mylistid='.$object->rowid.'&token='.newtoken().'">';
		else
			$newcardbutton = '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">';
		$newcardbutton.=$langs->trans('AddField').'</a>';
	}
	
	$title=$langs->trans('ListFields');

	if (is_array($object->listsUsed)) {
		print_barre_liste($title, 0, $_SERVER["PHP_SELF"], "", "", "", "",  0, count($object->listsUsed),'mylist@mylist', 0, $newcardbutton, '', 0);

		print '<table id="tablelines" class="noborder" >';
		print '<tr class="liste_titre">';
		print '<th width=150px>'.$langs->trans('field').'</td>' ;
		print '<th width=150px>'.$langs->trans('name').'</th>' ;
		print '<th width=75px>'.$langs->trans('alias').'</th>' ;
		print '<th width=75px>'.$langs->trans('type').'</th>' ;
		print '<th width=150px>'.$langs->trans('elementField').'</th>' ;
		print '<th width=50px>'.$langs->trans('width').'</th>' ;
		print '<th width=75px>'.$langs->trans('align').'</th>' ;
		print '<th width=50px>'.$langs->trans('enabled').'</th>' ;
		print '<th width=50px>'.$langs->trans('visible').'</th>' ;
		print '<th width=30px>'.$langs->trans('filter').'</th>' ;
		print '<th width=40px colspan=2>'.$langs->trans('filterinit').'</th>' ;
		print '</tr>';

		foreach ($object->listsUsed as $key=> $value) {
			print '<tr id="'.$value['rowid'].'">'."\n";
			print '<td><a href="champ.php?mylistid='.$rowid.'&rowid='.$value['rowid'].'&token='.newtoken().'">';
			print ($value['field']? $value['field']:$langs->trans('empty')).'</a></td>' ;
			$translatename=($value['name'] ? $langs->trans($value['name']) : '');
			print '<td title="'.$translatename.'">';
			print $value['name'].'</td>' ;
			print '<td>'.$value['alias'].'</td>' ;
			print '<td>'.$langs->trans($value['type']).'</td>' ;
			print '<td>'.dol_trunc($value['param'], 16).'</td>' ;
			print '<td>'.$value['width'].'</td>' ;
			print '<td>'.$langs->trans($value['align']).'</td>' ;
			print '<td>'.yn($value['enabled']).'</td>' ;
			print '<td>'.yn($value['visible']).'</td>' ;
			print '<td>'.yn($value['filter']).'</td>' ;
			print '<td>'.$value['filterinit'].'</td>' ;
			print '<td align="right" class="tdlineupdown">&nbsp;</td>';
			print '</tr>';
		}
		print "</table>";
	}
}
llxFooter();
$db->close();