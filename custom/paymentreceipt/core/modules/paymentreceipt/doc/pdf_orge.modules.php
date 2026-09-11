<?php

/* Copyright (C) 2004-2010 Laurent Destailleur    <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin          <regis@dolibarr.fr>
 * Copyright (C) 2008      Raphael Bertrand       <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010      Juanjo Menent		  <jmenent@2byte.es>
 * Copyright (C) 2016      Bahfir Abbes           <dolipar@dolipar.org>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
 * 	\file       htdocs/includes/modules/paiement/pdf_orge.modules.php
 * 	\ingroup    facture
 * 	\brief      File of class to generate invoices from crab model
 * 	\author	    Laurent Destailleur
 * 	\version    $Id: pdf_orge.modules.php,v 1.298 2011/01/03 09:50:08 eldy Exp $
 */
require_once DOL_DOCUMENT_ROOT . "/core/class/commondocgenerator.class.php";
require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');

/**
 * 	\class      pdf_orge
 * 	\brief      Classe permettant de generer les paiements au modele orge
 */
class pdf_orge extends CommonDocGenerator {

    var $emetteur; // Objet societe qui emet

    /**
     * 		Constructor
     * 		@param		db		Database access handler
     */

    function pdf_orge($db) {
        global $conf, $langs, $mysoc;

        $this->db = $db;
        $this->name = "orge";
        $this->description = $langs->trans('PDForgeDescription');

        // Dimension page pour format A4
        $this->type = 'pdf';
        $this->page_largeur = 210;
        $this->page_hauteur = 148.5;
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche = 10;
        $this->marge_droite = 10;
        $this->marge_haute = 10;
        $this->marge_basse = 10;

        $this->option_logo = 1;                    // Affiche logo
        $this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
        $this->option_modereg = 1;                 // Affiche mode reglement
        $this->option_condreg = 1;                 // Affiche conditions reglement
        $this->option_codeproduitservice = 1;      // Affiche code produit-service
        $this->option_multilang = 1;               // Dispo en plusieurs langues
        $this->option_escompte = 1;                // Affiche si il y a eu escompte
        $this->option_credit_note = 1;             // Support credit notes
        $this->option_freetext = 1;       // Support add of a personalised text

        $this->franchise = !$mysoc->tva_assuj;

        // Get source company
        $this->emetteur = $mysoc;
        if (!$this->emetteur->pays_code)
            $this->emetteur->pays_code = substr($langs->defaultlang, -2);    // By default, if was not defined
    }

    /**
     * 		Write the object to document file to disk
     * 		@param	    object			Object invoice to build (or id if old method)
     * 		@param		outputlangs		Lang object for output language
     * 		@return	    int     		1=OK, 0=KO
     */
    function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0) {
        global $user, $langs, $conf;

        if (!is_object($outputlangs))
            $outputlangs = $langs;
        // For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        $objectref = dol_sanitizeFileName($object->ref);
        $dir = DOL_DATA_ROOT . '/paymentreceipt/' . $objectref;
        $file = $dir . "/" . $objectref . ".pdf";
        if (!file_exists($dir)) {
            if (dol_mkdir($dir) < 0) {
                $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                return 0;
            }
        }

        if (file_exists($dir)) {
            $nblignes = sizeof($object->lines);

            $pdf = pdf_getInstance($this->format, 'mm', 'l');

            if (class_exists('TCPDF')) {
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
            }
            $pdf->SetFont(pdf_getPDFFont($outputlangs));

            $pdf->Open();
            $pagenb = 0;
            $pdf->SetDrawColor(128, 128, 128);

            $pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
            $pdf->SetSubject($outputlangs->transnoentities("Invoice"));
            $pdf->SetCreator("Dolibarr " . DOL_VERSION);
            $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
            $pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref) . " " . $outputlangs->transnoentities("Invoice"));
            if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION)
                $pdf->SetCompression(false);

            $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
            $pdf->SetAutoPageBreak(1, 0);

            // New page
            $pdf->AddPage();
            $pagenb++;
            $pdf->SetFont('', '', $default_font_size - 3);
            $pdf->MultiCell(0, 3, '');  // Set interline to 3
