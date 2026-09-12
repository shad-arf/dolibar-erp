<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville         <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur          <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne                  <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin                <regis@dolibarr.fr>
 * Copyright (C) 2008 	   Raphael Bertrand (Resultic)  <raphael.bertrand@resultic.fr>
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
 */

/**
 *      \file       htdocs/admin/facture.php
 *		\ingroup    facture
 *		\brief      Page to setup paymentreceipt module
 *		\version    $Id: facture.php,v 1.150 2011/07/31 22:23:25 eldy Exp $
 */

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");	// For "custom" directory
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');

$langs->load("admin");
$langs->load("companies");
$langs->load("bills");
$langs->load("other");
$langs->load("errors");
$langs->load("paymentreceipt@paymentreceipt");

if (!$user->admin)
accessforbidden();

$typeconst=array('yesno','texte','chaine');


/*
 * Actions
 */

if ($_POST["action"] == 'updateMask')
{
    $maskconstpaymentreceipt=$_POST['maskconstpaymentreceipt'];
    $maskconstcredit=$_POST['maskconstcredit'];
    $maskconsttransfer=$_POST['maskconsttransfer'];
    $maskpaymentreceipt=$_POST['maskpaymentreceipt'];
    $maskcredit=$_POST['maskcredit'];
    $masktransfer=$_POST['masktransfer'];
	if ($maskconstpaymentreceipt) dolibarr_set_const($db,$maskconstpaymentreceipt,$maskpaymentreceipt,'chaine',0,'',$conf->entity);
    if ($maskconstcredit)  dolibarr_set_const($db,$maskconstcredit,$maskcredit,'chaine',0,'',$conf->entity);
    if ($maskconsttransfer)  dolibarr_set_const($db,$maskconsttransfer,$masktransfer,'chaine',0,'',$conf->entity);
}

if ($_GET["action"] == 'specimen')
{
    $modele=$_GET["module"];

    $facture = new Facture($db);
    $facture->initAsSpecimen();

    // Load template
    $dir = DOL_DOCUMENT_ROOT . "/includes/modules/facture/doc/";
    $file = "pdf_".$modele.".modules.php";
    if (file_exists($dir.$file))
    {
        $classname = "pdf_".$modele;
        require_once($dir.$file);

        $obj = new $classname($db);

        if ($obj->write_file($facture,$langs) > 0)
        {
            header("Location: ".DOL_URL_ROOT."/document.php?modulepart=facture&file=SPECIMEN.pdf");
            return;
        }
        else
        {
            $mesg='<div class="error">'.$obj->error.'</div>';
            dol_syslog($obj->error, LOG_ERR);
        }
    }
    else
    {
        $mesg='<div class="error">'.$langs->trans("ErrorModuleNotFound").'</div>';
        dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
    }
}

// define constants for models generator that need parameters
if ($_POST["action"] == 'setModuleOptions')
{
    $post_size=count($_POST);
    for($i=0;$i < $post_size;$i++)
    {
        if (array_key_exists('param'.$i,$_POST))
        {
            $param=$_POST["param".$i];
            $value=$_POST["value".$i];
            if ($param) dolibarr_set_const($db,$param,$value,'chaine',0,'',$conf->entity);
        }
    }
}

if ($_GET["action"] == 'set')
{   $gettype=$_GET['type'];
    $type=$gettype?$gettype:'paymentreceipt';
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity, libelle, description)";
    $sql.= " VALUES ('".$db->escape($_GET["value"])."','".$type."',".$conf->entity.", ";
    $sql.= ($_GET["label"]?"'".$db->escape($_GET["label"])."'":'null').", ";
    $sql.= (! empty($_GET["scandir"])?"'".$db->escape($_GET["scandir"])."'":"null");
    $sql.= ")";
    if ($db->query($sql))
    {

    }
}

if ($_GET["action"] == 'del')
{
    $gettype=$_GET['type'];
    $type=$gettype?$gettype:'paymentreceipt';
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql.= " WHERE nom = '".$_GET["value"]."'";
    $sql.= " AND type = '".$type."'";
    $sql.= " AND entity = ".$conf->entity;

    if ($db->query($sql))
    {

    }
}

