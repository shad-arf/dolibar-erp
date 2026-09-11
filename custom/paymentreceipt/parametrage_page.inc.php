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

$id = GETPOST('id');
$action = GETPOST('action');
$confirm = GETPOST('confirm');

//Default parameters reloading
if ($action == 'confirm_defaults' && $confirm == 'yes') {
    copy(dol_buildpath("/$modname/{$modname}_default_consts.php"), dol_buildpath("/$modname/{$modname}_consts.php"));
    header("Location: $backlink".(strstr($backlink,'?')?'&':'?')."id=$id");
    exit;
}
elseif ($confirm == 'no') {
    header('Location: ' . DOL_URL_ROOT . "$backlink?id=$id");
    exit;
} elseif (!empty($_GET["defaults"])) {
    llxHeader("");
    $html = new Form($db);
    $html->form_confirm($_SERVER['PHP_SELF'] . '?defaults=1&backlink=' . urlencode($backlink) . "&id=$id", "", $langs->trans('Loaddefaults'), 'confirm_defaults', '', 0, 2);
    return;
}

/*
 * Actions
 */

$lines = file($constsfile);
//Bi-table for dependency constants => rules the type of require(X, Y) where X and Y are constants.
//These rules are followed for the activation / deactivation of these constants.
$requirers = array();
$required = array();
//Analysis of lines and construction of the array of records "vals"
class lineClass {
    var $nom;
    var $valeur;
    var $type;
    var $intervalle_liste;
    var $defaut;
    var $message;
}
foreach ($lines as $line_num => $line) {
    $vals[$line_num] = new lineClass();
    //comment0="------- The only processed now
    //comment1="=======
    //comment2="<<<<<<<<<
    //comment3=">>>>>>>>>>>
    if (preg_match("/^\/\/require\((.*)\,(.*)\)/U", $line, $tab)) {
        $requirers[] = $tab[1];
        $required[] = $tab[2];
    } 
    elseif (preg_match("/^define\('(.*)',(.*)\);\/\/(..)(.*)\/\/(.*)\/\/(.*)$/U", $line, $tab)) {
        $noms[$line_num] = $tab[1];
        $vals[$line_num]->nom = $tab[1];
        $vals[$line_num]->valeur = $tab[2];
        $vals[$line_num]->type = $tab[3];
        if ($vals[$line_num]->type[0] == 's')
            $vals[$line_num]->valeur = substr($tab[2], 1, strlen($tab[2]) - 2);
        $vals[$line_num]->intervalle_liste = (string) $tab[4];
        $vals[$line_num]->defaut = $tab[5];
        $vals[$line_num]->message = $tab[6];
    }
    elseif (preg_match("/\/\/(.*)(-+)$/U", $line, $tab)) {
        $vals[$line_num]->nom = $tab[1];
        $vals[$line_num]->type = 'comment0';
    } 
    elseif (preg_match("/\/\/(.*)(=+)$/U", $line, $tab)) {
        $vals[$line_num]->nom = $tab[1];
        $vals[$line_num]->type = 'comment1';
    } 
    elseif (preg_match("/\/\/(.*)(<+)$/U", $line, $tab)) {
        $vals[$line_num]->nom = $tab[1];
        $vals[$line_num]->type = 'comment2';
    } 
    elseif (preg_match("/\/\/(.*)(>+)$/U", $line, $tab)) {
        $vals[$line_num]->nom = $tab[1];
        $vals[$line_num]->type = 'comment3';
    }
}
if (!empty($_GET["modify"])) { //	modification de paramétres logiques
    $lines[$_GET["index"]] = preg_replace("/(.*)\',(.*)\)(.*)$/U", '$1\',' . (($vals[$_GET["index"]]->valeur > 0) ? 0 : 1) . ')$3', $lines[$_GET["index"]]);
    $vals[$_GET["index"]]->valeur = ($vals[$_GET["index"]]->valeur > 0) ? 0 : 1;
    $key = array_search($vals[$_GET["index"]]->nom, $requirers);

    if (is_numeric($key) && $vals[$_GET["index"]]->valeur > 0) {//si valeur dans requirers et activation
        $key1 = array_search($required[$key], $noms);
        $lines[$key1] = preg_replace("/(.*)\',(.*)\)(.*)$/U", '$1\',1)$3', $lines[$key1]);
        $vals[$key1]->valeur = 1;
    }
    $key = array_search($vals[$_GET["index"]]->nom, $required);
    if (is_numeric($key) && $vals[$_GET["index"]]->valeur == 0) {//si valeur dans required et désactivation
        $keys = array_keys($required, $vals[$_GET["index"]]->nom);
        foreach ($keys as $val) {
            $key = array_search($requirers[$val], $noms);
            $lines[$key] = preg_replace("/(.*)\',(.*)\)(.*)$/U", '$1\',0)$3', $lines[$key]);
            $vals[$key]->valeur = 0;
        }
    }
    sauve_config($lines, $constsfile);
    return 1;
} 
if (!empty($_GET["hiding"])) { //	modification de paramétres logiques
    $lines[$_GET["index"]] = preg_replace("/\((.*)\)(.*)\/\/..(...)?\/\//U", '($1)$2//' . $vals[$_GET["index"]]->type[0].($vals[$_GET["index"]]->type[1]=='h'?'m':'h').'$3//', $lines[$_GET["index"]]);
    $vals[$_GET["index"]]->type[1] = $vals[$_GET["index"]]->type[1]=='h'?'m':'h';
    sauve_config($lines, $constsfile);
    return 1;
} 
elseif (!empty($_POST["modify"])) { //	modification of natural or string parameters
    if ($vals[$_POST["index"]]->type[0] == 's') {
        $lines[$_POST["index"]] = preg_replace("/(.*),\'(.*)\'(.*)$/U", '$1,\'' . $_POST["const"][$_POST["index"]] . '\'$3', $lines[$_POST["index"]]);
        $vals[$_POST["index"]]->valeur = $_POST["const"][$_POST["index"]];
    } 
    else if ($vals[$_POST["index"]]->type[0] == 'b') {
        $lines[$_POST["index"]] = preg_replace("/(.*)\',(.*)\)(.*)$/U", '$1\',' . (($vals[$_POST["index"]]->valeur > 0) ? 0 : 1) . ')$3', $lines[$_POST["index"]]);
        $vals[$_POST["index"]]->valeur = ($vals[$_POST["index"]]->valeur > 0) ? 0 : 1;
        $key = array_search($vals[$_POST["index"]]->nom, $requirers);

        if (is_numeric($key) && $vals[$_POST["index"]]->valeur > 0) {//si valeur dans requirers et activation
            $key1 = array_search($required[$key], $noms);
            $lines[$key1] = preg_replace("/(.*)\',(.*)\)(.*)$/U", '$1\',1)$3', $lines[$key1]);
            $vals[$key1]->valeur = 1;
        }
        $key = array_search($vals[$_POST["index"]]->nom, $required);
        if (is_numeric($key) && $vals[$_POST["index"]]->valeur == 0) {//si valeur dans required et désactivation
            $keys = array_keys($required, $vals[$_POST["index"]]->nom);
            foreach ($keys as $val) {
                $key = array_search($requirers[$val], $noms);
                $lines[$key] = preg_replace("/(.*)\',(.*)\)(.*)$/U", '$1\',0)$3', $lines[$key]);
                $vals[$key]->valeur = 0;
            }
        }
    } else {
        $lines[$_POST["index"]] = preg_replace("/(.*)\',(.*)\)(.*)$/U", '$1\',' . $_POST["const"][$_POST["index"]] . ')$3', $lines[$_POST["index"]]);
        $vals[$_POST["index"]]->valeur = $_POST["const"][$_POST["index"]];
    }
    sauve_config($lines, $constsfile);
    header('Location: ' . $backlink);
}
//recovery of the former position in the page
$scrollx = GETPOST('scrollx');
$scrolly = GETPOST('scrolly');
?>
<script type="text/javascript" language="javascript">
    function valider(chartype, index, ref)
    {
        ref = ref || "";
        var section = document.getElementById("element" + index);
        var valeur = document.getElementById("element" + index).value;
        if (chartype == 's')
        {
            if ((v = valeur.search(/[\u0027\u0022\\]/)) > -1)
            {
                alert(" Caractere non accepté..." + v);
                return false;
            }
        }
        else if (chartype == 'i')
        {	//alert("i "+ref);
            var words = ref.split("-");
            if ((parseFloat(words[0]) > parseFloat(valeur)) || (parseFloat(words[1]) < parseFloat(valeur)))
            {
                alert(" Valeur hors intervalle : " + ref);
                return false;
            }
        } else if (chartype == 'b')
        {	//alert("i "+ref);
            if (valeur != 0 && valeur != 1)
            {
                alert("Valeurs possibles 0 ou 1...");
                return false;
            }
        }

    }