//Debut document
            $this->_pagehead($pdf, $object, 1, $outputlangs);
//Fin document

            $pdf->Close();

            $pdf->Output($file, 'F');
            if (!empty($conf->global->MAIN_UMASK))
                @chmod($file, octdec($conf->global->MAIN_UMASK));

            return 1;   // Pas d'erreur
        }
        else {
            $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
            return 0;
        }

        $this->error = $langs->trans("ErrorUnknown");
        return 0;   // Erreur par defaut
    }

    /**
     *   	\brief      Show header of page
     *      \param      pdf             Object PDF
     *      \param      object          Object invoice
     *      \param      showaddress     0=no, 1=yes
     *      \param      outputlangs		Object lang for output
     */
    function _pagehead(&$pdf, $object, $showaddress = 1, $outputlangs) {
        global $conf, $langs;
// Recipient name

        $sql = 'SELECT f.rowid as facid';
        $sql.= ' FROM ' . MAIN_DB_PREFIX . 'paiement_facture pf,' . MAIN_DB_PREFIX . 'facture f';
        $sql.= ' WHERE pf.fk_facture = f.rowid';
        $sql.= ' AND pf.fk_paiement = ' . $object->id;
        $sql.= ' LIMIT 1';
        $resql = $this->db->query($sql);
        if ($resql) {
            $objp = $this->db->fetch_object($resql);
            $invoice = new Facture($this->db);
            $invoice->fetch($objp->facid);
            $this->db->free($resql);
            $invoice->fetch_thirdparty();

            $carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, $invoice->thirdparty);
            // If BILLING contact defined on invoice, we use it
            $usecontact = false;
            $arrayidcontact = $object->getIdContact('external', 'BILLING');
            if (sizeof($arrayidcontact) > 0) {
                $usecontact = true;
                $result = $object->fetch_contact($arrayidcontact[0]);
            }

            $carac_client_name = $outputlangs->convToOutputCharset($invoice->thirdparty->name);
            $carac_client = pdf_build_address($outputlangs, $this->emetteur, $invoice->thirdparty, $invoice->contact, $usecontact, 'target');
        }
        else {
            dol_print_error($this->db);
        }

        $sql = "SELECT f.total_ttc";
        $sql.= " FROM " . MAIN_DB_PREFIX . "facture AS f";
        $sql.= " WHERE f.fk_soc =" . $invoice->socid;
        $sql.= " AND f.fk_statut>0";
        $sql.= " AND UNIX_TIMESTAMP(f.datef)<$object->date";
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $apayer = 0;
            for ($i = 0; $i < $num; $i++) {
                $objf = $this->db->fetch_object($resql);
                $apayer+= $objf->total_ttc;
            }
            $this->db->free($resql);
        }
        else {
            dol_print_error($this->db);
            return 0;
        }
        $sql = "SELECT sum(pf.amount) as paye";
        $sql.= " FROM " . MAIN_DB_PREFIX . "paiement_facture as pf,";
        $sql.= " " . MAIN_DB_PREFIX . "paiement as p, " . MAIN_DB_PREFIX . "facture AS f";
        $sql.= " WHERE pf.fk_paiement = p.rowid AND pf.fk_facture =f.rowid AND f.fk_soc=" . $invoice->socid;
        $sql.= " AND f.fk_statut>0";
        $sql.= " AND UNIX_TIMESTAMP(f.datef)<$object->date";
        $resqlp = $this->db->query($sql);
        if ($resqlp) {
            $objp = $this->db->fetch_object($resqlp);
            $paye = $objp->paye;
            $this->db->free($resqlp);
        }
        else {
            dol_print_error($this->db);
            return 0;
        }

        $nouveausolde = $apayer - $paye;
        $anciensolde = $nouveausolde + $object->amount;

        //get values

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

        $Xoff = 90;
        $Yoff = 0;

        $tab4_top = 60;
        $tab4_hl = 6;
        $tab4_sl = 4;
        $line = 2;

        $posy = $this->marge_haute;
        $pdf->SetXY(11, 7);

        // Logo
        $logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
        if ($this->emetteur->logo && is_readable($logo)) {
            if (is_readable($logo)) {
                $pdf->Image($logo, 10, 5, 0, 22);
            }
            else {
                $pdf->SetTextColor(200, 0, 0);
                $pdf->SetFont('', 'B', $default_font_size - 2);
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
            }
        }
        else {
            $text = $this->emetteur->nom;
            $pdf->MultiCell(100, 3, $outputlangs->convToOutputCharset($text), 0, 'L');
        }

        $pdf->SetXY($Xoff + 10, 7);
        $pdf->SetFont('', 'B', $default_font_size + 2);
        $pdf->SetTextColor(0, 0, 0);
        $titrebon = $outputlangs->transnoentities("Reçudeversement");
        $pdf->MultiCell(0, 3, $titrebon, '', 'L'); // Bordereau expedition

        $pdf->SetFont('', '', $default_font_size - 2);
        $pdf->SetTextColor(0, 0, 0);

        $Xoff = 142;

        $Yoff = $Yoff + 7;
        $pdf->SetXY($Xoff, $Yoff);
        $pdf->MultiCell(0, 3, $outputlangs->transnoentities("Ref") . " : " . $object->ref, '', 'R');

        //Date Versement
        $Yoff = $Yoff + 4;
        $pdf->SetXY($Xoff, $Yoff);
        $pdf->MultiCell(0, 3, $outputlangs->transnoentities("Date") . " : " . dol_print_date($object->date, "day", false, $outputlangs), '', 'R');

        $Yoff = $Yoff + 7;
        if ($showaddress) {
            // Sender properties
            //Definition Emplacement du bloc Societe
            $Xoff = 30;
            $blSocX = 10;
            $blSocY = 25;
            $Yoff +=6;
            $blH = 22;
            $blSocDX = $blSocX + 30;
            $blSocDY = $blSocY + 20;
            $blSocW = 50;
            $blSocX2 = $blSocW + $blSocXs;

            // Sender name
            $pdf->SetTextColor(0, 0, 60);
            $pdf->SetFont('', 'B', $default_font_size);
            $pdf->SetXY($blSocX, $blSocY);
            $pdf->MultiCell(80, 3, $outputlangs->convToOutputCharset($this->emetteur->nom), 0, 'L');
            $pdf->SetTextColor(0, 0, 0);

            // Infos expediteur
            // Sender properties
            $Ydef = $Yoff;
            $blExpX = $Xoff - 20;
            $blW = 52;
            $Yoff = $Yoff + 5;
            $Ydef = $Yoff;
            $blSocY = 1;
            $pdf->Rect($blExpX, $Yoff, $blW, $blH);
            $pdf->SetXY($blExpX, $Yoff + 1);
            $pdf->SetFont('', '', $default_font_size - 3);
            $pdf->MultiCell($blW - 2, 4, $carac_emetteur, 0, 'L');

            $blDestX = $blExpX + 55;
            $blW = 50;
            $Yoff = $Ydef + 1;
            $pdf->Rect($blDestX, $Yoff - 1, $blW, 20);

            //Titre
            $pdf->SetFont('', 'B', $default_font_size - 2);
            $pdf->SetXY($blDestX, $Yoff - 4);
            $pdf->MultiCell($blW, 3, $outputlangs->transnoentities('Client'), 0, 'L');

            // Show customer/recipient
            $pdf->SetFont('', 'B', $default_font_size - 3);
            $pdf->SetXY($blDestX, $Yoff);
            $pdf->MultiCell($blW, 3, $carac_client_name, 0, 'L');

            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->SetXY($blDestX, $Yoff + 4);
            $pdf->MultiCell($blW, 2, $carac_client, 0, 'L');
        }
        $y = $blSocDY + 20;
        $int = 7; //interligne
        $pdf->SetXY(10, $y);
        $pdf->SetFont('', 'B', $default_font_size - 2);
        $pdf->MultiCell(50, 0, $outputlangs->transnoentities('Montantverse').' : ', '', 'L');

        $pdf->SetXY(50, $y);
        $pdf->SetFont('', '', $default_font_size - 2);
        $pdf->MultiCell(0, 0, price($object->amount)." ".$outputlangs->trans($conf->currency), '', 'L');

        $y+=$int;
        $pdf->SetXY(10, $y);
        $pdf->SetFont('', 'B', $default_font_size - 2);
        $pdf->MultiCell(0, 0, $outputlangs->transnoentities('Montantverseenlettres').' : ', '', 'L');

        $pdf->SetXY(50, $y);
        $pdf->SetFont('', '', $default_font_size - 2);
        $string = ucfirst($langs->getLabelFromNumber(($object->amount), 1));
        if ($_SERVER['WINDIR'])
            $string = utf8_decode($string);
        $pdf->writeHTMLCell(0, 0, 50, $y, $string);

        $y+=$int;
        $pdf->SetXY(10, $y);
        $pdf->SetFont('', 'B', $default_font_size - 2);
        $pdf->MultiCell(0, 0, $outputlangs->trans('Modedepaiement').' : ', '', 'L');

        $pdf->SetXY(50, $y);
        $pdf->SetFont('', '', $default_font_size - 2);
        $labeltype = $langs->trans("PaymentType" . $object->type_code) != ("PaymentType" . $object->type_code) ? $langs->trans("PaymentType" . $object->type_code) : $object->type_libelle;
        if ($_SERVER['WINDIR'])
            $string = utf8_decode($string);
        $pdf->writeHTMLCell(0, 0, 50, $y, $labeltype);

        $y+=$int;
        $pdf->SetXY(10, $y);
        $pdf->SetFont('', 'B', $default_font_size - 2);
        $pdf->MultiCell(0, 0, $outputlangs->trans('Note').' : ', '', 'L');

        $pdf->SetXY(50, $y);
        $pdf->SetFont('', '', $default_font_size - 2);
        $string = $object->note;
        if ($_SERVER['WINDIR'])
            $string = utf8_decode($string);
        $pdf->writeHTMLCell(0, 0, 50, $y, $string);

        $y+=$int;
        $pdf->SetXY(10, $y);
        $pdf->SetFont('', 'B', $default_font_size - 2);
        $pdf->MultiCell(0, 0, $outputlangs->trans('Anciensolde').' : ', '', 'L');

        $pdf->SetXY(50, $y);
        $pdf->SetFont('', '', $default_font_size - 2);
        utf8_encode($object->note);
        $pdf->writeHTMLCell(0, 0, 50, $y, price($anciensolde)." ".$outputlangs->trans($conf->currency));

        $y+=$int;
        $pdf->SetXY(10, $y);
        $pdf->SetFont('', 'B', $default_font_size - 2);
        $pdf->MultiCell(0, 0, $outputlangs->trans('Noveausolde').' : ', '', 'L');

        $pdf->SetXY(50, $y);
        $pdf->SetFont('', '', $default_font_size - 2);
        utf8_encode($object->note);
        $pdf->writeHTMLCell(0, 0, 50, $y, price($nouveausolde)." ".$outputlangs->trans($conf->currency));

        $y+=$int;
        $pdf->SetFont('', 'B', $default_font_size - 2);
        $pdf->SetXY(140, $y);
        $pdf->MultiCell(0, 0, $outputlangs->trans('Cachetetsignature'), '', 'L');
    }

}

?>