if ($_GET["action"] == 'setdoc')
{
    $db->begin();
    $type=$_GET['type'];
    if(!$type) $type='paymentreceipt';

if ($type == 'paymentreceipt' && dolibarr_set_const($db, "PAYMENT_RECEIPT_ADDON_PDF", $_GET["value"], 'chaine', 0, '', $conf->entity)) {
        $conf->global->PAYMENT_RECEIPT_ADDON_PDF = $_GET["value"];
    }
    // On active le modele

    $sql_del = "DELETE FROM " . MAIN_DB_PREFIX . "document_model";
    $sql_del.= " WHERE nom = '" . $db->escape($_GET["value"]) . "'";
    $sql_del.= " AND type = '".$type."'";
    $sql_del.= " AND entity = ".$conf->entity;
    dol_syslog("facture.php ".$sql_del);
    $result1=$db->query($sql_del);

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity, libelle, description)";
    $sql.= " VALUES ('".$_GET["value"]."', '".$type."', ".$conf->entity.", ";
    $sql.= ($_GET["label"]?"'".$db->escape($_GET["label"])."'":'null').", ";
    $sql.= (! empty($_GET["scandir"])?"'".$_GET["scandir"]."'":"null");
    $sql.= ")";
    dol_syslog("facture.php ".$sql);
    $result2=$db->query($sql);
    if ($result1 && $result2)
    {
        $db->commit();
    }
    else
    {
        dol_syslog("facture.php ".$db->lasterror(), LOG_ERR);
        $db->rollback();
    }
}

if ($_GET["action"] == 'setmod')
{
    // TODO Verifier si module numerotation choisi peut etre active
    // par appel methode canBeActivated

    dolibarr_set_const($db, "FACTURE_ADDON",$_GET["value"],'chaine',0,'',$conf->entity);
}

if ($_POST["action"] == 'setribchq')
{
    dolibarr_set_const($db, "FACTURE_RIB_NUMBER",$_POST["rib"],'chaine',0,'',$conf->entity);
    dolibarr_set_const($db, "FACTURE_CHQ_NUMBER",$_POST["chq"],'chaine',0,'',$conf->entity);
}

if ($_POST["action"] == 'set_FACTURE_DRAFT_WATERMARK')
{
    dolibarr_set_const($db, "FACTURE_DRAFT_WATERMARK",trim($_POST["FACTURE_DRAFT_WATERMARK"]),'chaine',0,'',$conf->entity);
}

if ($_POST["action"] == 'set_FACTURE_FREE_TEXT')
{
    dolibarr_set_const($db, "FACTURE_FREE_TEXT",$_POST["FACTURE_FREE_TEXT"],'chaine',0,'',$conf->entity);
}

if ($_POST["action"] == 'setforcedate')
{
    dolibarr_set_const($db, "FAC_FORCE_DATE_VALIDATION",$_POST["forcedate"],'chaine',0,'',$conf->entity);
}

if ($_POST["action"] == 'update' || $_POST["action"] == 'add')
{
    if (! dolibarr_set_const($db, $_POST["constname"],$_POST["constvalue"],$typeconst[$_POST["consttype"]],0,isset($_POST["constnote"])?$_POST["constnote"]:'',$conf->entity));
    {
        dol_print_error($db);
    }
}

if ($_GET["action"] == 'delete')
{
    if (! dolibarr_del_const($db, $_GET["rowid"],$conf->entity));
    {
        dol_print_error($db);
    }
}


/*
 * View
 */

$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
llxHeader("",$langs->trans("Payment_receiptSetup"));

$html=new Form($db);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("Payment_receiptSetup"),$linkback,'setup');
print '<br>';

//  Document model for paymentreceipts

print '<br>';
print_titre($langs->trans("Modèle de document de paiements"));