</script>
<?php
print '<body id="mainbody">';
if (!empty($conf->use_javascript_ajax)) {
    if (!empty($conf->global->MAIN_MENU_USE_JQUERY_LAYOUT)) {
        print '<script type="text/javascript">
			jQuery(document).ready(function () {
				jQuery("body").layout( layoutSettings );
			});
			var layoutSettings = {
				name: "mainlayout",
				defaults: {
					useStateCookie: true,
					size: "auto",
					resizable: false,
					//paneClass: "none",
					//resizerClass: "resizer",
					//togglerClass: "toggler",
					//buttonClass: "button",
					//contentSelector: ".content",
					//contentIgnoreSelector: "span",
					togglerTip_open: "Close This Pane",
					togglerTip_closed: "Open This Pane",
					resizerTip:	"Resize This Pane"
				},
				west: {
					paneClass: "leftContent",
					spacing_closed:	14,
					togglerLength_closed: 14,
					togglerAlign_closed: "top",
					//togglerLength_open: 0,
					//	effect defaults - overridden on some panes
					//slideTrigger_open:	"mouseover",
					//initClosed:	true,
					fxName:	"drop",
					fxSpeed: "normal",
					fxSettings: { easing: "" }
				},
				north: {
					paneClass: "none",
					resizerClass: "none",
					togglerClass: "none",
					spacing_open: 0,
					togglerLength_open:	0,
					togglerLength_closed: -1,
					slidable: false,
					fxName:	"none"
				},
				center: {
					paneSelector: "#mainContent"
				}
			}
		</script>';
    }

    if (!empty($conf->global->MAIN_MENU_USE_JQUERY_ACCORDION)) {
        print "\n" . '<script type="text/javascript">
				jQuery(document).ready(function () {
					jQuery( ".vmenu" ).accordion({
						autoHeight: false,
						event: "mouseover",
						//collapsible: true,
						//active: 2,
						header: "> .blockvmenupair > .menu_titre"
					});
				});
				</script>';
    }

    // Wrapper to show tooltips
    print "\n" . '<script type="text/javascript">
				jQuery(document).ready(function () {
					jQuery(function() {
						$(".classfortooltip").tipTip({maxWidth: "600px", edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50});
					});
				});
			</script>';
}
print '<p class="titre"><b><u>' . $langs->trans("sectionParametrage") . '</u></b></p>';

