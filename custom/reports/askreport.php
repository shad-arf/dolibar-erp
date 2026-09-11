<?php
/* Copyright (C) 2011 	   Juanjo Menent <jmenent@2byte.es>
 * Copyright (C) 2012 	   Regis Houssin <regis@dolibarr.fr>
 * Copyright (C) 2013-2015 Ferran Marcet <fmarcet@2byte.es>
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
 *     \file       /reports/askreport.php
 *     \ingroup    reports
 *     \brief      Page to show info to ask reports
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");

$langs->load("reports@reports");
$langs->load("companies");

$actioncancel = GETPOST("cancel");
$actionsend = GETPOST("send");
$err=0;

/*
 * Actions
 */
if ($actionsend)
{
	$soc = GETPOST("soc");
	if (!$soc) 
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("ThirdPartyName")),"errors");
		$err++;
	}
	$name = GETPOST("name");
	if (!$name) 
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Lastname")),"errors");
		$err++;
	}
	$firstname = GETPOST("firstname");
	if (!$firstname) 
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Firstname")),"errors");
		$err++;
	}
	$ville = GETPOST("ville");
	if (!$ville) 
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Town")),"errors");
		$err++;
	}
	$pays = GETPOST("pays");
	if (!$pays) 
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Country")),"errors");
		$err++;
	}
	$tel = GETPOST("tel");
	if (!$tel) 
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Phone")),"errors");
		$err++;
	}
	$report_graphic =GETPOST("report_graphic");
	
	$mail =GETPOST("mail");
	if (!$mail) 
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("EMail")),"errors");
		$err++;
	}
	
	
	$filter= GETPOST("filter");
	if (!$filter) 
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Filter")),"errors");
		$err++;
	}
	$description = GETPOST("description");
	if (!$description) 
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Description")),"errors");
		$err++;
	}
	
	
	//Contact info
	$cuerpo = "Formulario enviado\n";
	$cuerpo .= "Empresa: " . $soc . "\n";
    $cuerpo .= "Nombre: " . $name . "\n";
    $cuerpo .= "Apellidos: " . $firstname . "\n";
    $cuerpo .= "Poblacion: " . $ville . "\n";
    $cuerpo .= "Pais: " . $pays . "\n";
    $cuerpo .= "Telefono: " . $tel . "\n";
    $cuerpo .= "E-Mail: " . $mail . "\n";
    // Report info
    $cuerpo .= "Graficos: " . GETPOST("report_graphic") . "\n";
    $cuerpo .= "Filtrado: " . $filter . "\n"; 
    $cuerpo .= "Descripcion: " . $description . "\n"; 
    
	if (!$err)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		$mailfile = new CMailFile("Formulario Petición Informe","info@2byte.es",$conf->global->MAIN_INFO_SOCIETE_NOM.'<'.$conf->global->MAIN_MAIL_EMAIL_FROM.'>',$cuerpo,array(),array(),array(),"","",0,1);
		if ($mailfile->error)
		{
			setEventMessage($mailfile->error,"errors");
		}
		else
		{
			$result=$mailfile->sendfile();
		}
		if ($result)
		{
			setEventMessage($langs->trans("MailOK"));
		}
		else
		{
			setEventMessage($langs->trans("MailKO"),"errors");
			$err++;
		}
	}
	else
	{
		setEventMessage($langs->trans("SolveIt"),"errors");
	}

}

if ($actioncancel)
{
	header("Location: ".dol_buildpath('/reports/index.php', 1));
}

/*
 * View
 */
$helpurl='EN:Module_Reports|FR:Module_Reports_FR|ES:M&oacute;dulo_Reports';
llxHeader('','',$helpurl);

print_fiche_titre($langs->trans("AsksForm"));

dol_htmloutput_events();

global $mysoc,$user;

$form = new Form($db);

$var=true;
print '<form name="askreports" action="'.$_SERVER["PHP_SELF"].'" method="post">';