// Load array def with activated templates
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = 'paymentreceipt'";
$sql.= " AND entity = ".$conf->entity;
$resql=$db->query($sql);
if ($resql)
{
    $i = 0;
    $num_rows=$db->num_rows($resql);
    while ($i < $num_rows)
    {
        $array = $db->fetch_array($resql);
        array_push($def, $array[0]);
        $i++;
    }
}
else
{
    dol_print_error($db);
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="60">'.$langs->trans("Default").'</td>';
print '<td align="center" width="32" colspan="2">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();
$var=true;
$filelist=array();
foreach ($dirmodels as $reldir)
{
    foreach (array('','/doc') as $valdir)
    {
        $dir = dol_buildpath($reldir."core/modules/paymentreceipt/".$valdir);
        if (is_dir($dir))
        {
            $handle=opendir($dir);
            if (is_resource($handle))
            {
                while (($file = readdir($handle))!==false)
                {
                    $filelist[]=$file;
                }
                closedir($handle);
                //sort($filelist);
                //var_dump($filelist);

                foreach($filelist as $file)
                {
                    if (preg_match('/\.modules\.php$/i',$file) && preg_match('/^(pdf_|doc_)/',$file))
                    {
                    	if (file_exists($dir.'/'.$file))
                    	{
                    		$name = substr($file, 4, dol_strlen($file) -16);
	                        $classname = substr($file, 0, dol_strlen($file) -12);

	                        require_once($dir.'/'.$file);
	                        $module = new $classname($db);

	                        $modulequalified=1;
	                        if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified=0;
	                        if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified=0;

	                        if ($modulequalified)
	                        {
	                            $var = !$var;
	                            print '<tr '.$bc[$var].'><td width="100">';
	                            print (empty($module->name)?$name:$module->name);
	                            print "</td><td>\n";
	                            if (method_exists($module,'info')) print $module->info($langs);
	                            else print $module->description;
	                            print '</td>';

	                            // Active
	                            if (in_array($name, $def))
	                            {
	                                print "<td align=\"center\">\n";
	                                if ($conf->global->PAYMENT_RECEIPT_ADDON_PDF != "$name")
	                                {
	                                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;type=paymentreceipt&amp;value='.$name.'">';
	                                    print img_picto($langs->trans("Enabled"),'on');
	                                    print '</a>';
	                                }
	                                else
	                                {
	                                    print img_picto($langs->trans("Enabled"),'on');
	                                }
	                                print "</td>";
	                            }
	                            else
	                            {
	                                print "<td align=\"center\">\n";
	                                print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;type=paymentreceipt&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	                                print "</td>";
	                            }

	                            // Defaut
	                            print "<td align=\"center\">";
	                            if ($conf->global->PAYMENT_RECEIPT_ADDON_PDF == "$name")
	                            {
	                                print img_picto($langs->trans("Default"),'on');
	                            }
	                            else
	                            {
	                                print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;type=paymentreceipt&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	                            }
	                            print '</td>';

	                            // Info
	                            $htmltooltip =    ''.$langs->trans("Name").': '.$module->name;
	                            $htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
	                            if ($module->type == 'pdf')
	                            {
	                                $htmltooltip.='<br>'.$langs->trans("Height").'/'.$langs->trans("Width").': '.$module->page_hauteur.'/'.$module->page_largeur;
	                            }
	                            $htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
	                            $htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo,1,1);
	                            $htmltooltip.='<br>'.$langs->trans("PaymentMode").': '.yn($module->option_modereg,1,1);
	                            $htmltooltip.='<br>'.$langs->trans("PaymentConditions").': '.yn($module->option_condreg,1,1);
	                            $htmltooltip.='<br>'.$langs->trans("Escompte").': '.yn($module->option_escompte,1,1);
	                            $htmltooltip.='<br>'.$langs->trans("CreditNote").': '.yn($module->option_credit_note,1,1);
	                            $htmltooltip.='<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang,1,1);
	                            $htmltooltip.='<br>'.$langs->trans("WatermarkOnDraftInvoices").': '.yn($module->option_draft_watermark,1,1);


	                            print '<td align="center">';
	                            print $html->textwithpicto('',$htmltooltip,1,0);
	                            print '</td>';

	                            // Preview
	                            print '<td align="center">';
	                            if ($module->type == 'pdf')
	                            {
	                                print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&amp;type=paymentreceipt&module='.$name.'">'.img_object($langs->trans("Preview"),'bill').'</a>';
	                            }
	                            else
	                            {
	                                print img_object($langs->trans("PreviewNotAvailable"),'generic');
	                            }
	                            print '</td>';

	                            print "</tr>\n";
	                        }
                    	}
                    }
                }
            }
        }
    }
}
print '</table>';
?>
<script>
    $(document).ready(function() {
        $('body').append('<div id="params"></div>');
        $('#params').load('<?php print dol_buildpath('/paymentreceipt/paymentreceipt_parametrage_page.php', 1). "?backlink=/paymentreceipt/admin/setup.php&unhide=1";?>');
        $("#params").delegate("form input[type=submit]","click",function(){
            var form = $(this).parents("form");
            var data = form.serialize();
            var attr = $(this).attr("name");
            if (typeof attr !== "undefined" && attr !== false)  data+="&" +attr + "=" + $(this).val();
            $.post(form.attr("action"), data,function(result){ $("#params").html(result); })
            return false;
        });
    });
</script>
<?php
$db->close();

llxFooter();