$form = new Form($db);
print '<table>';
$noparam=1;//Numéro de ligne
$intable=false;
$tohide = 0;
$var = false;
foreach ($lines as $line_num => $line) {
    $line = trim($line);
    $value = $vals[$line_num]->nom;
    $a = explode("_", $value);
    if (isset($a[1]))  $modname = $a[1];
    if (!in_array($modname, $params) || in_array($value, $outdef) || isset($indef[$modname]) && is_array($indef[$modname] && !in_array($value, $indef[$modname])))
        continue;
    if (!empty($vals[$line_num]->nom)) {
        if ($vals[$line_num]->type == "comment0") {
            if ($intable)
                print '</table>';
            print '<table >';
            $intable = true;
            $info = $vals[$line_num]->nom . '_INFO';
            $trinfo = $langs->trans($info);
            $tohide++;
            print "<tr><td>";
            print '<div class="titre" onclick="
				var str=$(this).children(\'img\').attr(\'src\');
				if (str.match(/1rightarrow.png/g)) {
					str=str.replace(\'1rightarrow.png\',\'1downarrow.png\');
					$(\'#tohide' . $tohide . '\').show();
				}	
				else {
					str=str.replace(\'1downarrow.png\',\'1rightarrow.png\');
					$(\'#tohide' . $tohide . '\').hide();
				}	
				$(this).children(\'img\').attr(\'src\',str);
				"><img src=' . DOL_URL_ROOT . '/theme/eldy/img/1rightarrow.png>' . ucfirst($langs->trans($vals[$line_num]->nom)) . '</div>';
            print "</td><td>";
            if ($info != $trinfo) {
                print $form->textwithpicto('', ucfirst($trinfo), 1, 0);
            }
            print '</td></tr>';
            print '</table>';
            print '<table class="noborder tohide" id="tohide' . $tohide . '" width="100%">';
            print "<tr class=\"liste_titre\">";
            print '  <td width="100">' . $langs->trans("Name") . '</td>';

            $inbloc = '';
            switch ($inbloc) {
                case 'L':
                    print '  <td width="600">' . $langs->trans("Description") . '</td>';
                    print '  <td align="center">' . $langs->trans("Status") . '</td>';
                    break;
                case 'S':
                case 'I':
                    print '  <td width="400">' . $langs->trans("Description") . '</td>';
                    print '  <td align="left" width="200">' . $langs->trans("Value") . '</td>';
                    break;
            }
            print '  <td>' . $langs->trans("Infos") . '</td>';
            print '  <td>' . $langs->trans("Value") . '</td>';
            print '  <td align="center" width="60" colspan=2></td>';
            if ($_GET['unhide']) print '  <td>' . $langs->trans("Active") . '</td>';
            print "</tr>";
            continue;
        } 
        elseif ($vals[$line_num]->type == "comment1") {
            print "<br>";
            switch ($vals[$line_num]->nom) {
                case "param_Logiques":
                    $inbloc = 'L';
                    break;
                case "param_Strings":
                    $inbloc = 'S';
                    break;
                case "param_IntervallesNumeriques":
                    $inbloc = 'I';
                    break;
            }
            if ($intable)
                print '</table>';
            $intable = false;
            print '<br>';
            print_titre('<B><U>' . ucfirst($langs->trans($vals[$line_num]->nom)) . '</U></B>');
            continue;
        }
        elseif ($vals[$line_num]->type == "comment3") {
            print "<br>";
            print_titre('<B>' . ucfirst($langs->trans($vals[$line_num]->nom)) . '</B>');
            continue;
        } 
        elseif ($vals[$line_num]->type == "comment2") {
            print "<br>";
            print_titre('<B>' . ucfirst($langs->trans($vals[$line_num]->nom)) . '</B>');
            continue;
        }
        $var = !$var;
        preg_match('/^.*_(.+)$/', $vals[$line_num]->nom, $matches);
        $developpeur = $matches[1];
        $nom = substr($vals[$line_num]->nom, 0, -strlen($developpeur)) . 'NAME_' . $developpeur;
        $trnom = $langs->trans($nom);
        $ref = $vals[$line_num]->nom;
        $trref = $langs->trans($vals[$line_num]->nom);
        if ($_GET['unhide'] || $vals[$line_num]->type[1] != 'h') {
            print "<tr " . $bc[$var] . ">\n  <td>" . $noparam++ . ": " . (($nom != $trnom) ? $trnom : " ") . "</td>\n";
            print "<td nowrap>" . (($ref != $trref) ? ucfirst($langs->trans($ref)) : '') . "</td>\n";
            if ($vals[$line_num]->type[1] == 'm') {
                if ($vals[$line_num]->type[0] == 's') {
                    print '<td>';
                    print '<form action="' . $_SERVER["PHP_SELF"]. '" method="POST" name="form">';
                    print '<input type="hidden" name="index" value="' . $line_num . '">';
                    print '<input type="hidden" name="backlink" value="' . $backlink . '">';
                    print '<input type="hidden" name="scrollx" >';
                    print '<input type="hidden" name="scrolly" >';
                    print '<input type="hidden" name="opentab" value="tohide' . $tohide . '">';
                    print '<input type="text" SIZE=5  id="element' . $line_num . '"name="const[' . $line_num . ']" value="' . $vals[$line_num]->valeur . '"></input>';
                    print '<input type="submit" name="modify" value="' . $langs->trans("Modify") . '" onclick="return valider(\'s\',' . $line_num . ',\'x\')" >';
                    print '</form>';
                    print '</td><td>';
                    $info = substr($vals[$line_num]->nom, 0, -strlen($developpeur)) . 'INFO_' . $developpeur;
                    $trinfo = $langs->trans($info);
                    print '</td><td>';
                    if ($info != $trinfo)
                        print $form->textwithpicto('', ucfirst($trinfo), 1);
                    print '</td>';
                }
                elseif ($vals[$line_num]->type[0] == 'i') {
                    print '<td>';
                    print '<form action="' . $_SERVER["PHP_SELF"]. '" method="POST" name="form">';
                    print '<input type="hidden" name="index" value="' . $line_num . '">';
                    print '<input type="hidden" name="backlink" value="' . $backlink . '">';
                    print '<input type="hidden" name="scrollx" >';
                    print '<input type="hidden" name="scrolly" >';
                    print '<input type="hidden" name="opentab" value="tohide' . $tohide . '">';
                    print '<input type="text" SIZE=5  id="element' . $line_num . '"name="const[' . $line_num . ']" value="' . $vals[$line_num]->valeur . '"></input>';
                    print '<input type="submit" name="modify" value="' . $langs->trans("Modify") . '" onclick="return valider(\'i\',' . $line_num . ',\'' . $vals[$line_num]->intervalle_liste . '\')" >';
                    print '</form>';
                    print '</td><td>';
                    $info = substr($vals[$line_num]->nom, 0, -strlen($developpeur)) . 'INFO_' . $developpeur;
                    $trinfo = $langs->trans($info);
                    print '</td><td>';
                    if ($info != $trinfo)
                        print $form->textwithpicto('', ucfirst($trinfo), 1);
                    print '</td>';
                }
                else if ($vals[$line_num]->type[0] == 'b') {
                    print '<td align="center" >';
                    print '<a onclick="
                    var str=$(this).children(\'img\').attr(\'src\');
                    var title;
                    if (str.match(/on.png/g)) {
                        str=str.replace(\'on.png\',\'off.png\');
                        title=\'' . $langs->trans("Disabled") . '\';
                    }	
                    else {
                        str=str.replace(\'off.png\',\'on.png\');
                        title=\'' . $langs->trans("Activated") . '\';
                    }							
                    $(this).children(\'img\').attr(\'src\',str);
                    $(this).children(\'img\').attr(\'title\',title);
                    $.get(&#39' . preg_replace('/\//', '&#47', $_SERVER['PHP_SELF']) . '?modify=modif&amp;index=' . $line_num . '&#39).success(function() {
                        $.jnotify(\'' . $langs->trans('ParametreModifie') . '\');
                    });
                    return false;
                    ">';
                    if (($vals[$line_num]->valeur > 0))
                        print img_picto($langs->trans("Activated"), 'switch_on');
                    else
                        print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a></td><td align="center">';
                    $info = substr($vals[$line_num]->nom, 0, -strlen($developpeur)) . 'INFO_' . $developpeur;
                    $trinfo = $langs->trans($info);
                    print '</td><td>';
                    if ($info != $trinfo)
                        print $form->textwithpicto('', ucfirst($trinfo), 1);
                    print '</td>';
                }
            }
            elseif ($vals[$line_num]->type[1] == 'u') {
                if ($vals[$line_num]->type[0] == 's')
                    print '<td></td><td>' . $vals[$line_num]->valeur . '</td></tr>';
                else if ($vals[$line_num]->type[0] == 'i')
                    print '<td></td><td>' . $vals[$line_num]->intervalle_liste . ":" . $vals[$line_num]->valeur . '</td></tr>';
                else if ($vals[$line_num]->type[0] == 'b') {
                    print '<td align="center" ' . ($vals[$line_num]->valeur > 0 ? 'style="background-color:#00FF00"' : ' style="background-color:#FF0000"') . '>';
                    print $langs->trans("Unmodifiable");
               }
                print '</a></td><td align="center">';
                $info = substr($vals[$line_num]->nom, 0, -strlen($developpeur)) . 'INFO_' . $developpeur;
                $trinfo = $langs->trans($info);
                print '</td><td>';
                if ($info != $trinfo)
                    print $form->textwithpicto('', ucfirst($trinfo), 1);
                print '</td>';
            }elseif ($vals[$line_num]->type[1] == 'h') {
                if ($vals[$line_num]->type[0] == 's') {
                    print '<td>';
                    print '<form action="' . $_SERVER["PHP_SELF"]. '" method="POST" name="form">';
                    print '<input type="hidden" name="index" value="' . $line_num . '">';
                    print '<input type="hidden" name="backlink" value="' . $backlink . '">';
                    print '<input type="hidden" name="scrollx" >';
                    print '<input type="hidden" name="scrolly" >';
                    print '<input type="hidden" name="opentab" value="tohide' . $tohide . '">';
                    print '<input type="text" SIZE=5  id="element' . $line_num . '"name="const[' . $line_num . ']" value="' . $vals[$line_num]->valeur . '"></input>';
                    print '<input type="submit" name="modify" value="' . $langs->trans("Modify") . '" onclick="return valider(\'s\',' . $line_num . ',\'x\')" >';
                    print '</form>';
                    print '</td><td>';
                    $info = substr($vals[$line_num]->nom, 0, -strlen($developpeur)) . 'INFO_' . $developpeur;
                    $trinfo = $langs->trans($info);
                    print '</td><td>';
                    if ($info != $trinfo)
                        print $form->textwithpicto('', ucfirst($trinfo), 1);
                    print '</td>';
                }
                elseif ($vals[$line_num]->type[0] == 'i') {
                    print '<td>';
                    print '<form action="' . $_SERVER["PHP_SELF"]. '" method="POST" name="form">';
                    print '<input type="hidden" name="index" value="' . $line_num . '">';
                    print '<input type="hidden" name="backlink" value="' . $backlink . '">';
                    print '<input type="hidden" name="scrollx" >';
                    print '<input type="hidden" name="scrolly" >';
                    print '<input type="hidden" name="opentab" value="tohide' . $tohide . '">';
                    print '<input type="text" SIZE=5  id="element' . $line_num . '"name="const[' . $line_num . ']" value="' . $vals[$line_num]->valeur . '"></input>';
                    print '<input type="submit" name="modify" value="' . $langs->trans("Modify") . '" onclick="return valider(\'i\',' . $line_num . ',\'' . $vals[$line_num]->intervalle_liste . '\')" >';
                    print '</form>';
                    print '</td><td>';
                    $info = substr($vals[$line_num]->nom, 0, -strlen($developpeur)) . 'INFO_' . $developpeur;
                    $trinfo = $langs->trans($info);
                    print '</td><td>';
                    if ($info != $trinfo)
                        print $form->textwithpicto('', ucfirst($trinfo), 1);
                    print '</td>';
                }
                else if ($vals[$line_num]->type[0] == 'b') {
                    print '<td align="center" >';
                    print '<a onclick="
                    var str=$(this).children(\'img\').attr(\'src\');
                    var title;
                    if (str.match(/on.png/g)) {
                        str=str.replace(\'on.png\',\'off.png\');
                        title=\'' . $langs->trans("Disabled") . '\';
                    }	
                    else {
                        str=str.replace(\'off.png\',\'on.png\');
                        title=\'' . $langs->trans("Activated") . '\';
                    }							
                    $(this).children(\'img\').attr(\'src\',str);
                    $(this).children(\'img\').attr(\'title\',title);
                    $.get(&#39' . preg_replace('/\//', '&#47', $_SERVER['PHP_SELF']) . '?modify=modif&amp;index=' . $line_num . '&#39).success(function() {
                        $.jnotify(\'' . $langs->trans('ParametreModifie') . '\');
                    });
                    return false;
                    ">';
                    if ($vals[$line_num]->valeur > 0)
                        print img_picto($langs->trans("Activated"), 'switch_on');
                    else
                        print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a></td><td align="center">';
                    $info = substr($vals[$line_num]->nom, 0, -strlen($developpeur)) . 'INFO_' . $developpeur;
                    $trinfo = $langs->trans($info);
                    print '</td><td>';
                    if ($info != $trinfo)
                        print $form->textwithpicto('', ucfirst($trinfo), 1);
                    print '</td>';
                }                
            }
            if ($_GET['unhide']){
                print '<td>';
                print '<a onclick="
                    var str=$(this).children(\'img\').attr(\'src\');
                    var title;
                    if (str.match(/on.png/g)) {
                        str=str.replace(\'on.png\',\'off.png\');
                        title=\'' . $langs->trans("Disabled") . '\';
                    }	
                    else {
                        str=str.replace(\'off.png\',\'on.png\');
                        title=\'' . $langs->trans("Activated") . '\';
                    }							
                    $(this).children(\'img\').attr(\'src\',str);
                    $(this).children(\'img\').attr(\'title\',title);
                    $.get(&#39' . preg_replace('/\//', '&#47', $_SERVER['PHP_SELF']) . '?hiding=hiding&amp;index=' . $line_num . '&#39).success(function() {
                        $.jnotify(\'' . $langs->trans('ParametreModifie') . '\');
                    });
                    return false;
                    ">';
                if ($vals[$line_num]->type[1] != 'h')
                    print img_picto($langs->trans("Activated"), 'switch_on');
                else
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                print '</a></td><td align="center">';
                print '</td>';
            }
            print '</tr>';

        }
    }
}
print '</table><br/>';
if ($_GET['unhide']) print '<a style="position:float" class="button" href="' . $_SERVER["PHP_SELF"]. '?defaults=1&backlink=' . urlencode($backlink) . '" >' . $langs->trans("loaddefaultparameters") . '</a><br/><br/><br/>';
print '<script>$(".tohide").hide();';
//reopen previously open Jquery parameter class
if ($_POST['opentab'])
    print '$(\'#' . $_POST['opentab'] . '\').show();';
if ($scrollx || $scrolly)
    print 'window.scrollTo(' . $scrollx . ',' . $scrolly . ');';
print '</script>';
print "\n</body>\n</html>\n";

/** 	Function to save the current configuration to the file of constants
 *  @return	void
 */
function sauve_config($lines, $constsfile) {
    //reset PHP cache if opcache installed
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    $handle = fopen($constsfile, 'w');
    foreach ($lines as $line_num => $line)
        fwrite($handle, trim($line) . "\n");
    fclose($handle);
}