print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" width="100%">';
if(!$actionsend || $err)
{
	$message ='<div class="info">';
	$message.=$langs->trans("NewReport1").'<br>'.$langs->trans("NewReport2");
	$message.='</div>';
	print $message;
	
	print '<tr class="liste_titre">';
	print '<td width="40%">'.$langs->trans("Parameter").'</td>';
	print "<td>".$langs->trans("Value")."</td>";
	print "<td>".$langs->trans("Description")."</td>";
	print "</tr>";
	$var=!$var;
	
	// Soc
	print '<tr '.$bc[$var].'>';
	print '<td class="fieldrequired">'.$langs->trans("ThirdPartyName").'</td>';
	print '<td><input name="soc" readonly="readonly" value="'.$mysoc->nom.'"></td>';
	print '<td></td>';
	print '</tr>'."\n";
	$var=!$var;
	
	//User name
	print '<tr '.$bc[$var].'>';
	print '<td class="fieldrequired">'.$langs->trans("Lastname").'</td>';
	print '<td><input name="name" readonly="readonly" value="'.$user->lastname.'"></td>';
	print '<td></td>';
	$var=!$var;
	
	print '<tr '.$bc[$var].'>';
	print '<td class="fieldrequired">'.$langs->trans("Firstname").'</td>';
	print '<td><input name="firstname" readonly="readonly" value="'.$user->firstname.'"></td>';
	print '<td></td>';
	print '</tr>'."\n";
	$var=!$var;
	
	print '<tr '.$bc[$var].'>';
	print '<td class="fieldrequired">'.$langs->trans("Town").'</td>';
	print '<td><input name="ville" readonly="readonly" value="'.$mysoc->town.'"></td>';
	print '<td></td>';
	print '</tr>'."\n";
	$var=!$var;
	
	print '<tr '.$bc[$var].'>';
	print '<td class="fieldrequired">'.$langs->trans("Country").'</td>';
	print '<td><input name="pays" readonly="readonly" value="'.$mysoc->country.'"></td>';
	print '<td></td>';
	print '</tr>'."\n";
	$var=!$var;
	
	print '<tr '.$bc[$var].'>';
	print '<td class="fieldrequired">'.$langs->trans("Phone").'</td>';
	print '<td><input name="tel" readonly="readonly" value="'.$mysoc->phone.'"></td>';
	print '<td></td>';
	print '</tr>'."\n";
	$var=!$var;
	
	print '<tr '.$bc[$var].'>';
	print '<td class="fieldrequired">'.$langs->trans("EMail").'</td>';
	print '<td><input name="mail" readonly="readonly" value="'.$mysoc->email.'"></td>';
	print '<td></td>';
	print '</tr>'."\n";
	$var=!$var;
	
	print '<tr '.$bc[$var].'>';
	print '<td class="fieldrequired">'.$langs->trans("Graphics").'</td>';
	print '<td>'.$form->selectyesno("report_graphic",$report_graphic,1).'</td>';
	print '<td></td>';
	print '</tr>'."\n";
	$var=!$var;
	
	print '<tr '.$bc[$var].'>';
	print '<td class="fieldrequired">'.$langs->trans("Filter").'</td>';
	print '<td valign="top">';
	
	print '<textarea name="filter" cols="80" rows="'.ROWS_5.'">'.$filter.' </textarea></td>';
	
	/*require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
	$doleditor=new DolEditor('filter','','',200,'dolibarr_notes','In',true,false,false,20,70); 
	$doleditor->Create();*/
	
	print '</td>';
	print '<td>'.$langs->trans("SampleFilter").'</td>';
	print '</tr>'."\n";
	$var=!$var;
	
	print '<tr '.$bc[$var].'>';
	print '<td class="fieldrequired">'.$langs->trans("Description").'</td>';
	print '<td valign="top">';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$enabled=(! empty($conf->global->FCKEDITOR_ENABLE_DETAILS)?$conf->global->FCKEDITOR_ENABLE_DETAILS:0);
	$toolbarname='dolibarr_details';
	if (! empty($conf->global->FCKEDITOR_ENABLE_DETAILS_FULL)) $toolbarname='dolibarr_notes';
	$doleditor=new DolEditor('description',$description,'',200,$toolbarname,'In',true,false,$enabled,20,70);
	$doleditor->Create();
	print '</td>';
	print '<td>'.$langs->trans("SampleDesc").'</td>';
	print '</tr>'."\n";
	
	print '</table>';
	
	print '<br>';

	print '<tr><td colspan="2" align="center">';
	print '<input type="submit" class="button" name="send" value="'.$langs->trans("Send").'">';
	print ' &nbsp; ';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</td></tr>';
}
else
{
	print '<tr><td colspan="2" align="center">';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Return").'">';
	print '</td></tr>';
}
print '</form>';

llxFooter();

$db->close();
?>