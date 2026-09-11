<?php
/* Copyright (C) 2016 	   Abbes Bahfir 		<bafbes@gmail.com>
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
 * or see http://www.gnu.org/
 */

if(isset($_COOKIE['dolconf']) && $_COOKIE['dolconf'] != 'conf' && $_COOKIE['dolconf'] != '') $constsfile = '_' . $_COOKIE['dolconf'];
else $constsfile = '';
$constsfile = dol_buildpath(empty($conf->global->MAIN_MODULE_PARAMETRAGE) ? '/paymentreceipt/paymentreceipt_consts' . $constsfile . '.php' : "/parametrage/clientconsts'.$constsfile.'.php");
if(file_exists($constsfile)) $res = @include_once $constsfile;
else @include_once dol_buildpath('paymentreceipt/paymentreceipt_consts.php');
// Load all needed dictionaries
$langs->load("main");
$langs->load("bills");
$langs->load("companies");
$langs->load("products");
$langs->load("paymentreceipt@paymentreceipt");


/**
 *      Return a string to show the box with list of available documents for paymentreceipt object.
 *      This also set the property $this->numoffiles
 *
 * @param      string $modulepart Module the files are related to ('propal', 'facture', 'facture_fourn', 'mymodule', 'mymodule_temp', ...)
 * @param      string $modulesubdir Existing (so sanitized) sub-directory to scan (Example: '0/1/10', 'FA/DD/MM/YY/9999'). Use '' if file is not into subdir of module.
 * @param      string $filedir Directory to scan
 * @param      string $urlsource Url of origin page (for return)
 * @param      int $genallowed Generation is allowed (1/0 or array list of templates)
 * @param      int $delallowed Remove is allowed (1/0)
 * @param      string $modelselected Model to preselect by default
 * @param      integer $allowgenifempty Allow generation even if list of template ($genallowed) is empty (show however a warning)
 * @param      integer $forcenomultilang Do not show language option (even if MAIN_MULTILANGS defined)
 * @param      int $iconPDF Deprecated, see getDocumentsLink
 * @param        int $notused Not used
 * @param        integer $noform Do not output html form tags
 * @param        string $param More param on http links
 * @param        string $title Title to show on top of form
 * @param        string $buttonlabel Label on submit button
 * @param        string $codelang Default language code to use on lang combo box if multilang is enabled
 * @param        string $morepicto Add more HTML content into cell with picto
 * @param      Object $object Object when method is called from an object card.
 * @return        string                                Output string with HTML array of documents (might be empty string)
 */
