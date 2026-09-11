<?php
/* Copyright (C) 2021  MB Informatique     <info@mb-informatique.fr> 
 * Copyright (C) 2021  Philippe Grand 	   <philippe.grand@atoo-net.com>
 */

if (!empty($conf->mbisignature->enabled)) {
    dol_include_once("/mbisignature/class/signature_area.class.php");
}

class SignatureUltimateArea extends SignatureArea
{

    // PROPAL

    /**
     *	Show area for the customer to sign
     *
     *	@param	PDF			$pdf            Object PDF
     *	@param  Object		$object         Object
     *	@param	int			$posy			Position depart
     *	@param	Translate	$outputlangs	Objet langs
     *	@return int							Position pour suite
     */
    public function _signature_area(&$pdf, $object, $posy, $outputlangs, $db, $ref, $langs, $page_largeur, $marge_droite)
    {
        global $conf;
        $default_font_size = pdf_getPDFFontSize($outputlangs);
        $heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 12);	// Height reserved to output the free text on last page
        $heightforfooter = (int) $this->marge_basse + 12;	// Height reserved to output the footer (value include bottom margin)
        $heightforinfotot = 40;	// Height reserved to output the info and total part
        $widthrecbox = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 4) / 2;
        $deltay = $this->page_hauteur - $heightforfreetext - $heightforfooter - $heightforinfotot;
        $posy = max($posy + 10, $deltay);
        $deltax = $this->marge_gauche + $widthrecbox + 4;
       
        $resql = $db->query("SELECT * from " . MAIN_DB_PREFIX . "mbi_signature WHERE object_type = '" . $ref . "' AND entity = " . $conf->entity);
        $obj = $db->fetch_object($resql);
        if ($obj && $obj->pathoffile !== "document generated") {
            $img_base64_encoded = $obj->pathoffile;
            $img = '<img src="@' . preg_replace('#^data:image/[^;]+;base64,#', '', $img_base64_encoded) . '">';
            $pdf->writeHTMLCell(28,'', 122, $deltay + 14, $img, 0, 0, false, true, '', true);
            // $pdf->Image($obj->pathoffile, 124, $deltay + 14, 24); --> THIS ONLY WORKS ON DOLIBARR 11 ++
            if ($conf->global->SIGNATURE_DISPLAY_NAME == "true") {
                $pdf->SetFillColor(255, 255, 255);
                $pdf->SetXY(150, $deltay + 12);
                $pdf->SetFont('', '', $default_font_size - 2);
                $pdf->MultiCell(49, 6, $obj->name, 0, 'L', 1);
            }
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetXY(150, $conf->global->SIGNATURE_DISPLAY_NAME == "true" ? $deltay + 20 : $deltay + 18);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->MultiCell(38, 6, $langs->trans("Accord"), 0, 'L', 1);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetXY(150, $conf->global->SIGNATURE_DISPLAY_NAME == "true" ? $deltay + 16 : $deltay + 14);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->MultiCell(38, 6, date("d/m/Y, H:i:s", strtotime($obj->tms)), 0, 'L', 1);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetXY(150, $conf->global->SIGNATURE_DISPLAY_NAME == "true" ? $deltay + 24 : $deltay + 22);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->MultiCell(38, 6, 'IP: ' . $obj->ip, 0, 'L', 1);
        }

        $this->_use_certificate($conf, $pdf);

        return $posy;
    }

    // MANDAT DE PRELEVEMENT SEPA

    public function _signature_area_sepa_mandate(&$pdf, $object, $posy, $outputlangs, $db, $ref, $conf, $page_largeur, $marge_droite, $marge_gauche)
    {
        global $langs;
        $default_font_size = pdf_getPDFFontSize($outputlangs);
        $tab_top = $posy + 4;
        $tab_hl = 4;

        $resql = $db->query("SELECT * from " . MAIN_DB_PREFIX . "mbi_signature WHERE object_type = '" . $ref . "' AND entity = " . $conf->entity);
        $obj = $db->fetch_object($resql);
        if ($obj && $obj->pathoffile !== "document generated") {
            $img_base64_encoded = $obj->pathoffile;
            $img = '<img src="@' . preg_replace('#^data:image/[^;]+;base64,#', '', $img_base64_encoded) . '">';
            $pdf->writeHTMLCell(28,'', 122, $tab_top + 3, $img, 0, 0, false, true, '', true);
            // $pdf->Image($obj->pathoffile, 124, $tab_top + 14, 24); --> THIS ONLY WORKS ON DOLIBARR 11 ++
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetXY(152, $tab_top + 6);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->MultiCell(38, 8, $langs->trans("Accord"), 0, 'L', 1);
            $dateSignature = date("d/m/Y, H:i:s", strtotime($obj->tms));
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetXY(152, $tab_top + 10);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->MultiCell(38, 8, 'IP: ' . $obj->ip, 0, 'L', 1);
        }

        $posx = $marge_gauche;
        $pdf->SetXY($posx, $tab_top + 0);
        $pdf->SetFont('', '', $default_font_size - 2);
        $pdf->MultiCell(100, 3, $outputlangs->transnoentitiesnoconv("DateOfSignature"), 0, 'L', 0);
        $pdf->MultiCell(100, 3, ' ');
        $pdf->MultiCell(100, 3, $dateSignature, 0, 'L', 0);

        $posx = 120;
        $largcol = ($page_largeur - $marge_droite - $posx);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetXY($posx, $tab_top + 0);
        $pdf->MultiCell($largcol, $tab_hl, $outputlangs->transnoentitiesnoconv("Signature"), 0, 'L', 1);
        $pdf->SetXY($posx, $tab_top + $tab_hl);
        $pdf->MultiCell($largcol, $tab_hl * 3, '', 1, 'R');

        $this->_use_certificate($conf, $pdf);

        return ($tab_hl * 7);
    }

    // INTERVENTION, CONTRAT, NOTES DE FRAIS, DEMANDE DE CONGES

    /**
     *	Show area for the customer to sign
     *
     *	@param	PDF			$pdf            Object PDF
     *	@param  Object		$object         Object
     *	@param	int			$posy			Position depart
     *	@param	Translate	$outputlangs	Objet langs
     *	@return int							Position pour suite
     */
    public function _signature_area_double(&$pdf, $object, $posy, $outputlangs, $db, $ref, $langs, $page_largeur, $marge_droite, $emetteur_name, $thirdparty_name)
    {
        global $conf;
        $outputlangs->loadLangs(array("propal", "mbisignature", "holiday"));
        $default_font_size = pdf_getPDFFontSize($outputlangs);
        $heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 12);	// Height reserved to output the free text on last page
        $heightforfooter = (int) $this->marge_basse + 12;	// Height reserved to output the footer (value include bottom margin)
        $heightforinfotot = 40;	// Height reserved to output the info and total part
        $widthrecbox = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 4) / 2;
        $deltay = $this->page_hauteur - $heightforfreetext - $heightforfooter - $heightforinfotot;
        $posy = max($posy + 10, $deltay);
        $deltax = $this->marge_gauche + $widthrecbox + 4;

        $resql = $db->query("SELECT * from " . MAIN_DB_PREFIX . "mbi_signature WHERE object_type = '" . $ref . "' AND object_id = 0 AND entity = " . $conf->entity);
        $obj = $db->fetch_object($resql);
        if ($obj && $obj->pathoffile !== "document generated") {
            $img_base64_encoded = $obj->pathoffile;
            $img = '<img src="@' . preg_replace('#^data:image/[^;]+;base64,#', '', $img_base64_encoded) . '">';
            $pdf->writeHTMLCell(28,'', 122, $deltay + 20, $img, 0, 0, false, true, '', true);
            // $pdf->Image($obj->pathoffile, 124, $deltay + 20, 24);
            if ($conf->global->SIGNATURE_DISPLAY_NAME == "true") {
                $pdf->SetFillColor(255, 255, 255);
                $pdf->SetXY(150, $deltay + 18);
                $pdf->SetFont('', '', $default_font_size - 2);
                $pdf->MultiCell(49, 6, $obj->name, 0, 'L', 1);
            }
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetXY(150, $conf->global->SIGNATURE_DISPLAY_NAME == "true" ? $deltay + 22 : $deltay + 20);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->MultiCell(38, 6, date("d/m/Y, H:i:s", strtotime($obj->tms)), 0, 'L', 1);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetXY(150, $conf->global->SIGNATURE_DISPLAY_NAME == "true" ? $deltay + 26 : $deltay + 24);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->MultiCell(38, 6, $langs->trans("Accord"), 0, 'L', 1);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetXY(150, $conf->global->SIGNATURE_DISPLAY_NAME == "true" ? $deltay + 30 : $deltay + 28);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->MultiCell(38, 6, 'IP: ' . $obj->ip, 0, 'L', 1);
            //return $deltay;
        }

        // Mycompany
        $deltay = $this->page_hauteur - $heightforfreetext - $heightforfooter - $heightforinfotot;
        $cury = $pdf->getY();
        $cury = max($cury + 10, $deltay);
        $deltax = $this->marge_gauche + 20;

        $resql = $db->query("SELECT * from " . MAIN_DB_PREFIX . "mbi_signature WHERE object_type = '" . $ref . "' AND object_id = 1  AND entity = " . $conf->entity);
        $obj = $db->fetch_object($resql);
        if ($obj && $obj->pathoffile !== "document generated") {
            $img_base64_encoded = $obj->pathoffile;
            $img = '<img src="@' . preg_replace('#^data:image/[^;]+;base64,#', '', $img_base64_encoded) . '">';
            $pdf->writeHTMLCell(28, '', $deltax + 2, $deltay + 20, $img, 0, 0, false, true, '', true);
            if ($conf->global->SIGNATURE_DISPLAY_NAME == "true") {
                $pdf->SetFillColor(255, 255, 255);
                $pdf->SetXY($deltax + 30, $deltay + 18);
                $pdf->SetFont('', '', $default_font_size - 2);
                $pdf->MultiCell(49, 6, $obj->name, 0, 'L', 1);
            }
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetXY($deltax + 30, $conf->global->SIGNATURE_DISPLAY_NAME == "true" ? $deltay + 22 : $deltay + 20);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->MultiCell(38, 6, date("d/m/Y, H:i:s", strtotime($obj->tms)), 0, 'L', 1);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetXY($deltax + 30, $conf->global->SIGNATURE_DISPLAY_NAME == "true" ? $deltay + 26 : $deltay + 24);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->MultiCell(38, 6, $langs->trans("Accord"), 0, 'L', 1);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetXY($deltax + 30, $conf->global->SIGNATURE_DISPLAY_NAME == "true" ? $deltay + 30 : $deltay + 28);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->MultiCell(38, 6, 'IP: ' . $obj->ip, 0, 'L', 1);
        }

        $this->_use_certificate($conf, $pdf);

        return $cury;
    }

    // COMMANDE, COMMANDE FOURNISSEUR, EXPEDITION

    /**
     *	Show area for the customer to sign
     *
     *	@param	PDF			$pdf            Object PDF
     *	@param  Object		$object         Object
     *	@param	int			$posy			Position depart
     *	@param	Translate	$outputlangs	Objet langs
     *	@return int							Position pour suite
     */
    public function _signature_area_simple_or_double(&$pdf, $object, $posy, $outputlangs, $db, $ref, $langs, $page_largeur, $marge_droite, $emetteur_name)
    {
        global $conf;
        $default_font_size = pdf_getPDFFontSize($outputlangs);
        $heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 12);	// Height reserved to output the free text on last page
        $heightforfooter = (int) $this->marge_basse + 12;	// Height reserved to output the footer (value include bottom margin)
        $heightforinfotot = 40;	// Height reserved to output the info and total part
        $widthrecbox = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 4) / 2;
        $deltay = $this->page_hauteur - $heightforfreetext - $heightforfooter - $heightforinfotot;
        $posy = max($posy + 18, $deltay);
        $deltax = $this->marge_gauche + $widthrecbox + 4;

        if ($conf->global->DOUBLE_SIGNATURE !== "true") {

            $resql = $db->query("SELECT * from " . MAIN_DB_PREFIX . "mbi_signature WHERE object_type = '" . $ref . "' AND entity = " . $conf->entity);
            $obj = $db->fetch_object($resql);
            if ($obj && $obj->pathoffile !== "document generated") {
                $img_base64_encoded = $obj->pathoffile;
                $img = '<img src="@' . preg_replace('#^data:image/[^;]+;base64,#', '', $img_base64_encoded) . '">';
                $pdf->writeHTMLCell(28,'', 122, $posy + 14, $img, 0, 0, false, true, '', true);
                // $pdf->Image($obj->pathoffile, 124, $posy + 14, 24); --> THIS ONLY WORKS ON DOLIBARR 11 ++
                if ($conf->global->SIGNATURE_DISPLAY_NAME == "true") {
                    $pdf->SetFillColor(255, 255, 255);
                    $pdf->SetXY(152, $posy + 12);
                    $pdf->SetFont('', '', $default_font_size - 2);
                    $pdf->MultiCell(38, 6, $obj->name, 0, 'L', 1);
                }
                $pdf->SetFillColor(255, 255, 255);
                $pdf->SetXY(152, $conf->global->SIGNATURE_DISPLAY_NAME == "true" ? $posy + 20 : $posy + 18);
                $pdf->SetFont('', '', $default_font_size - 2);
                $pdf->MultiCell(38, 6, $langs->trans("Accord"), 0, 'L', 1);
                $pdf->SetFillColor(255, 255, 255);
                $pdf->SetXY(152, $conf->global->SIGNATURE_DISPLAY_NAME == "true" ? $posy + 16 : $posy + 14);
                $pdf->SetFont('', '', $default_font_size - 2);
                $pdf->MultiCell(38, 6, date("d/m/Y, H:i:s", strtotime($obj->tms)), 0, 'L', 1);
                $pdf->SetFillColor(255, 255, 255);
                $pdf->SetXY(152, $conf->global->SIGNATURE_DISPLAY_NAME == "true" ? $posy + 24 : $posy + 22);
                $pdf->SetFont('', '', $default_font_size - 2);
                $pdf->MultiCell(38, 6, 'IP: ' . $obj->ip, 0, 'L', 1);
            }
        } else {

            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetXY($deltax, $posy + 0);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->MultiCell($widthrecbox, $posy, $outputlangs->transnoentities("ProposalCustomerSignature"), 0, 'L', 1);
            $pdf->SetXY($deltax, $posy + 6);
            $pdf->MultiCell($widthrecbox, $posy, $object->thirdparty->name . " :", 0, 'L', 1);
            $pdf->SetXY($deltax, $posy + $posy + 6);
            $pdf->MultiCell($widthrecbox, $posy, '', 1, 'R');

            $resql = $db->query("SELECT * from " . MAIN_DB_PREFIX . "mbi_signature WHERE object_type = '" . $ref . "' AND object_id = 0 AND entity = " . $conf->entity);
            $obj = $db->fetch_object($resql);
            if ($obj && $obj->pathoffile !== "document generated") {
                $img_base64_encoded = $obj->pathoffile;
                $img = '<img src="@' . preg_replace('#^data:image/[^;]+;base64,#', '', $img_base64_encoded) . '">';
                $pdf->writeHTMLCell(28,'', 122, $posy + 20, $img, 0, 0, false, true, '', true);
                // $pdf->Image($obj->pathoffile, 124, $posy + 20, 24);
                if ($conf->global->SIGNATURE_DISPLAY_NAME == "true") {
                    $pdf->SetFillColor(255, 255, 255);
                    $pdf->SetXY(150, $posy + 18);
                    $pdf->SetFont('', '', $default_font_size - 2);
                    $pdf->MultiCell(49, 6, $obj->name, 0, 'L', 1);
                }
                $pdf->SetFillColor(255, 255, 255);
                $pdf->SetXY(150, $conf->global->SIGNATURE_DISPLAY_NAME == "true" ? $posy + 22 : $posy + 20);
                $pdf->SetFont('', '', $default_font_size - 2);
                $pdf->MultiCell(38, 6, date("d/m/Y, H:i:s", strtotime($obj->tms)), 0, 'L', 1);
                $pdf->SetFillColor(255, 255, 255);
                $pdf->SetXY(150, $conf->global->SIGNATURE_DISPLAY_NAME == "true" ? $posy + 26 : $posy + 24);
                $pdf->SetFont('', '', $default_font_size - 2);
                $pdf->MultiCell(38, 6, $langs->trans("Accord"), 0, 'L', 1);
                $pdf->SetFillColor(255, 255, 255);
                $pdf->SetXY(150, $conf->global->SIGNATURE_DISPLAY_NAME == "true" ? $posy + 30 : $posy + 28);
                $pdf->SetFont('', '', $default_font_size - 2);
                $pdf->MultiCell(38, 6, 'IP: ' . $obj->ip, 0, 'L', 1);
            }

            //--//

            $tab_top = $posy + 4;
            $tab_hl = 8;
            $posx = 10;
            $largcol = 80;
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetXY($deltax, $tab_top + 0);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->MultiCell($largcol, $tab_hl, $outputlangs->transnoentities("ProposalCustomerSignature"), 0, 'L', 1);
            $pdf->SetXY($deltax, $tab_top + 6);
            $pdf->MultiCell($largcol, $tab_hl, $emetteur_name . " :", 0, 'L', 1);
            $pdf->SetXY($deltax, $tab_top + $tab_hl + 6);
            $pdf->MultiCell($largcol, $tab_hl * 3, '', 1, 'R');
            if (!empty($conf->global->MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING)) {
                $pdf->addEmptySignatureAppearance($deltax, $tab_top + $tab_hl, $largcol, $tab_hl * 3);
            }

            $resql = $db->query("SELECT * from " . MAIN_DB_PREFIX . "mbi_signature WHERE object_type = '" . $ref . "' AND object_id = 1 AND entity = " . $conf->entity);
            $obj = $db->fetch_object($resql);
            if ($obj && $obj->pathoffile !== "document generated") {
                $img_base64_encoded = $obj->pathoffile;
                $img = '<img src="@' . preg_replace('#^data:image/[^;]+;base64,#', '', $img_base64_encoded) . '">';
                $pdf->writeHTMLCell(28,'', $deltax + 2, $tab_top + 20, $img, 0, 0, false, true, '', true);
                // $pdf->Image($obj->pathoffile, $deltax + 4, $tab_top + 20, 24);
                if ($conf->global->SIGNATURE_DISPLAY_NAME == "true") {
                    $pdf->SetFillColor(255, 255, 255);
                    $pdf->SetXY($deltax + 30, $tab_top + 18);
                    $pdf->SetFont('', '', $default_font_size - 2);
                    $pdf->MultiCell(49, 6, $obj->name, 0, 'L', 1);
                }
                $pdf->SetFillColor(255, 255, 255);
                $pdf->SetXY($deltax + 30, $conf->global->SIGNATURE_DISPLAY_NAME == "true" ? $tab_top + 22 : $tab_top + 20);
                $pdf->SetFont('', '', $default_font_size - 2);
                $pdf->MultiCell(38, 6, date("d/m/Y, H:i:s", strtotime($obj->tms)), 0, 'L', 1);
                $pdf->SetFillColor(255, 255, 255);
                $pdf->SetXY($deltax + 30, $conf->global->SIGNATURE_DISPLAY_NAME == "true" ? $tab_top + 26 : $tab_top + 24);
                $pdf->SetFont('', '', $default_font_size - 2);
                $pdf->MultiCell(38, 6, $langs->trans("Accord"), 0, 'L', 1);
                $pdf->SetFillColor(255, 255, 255);
                $pdf->SetXY($deltax + 30, $conf->global->SIGNATURE_DISPLAY_NAME == "true" ? $tab_top + 30 : $tab_top + 28);
                $pdf->SetFont('', '', $default_font_size - 2);
                $pdf->MultiCell(38, 6, 'IP: ' . $obj->ip, 0, 'L', 1);
            }
        }

        $this->_use_certificate($conf, $pdf);

        return ($tab_hl * 7);
    }

    protected function _use_certificate($conf, $pdf)
    {
        if ($conf->global->SIGNATURE_USE_CERTIFICATE == "true" && file_exists($conf->global->SIGNATURE_CRT) && file_exists($conf->global->SIGNATURE_CRT_PRIVATE_KEY)) {
            $certificate = "file://" . realpath($conf->global->SIGNATURE_CRT);
            $private_key = "file://" . realpath($conf->global->SIGNATURE_CRT_PRIVATE_KEY);
            $info = array('Name' => 'TCPDF', 'Location' => 'Dolibarr', 'Reason' => 'Automatic signature with MBI Signature module', 'ContactInfo' => '');
            $pdf->setSignature($certificate, $private_key, 'mbisignature', '', 2, $info);
        }
    }
}