function showdocuments($db, $modulepart, $modulesubdir, $filedir, $urlsource, $genallowed, $delallowed = 0, $modelselected = '', $allowgenifempty = 1, $forcenomultilang = 0, $iconPDF = 0, $notused = 0, $noform = 0, $param = '', $title = '', $buttonlabel = '', $codelang = '', $morepicto = '', $object = null)
{
    $formfile = new formfile($db);
// Deprecation warning
    if(0 !== $iconPDF) {
        dol_syslog(__METHOD__ . ": passing iconPDF parameter is deprecated", LOG_WARNING);
    }

    global $langs, $conf, $user, $hookmanager;
    global $form, $bc;

    if(!is_object($form)) $form = new Form($db);

    include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

    // For backward compatibility
    if(!empty($iconPDF)) {
        return $this->getDocumentsLink($modulepart, $modulesubdir, $filedir);
    }

    $printer = 0;
    $forname = 'builddoc';
    $out = '';

    $headershown = 0;
    $showempty = 0;
    $i = 0;

    $out .= "\n" . '<!-- Start show_document -->' . "\n";
    //print 'filedir='.$filedir;

    $titletoshow = $langs->trans("Documents");
    if(!empty($title)) $titletoshow = $title;

    // Show table
    if($genallowed) {
        $modellist = array();

        $showempty = 1;
        if(is_array($genallowed)) $modellist = $genallowed;
        else {
            include_once dol_buildpath('paymentreceipt/core/modules/paymentreceipt/modules_paymentreceipt.php');
            $modellist = ModelePayment_receipt::liste_modeles($db);
        }

        // Set headershown to avoit to have table opened a second time later
        $headershown = 1;

        $buttonlabeltoshow = $buttonlabel;
        if(empty($buttonlabel)) $buttonlabel = $langs->trans('Generate');

        if($conf->browser->layout == 'phone') $urlsource .= '#' . $forname . '_form';   // So we switch to form after a generation
        if(empty($noform)) $out .= '<form action="' . $urlsource . (empty($conf->global->MAIN_JUMP_TAG) ? '' : '#builddoc') . '" id="' . $forname . '_form" method="post">';
        $out .= '<input type="hidden" name="action" value="builddoc">';
        $out .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';

        $out .= load_fiche_titre($titletoshow, '', '');
        $out .= '<div class="div-table-responsive-no-min">';
        $out .= '<table class="liste formdoc noborder" summary="listofdocumentstable" width="100%">';

        $out .= '<tr class="liste_titre">';

        $addcolumforpicto = ($delallowed || $printer || $morepicto);
        $out .= '<th align="center" colspan="' . (3 + ($addcolumforpicto ? '2' : '1')) . '" class="formdoc liste_titre maxwidthonsmartphone">';

        // Model
        if(!empty($modellist)) {
            $out .= '<span class="hideonsmartphone">' . $langs->trans('Model') . ' </span>';
            if(is_array($modellist) && count($modellist) == 1)    // If there is only one element
            {
                $arraykeys = array_keys($modellist);
                $modelselected = $arraykeys[0];
            }
            $out .= $form->selectarray('model', $modellist, $modelselected, $showempty, 0, 0, '', 0, 0, 0, '', 'minwidth100');
            $out .= ajax_combobox('model');
        }
        else {
            $out .= '<div class="float">' . $langs->trans("Files") . '</div>';
        }

        // Language code (if multilang)
        if(($allowgenifempty || (is_array($modellist) && count($modellist) > 0)) && $conf->global->MAIN_MULTILANGS && !$forcenomultilang && (!empty($modellist) || $showempty)) {
            include_once DOL_DOCUMENT_ROOT . '/core/class/html.formadmin.class.php';
            $formadmin = new FormAdmin($db);
            $defaultlang = $codelang ? $codelang : $langs->getDefaultLang();
            $morecss = 'maxwidth150';
            if(!empty($conf->browser->phone)) $morecss = 'maxwidth100';
            $out .= $formadmin->select_language($defaultlang, 'lang_id', 0, 0, 0, 0, 0, $morecss);
        }
        else {
            $out .= '&nbsp;';
        }

        // Button
        $genbutton = '<input class="button buttongen" id="' . $forname . '_generatebutton" name="' . $forname . '_generatebutton"';
        $genbutton .= ' type="submit" value="' . $buttonlabel . '"';
        if(!$allowgenifempty && !is_array($modellist) && empty($modellist)) $genbutton .= ' disabled';
        $genbutton .= '>';
        if($allowgenifempty && !is_array($modellist) && empty($modellist) && empty($conf->dol_no_mouse_hover) && $modulepart != 'unpaid') {
            $langs->load("errors");
            $genbutton .= ' ' . img_warning($langs->transnoentitiesnoconv("WarningNoDocumentModelActivated"));
        }
        if(!$allowgenifempty && !is_array($modellist) && empty($modellist) && empty($conf->dol_no_mouse_hover) && $modulepart != 'unpaid') $genbutton = '';
        if(empty($modellist) && !$showempty && $modulepart != 'unpaid') $genbutton = '';
        $out .= $genbutton;
        $out .= '</th>';

        $out .= '</tr>';

    }

    // Get list of files
    if(!empty($filedir)) {
        $file_list = dol_dir_list($filedir, 'files', 0, '', '(\.meta|_preview.*.*\.png)$', 'date', SORT_DESC);

        $link_list = array();
        if(is_object($object)) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
            $link = new Link($db);
            $sortfield = $sortorder = null;
            $res = $link->fetchAll($link_list, $object->element, $object->id, $sortfield, $sortorder);
        }

        $out .= '<!-- html.formfile::showdocuments -->' . "\n";

        // Show title of array if not already shown
        if((!empty($file_list) || !empty($link_list) || preg_match('/^massfilesarea/', $modulepart)) && !$headershown) {
            $headershown = 1;
            $out .= '<div class="titre">' . $titletoshow . '</div>' . "\n";
            $out .= '<div class="div-table-responsive-no-min">';
            $out .= '<table class="noborder" summary="listofdocumentstable" id="' . $modulepart . '_table" width="100%">' . "\n";
        }

        // Loop on each file found
        if(is_array($file_list)) {
            foreach($file_list as $file) {
                // Define relative path for download link (depends on module)
                $relativepath = $file["name"];                                        // Cas general
                if($modulesubdir) $relativepath = $modulesubdir . "/" . $file["name"];    // Cas propal, facture...
                if($modulepart == 'export') $relativepath = $file["name"];            // Other case

                $out .= '<tr class="oddeven">';

                $documenturl = DOL_URL_ROOT . '/document.php';
                if(isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP)) $documenturl = $conf->global->DOL_URL_ROOT_DOCUMENT_PHP;    // To use another wrapper

                // Show file name with link to download
                $out .= '<td class="tdoverflowmax300">';
                $tmp = $formfile->showPreview($file, $modulepart, $relativepath, 0, $param);
                $out .= ($tmp ? $tmp . ' ' : '');
                $out .= '<a class="documentdownload" href="' . $documenturl . '?modulepart=' . $modulepart . '&amp;file=' . urlencode($relativepath) . ($param ? '&' . $param : '') . '"';
                $mime = dol_mimetype($relativepath, '', 0);
                if(preg_match('/text/', $mime)) $out .= ' target="_blank"';
                $out .= ' target="_blank">';
                $out .= img_mime($file["name"], $langs->trans("File") . ': ' . $file["name"]) . ' ' . $file["name"];
                $out .= '</a>' . "\n";
                $out .= '</td>';

                // Show file size
                $size = (!empty($file['size']) ? $file['size'] : dol_filesize($filedir . "/" . $file["name"]));
                $out .= '<td align="right" class="nowrap">' . dol_print_size($size) . '</td>';

                // Show file date
                $date = (!empty($file['date']) ? $file['date'] : dol_filemtime($filedir . "/" . $file["name"]));
                $out .= '<td align="right" class="nowrap">' . dol_print_date($date, 'dayhour', 'tzuser') . '</td>';

                if($delallowed || $printer || $morepicto) {
                    $out .= '<td align="right">';
                    if($delallowed) {
                        $out .= '<a href="' . $urlsource . (strpos($urlsource, '?') ? '&amp;' : '?') . 'action=remove_file&amp;file=' . urlencode($relativepath);
                        $out .= ($param ? '&amp;' . $param : '');
                        //$out.= '&modulepart='.$modulepart; // TODO obsolete ?
                        //$out.= '&urlsource='.urlencode($urlsource); // TODO obsolete ?
                        $out .= '">' . img_picto($langs->trans("Delete"), 'delete.png') . '</a>';
                        //$out.='</td>';
                    }
                    if($printer) {
                        //$out.= '<td align="right">';
                        $out .= '&nbsp;<a href="' . $urlsource . (strpos($urlsource, '?') ? '&amp;' : '?') . 'action=print_file&amp;printer=' . $modulepart . '&amp;file=' . urlencode($relativepath);
                        $out .= ($param ? '&amp;' . $param : '');
                        $out .= '">' . img_picto($langs->trans("PrintFile", $relativepath), 'printer.png') . '</a>';
                    }
                    if($morepicto) {
                        $morepicto = preg_replace('/__FILENAMEURLENCODED__/', urlencode($relativepath), $morepicto);
                        $out .= $morepicto;
                    }
                    $out .= '</td>';
                }

                if(is_object($hookmanager)) {
                    $parameters = array('socid' => (isset($GLOBALS['socid']) ? $GLOBALS['socid'] : ''), 'id' => (isset($GLOBALS['id']) ? $GLOBALS['id'] : ''), 'modulepart' => $modulepart, 'relativepath' => $relativepath);
                    $res = $hookmanager->executeHooks('formBuilddocLineOptions', $parameters, $file);
                    if(empty($res)) {
                        $out .= $hookmanager->resPrint;        // Complete line
                        $out .= '</tr>';
                    }
                    else $out = $hookmanager->resPrint;        // Replace line
                }
            }

            $numoffiles++;
        }
        // Loop on each file found
        if(is_array($link_list)) {
            $colspan = 2;

            foreach($link_list as $file) {
                $out .= '<tr class="oddeven">';
                $out .= '<td colspan="' . $colspan . '" class="maxwidhtonsmartphone">';
                $out .= '<a data-ajax="false" href="' . $link->url . '" target="_blank">';
                $out .= $file->label;
                $out .= '</a>';
                $out .= '</td>';
                $out .= '<td align="right">';
                $out .= dol_print_date($file->datea, 'dayhour');
                $out .= '</td>';
                if($delallowed || $printer || $morepicto) $out .= '<td></td>';
                $out .= '</tr>' . "\n";
            }
            $numoffiles++;
        }

        if(count($file_list) == 0 && count($link_list) == 0 && $headershown) {
            $out .= '<tr class="oddeven"><td colspan="3" class="opacitymedium">' . $langs->trans("None") . '</td></tr>' . "\n";
        }

    }

    if($headershown) {
        // Affiche pied du tableau
        $out .= "</table>\n";
        $out .= "</div>\n";
        if($genallowed) {
            if(empty($noform)) $out .= '</form>' . "\n";
        }
    }
    $out .= '<!-- End show_document -->' . "\n";
    //return ($i?$i:$headershown);
    return $out;
}
