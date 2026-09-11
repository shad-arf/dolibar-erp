<?php
/* Copyright (C) 2017 	Laurent Destailleur <eldy@products.sourceforge.net>
 * Copyright (C) 2011-2021 Philippe Grand   <philippe.grand@atoo-net.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/product/doc/pdf_ultimate_product.modules.php
 *	\ingroup    societe
 *	\brief      File of class to build PDF documents for products/services
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/product/modules_product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
dol_include_once("/ultimatepdf/lib/ultimatepdf.lib.php");


/**
 *	Class to generate PDF pdf_ultimate_product
 */
class pdf_ultimate_product extends ModelePDFProduct
{
    /**
     * @var DoliDb Database handler
     */
	public $db;
	
	/**
	 * @var int The environment ID when using a multicompany module
	 */
	public $entity;

	/**
     * @var string model name
     */
    public $name;

	/**
     * @var string model description (short text)
     */
    public $description;

	/**
	 * @var int Save the name of generated file as the main doc when generating a doc with this template
	 */
	public $update_main_doc_field;

	/**
     * @var string document type
     */
    public $type;

	/**
     * @var array() Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.6 = array(5, 6)
     */
	public $phpmin = array(5, 6);

	/**
     * Dolibarr version of the loaded document
     * @var string
     */
	public $version = 'dolibarr';

    /**
     * @var int page_largeur
     */
    public $page_largeur;

    /**
     * @var int page_hauteur
     */
    public $page_hauteur;

    /**
     * @var array format
     */
    public $format;

	/**
     * @var int marge_gauche
     */
	public $marge_gauche;

	/**
     * @var int marge_droite
     */
	public $marge_droite;

	/**
     * @var int marge_haute
     */
	public $marge_haute;

	/**
     * @var int marge_basse
     */
	public $marge_basse;

    /**
	 * Issuer
	 * @var Societe
	 */
	public $emetteur;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		// Load traductions files required by page
		$langs->loadLangs(array("main", "companies", "languages"));

		$this->db = $db;
		$this->name = "ultimate_product";
		$this->description = $langs->trans("DocumentModelStandardPDF");
		$this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template

		// Page size for A4 format
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = isset($conf->global->MAIN_PDF_MARGIN_LEFT) ? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
		$this->marge_droite = isset($conf->global->MAIN_PDF_MARGIN_RIGHT) ? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
		$this->marge_haute = isset($conf->global->MAIN_PDF_MARGIN_TOP) ? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
		$this->marge_basse = isset($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;

		$this->option_logo = 1;                    // Affiche logo
		$this->option_codeproduitservice = 0;      // Affiche code produit-service
		$this->option_multilang = 1;               // Dispo en plusieurs langues
		$this->option_freetext = 0;				   // Support add of a personalised text

		// Recupere emetteur
		$this->emetteur = $mysoc;
		if (!$this->emetteur->country_code) $this->emetteur->country_code = substr($langs->defaultlang, -2);    // By default if not defined
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Function to build pdf onto disk.
	 *
	 *	@param		Product		$object				Object source to build document
	 *	@param		Translate	$outputlangs		Lang output object
	 * 	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *	@return		int         					1 if OK, <=0 if KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $langs, $conf, $mysoc, $nblines, $db, $hookmanager;

		if (!is_object($outputlangs)) $outputlangs = $langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (!empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output = 'ISO-8859-1';

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "dict", "companies", "bills", "products", "orders", "deliveries"));

		if (!empty($conf->global->PDF_USE_ALSO_LANGUAGE_CODE) && $outputlangs->defaultlang != $conf->global->PDF_USE_ALSO_LANGUAGE_CODE) {
			global $outputlangsbis;
			$outputlangsbis = new Translate('', $conf);
			$outputlangsbis->setDefaultLang($conf->global->PDF_USE_ALSO_LANGUAGE_CODE);
			$outputlangsbis->loadLangs(array("main", "dict", "companies", "bills", "products", "propal"));
		}

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		$lang_id = GETPOST('lang_id');
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && !empty($lang_id)) $newlang = $lang_id;
		if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang = $object->default_lang;
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}

		if ($conf->product->dir_output) {
			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->product->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			} else {
				//Ending the file with the default language
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->product->dir_output . "/" . $objectref;
				$file = $dir . "/" . $objectref . '_' . $lang_id . ".pdf";
			}

			$productFournisseur = new ProductFournisseur($this->db);
			$supplierprices = $productFournisseur->list_product_fournisseur_price($object->id);
			$object->supplierprices = $supplierprices;

			if (!file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return -1;
				}
			}

			if (file_exists($dir)) {
				// Add pdfgeneration hook
				if (!is_object($hookmanager)) {
					include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
					$hookmanager = new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks

				// Create pdf instance
				$pdf = pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
				$pdf->SetAutoPageBreak(1, 0);

				$heightforfooter = $this->marge_basse + 8;	// Height reserved to output the footer (value include bottom margin)
				if ($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS > 0) $heightforfooter += 6;

				//catch logo height
				if ($conf->global->ULTIMATE_OTHERLOGO_HEIGHT && $conf->global->PDF_DISABLE_ULTIMATE_OTHERLOGO_FILE == 0) {
					$logo_height = $conf->global->ULTIMATE_OTHERLOGO_HEIGHT;
				} else {
					// MyCompany logo
					$logo_height = max(pdf_getUltimateHeightForLogo($conf->global->ULTIMATE_LOGO_HEIGHT, true), 20);
				}

				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));
				// Set path to the background PDF File
				if (!empty($conf->global->MAIN_ADD_PDF_BACKGROUND)) {
					$pagecount = $pdf->setSourceFile($conf->mycompany->dir_output . '/' . $conf->global->MAIN_ADD_PDF_BACKGROUND);
					$tplidx = $pdf->importPage(1);
				}

				$pdf->Open();
				$pagenb = 0;
				$pdf->SetDrawColor(128, 128, 128);

				if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Product"));
				$pdf->SetCreator("Dolibarr " . DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref) . " " . $outputlangs->transnoentities("Product") . " " . $outputlangs->convToOutputCharset($object->thirdparty->name));
				if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

				// New page
				$pdf->AddPage();
				if (!empty($tplidx)) $pdf->useTemplate($tplidx);
				$pagenb++;

				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColorArray($textcolor);

				$tab_width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
				$tab_top = $this->marge_haute + $logo_height + $tab_width / 3 + 20;
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD) ? $this->marge_haute + $logo_height + 20 : 10);
				$tab_height = $this->page_hauteur - $tab_top_newpage - $heightforfooter;
				$tab_height_newpage = 150;

				// Label
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->writeHTMLCell($tab_width, 3, $this->marge_gauche, $tab_top, dol_htmlentitiesbr($object->multilangs[$newlang]['label']), 0, 1);
				$nexY = $pdf->GetY();

				// Description
				$pdf->SetFont('', '', $default_font_size);
				$pdf->writeHTMLCell($tab_width, 3, $this->marge_gauche, $nexY, dol_htmlentitiesbr($object->multilangs[$newlang]['description']), 0, 1);
				$nexY = $pdf->GetY();

				$nexY += 5;

				if (!empty($conf->global->ULTIMATEPDF_GENERATE_PRODUCTS_WITH_VOL_WEIGHT)) {
					if ($object->weight) {
						$texttoshow = $langs->trans("Weight") . ': ' . dol_htmlentitiesbr($object->weight);
						if (isset($object->weight_units)) $texttoshow .= ' ' . measuring_units_string($object->weight_units, 'weight', 0, 0, $outputlangs);
						$pdf->writeHTMLCell(190, 3, $this->marge_gauche, $nexY, $texttoshow, 0, 1);
						$nexY = $pdf->GetY();
					}
					if ($object->length) {
						$texttoshow = $langs->trans("Length") . ' x ' . $langs->trans("Width") . ' x ' . $langs->trans("Height") . ': ' . ($object->length != '' ? $object->length : '?') . ' x ' . ($object->width != '' ? $object->width : '?') . ' x ' . ($object->height != '' ? $object->height : '?');
						$texttoshow .= ' ' . measuring_units_string($object->length_units, 'length', 0, 0, $outputlangs);
						$pdf->writeHTMLCell(190, 3, $this->marge_gauche, $nexY, $texttoshow, 0, 1);
						$nexY = $pdf->GetY();
					}
					if ($object->surface) {
						$pdf->writeHTMLCell(190, 3, $this->marge_gauche, $nexY, $langs->trans("Area") . ': ' . dol_htmlentitiesbr($object->surface), 0, 1);
						$nexY = $pdf->GetY();
					}
					if ($object->volume) {
						$pdf->writeHTMLCell(190, 3, $this->marge_gauche, $nexY, $langs->trans("Volume") . ': ' . dol_htmlentitiesbr($object->volume), 0, 1);
						$nexY = $pdf->GetY();
					}
				}


				// Affiche notes
				// TODO There is no public note on product yet
			/*	$notetoshow = empty($object->note_public) ? '' : $object->note_public;
				if (!empty($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_NOTE)) {
					// Get first sale rep
					if (is_object($object->thirdparty)) {
						$salereparray = $object->thirdparty->getSalesRepresentatives($user);
						$salerepobj = new User($this->db);
						$salerepobj->fetch($salereparray[0]['id']);
						if (!empty($salerepobj->signature)) $notetoshow = dol_concatdesc($notetoshow, $salerepobj->signature);
					}
				}
				if ($notetoshow) {
					$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$notetoshow = make_substitutions($notetoshow, $substitutionarray, $outputlangs);
					$notetoshow = convertBackOfficeMediasLinksToPublicLinks($notetoshow);

					$tab_top = 88;

					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
					$nexY = $pdf->GetY();
					$height_note = $nexY - $tab_top;

					// Rect takes a length in 3rd parameter
					$pdf->SetDrawColor(192, 192, 192);
					$pdf->Rect($this->marge_gauche, $tab_top - 1, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_note + 1);

					$tab_height = $tab_height - $height_note;
					$tab_top = $nexY + 6;
				} else {
					$height_note = 0;
				}

				$nexY = $tab_top + 7;*/

				// Loop on each lines
				/*
				for ($i = 0 ; $i < $nblines ; $i++)
				{
					$curY = $nexY;
					$pdf->SetFont('','', $default_font_size - 1);   // Into loop to work with multipage
					$pdf->SetTextColorArray($textcolor);

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter+$heightforfreetext+$heightforinfotot);	// The only function to edit the bottom margin of current page to set it.
					$pageposbefore=$pdf->getPage();

					// Description of product line
					$curX = $this->posxdesc-1;

					$showpricebeforepagebreak=1;

					$pdf->startTransaction();
					pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->posxtva-$curX,3,$curX,$curY,$hideref,$hidedesc);
					$pageposafter=$pdf->getPage();
					if ($pageposafter > $pageposbefore)	// There is a pagebreak
					{
						$pdf->rollbackTransaction(true);
						$pageposafter=$pageposbefore;
						//print $pageposafter.'-'.$pageposbefore;exit;
						$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
						pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->posxtva-$curX,4,$curX,$curY,$hideref,$hidedesc);
						$pageposafter=$pdf->getPage();
						$posyafter=$pdf->GetY();
						if ($posyafter > ($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot)))	// There is no space left for total+free text
						{
							if ($i == ($nblines-1))	// No more lines, and no space left to show total, so we create a new page
							{
								$pdf->AddPage('','',true);
								if (! empty($tplidx)) $pdf->useTemplate($tplidx);
								if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
								$pdf->setPage($pageposafter+1);
							}
						}
						else
						{
							// We found a page break
							$showpricebeforepagebreak=0;
						}
					}
					else	// No pagebreak
					{
						$pdf->commitTransaction();
					}

					$nexY = $pdf->GetY();
					$pageposafter=$pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description is moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter); $curY = $tab_top_newpage;
					}

					$pdf->SetFont('','',  $default_font_size - 1);   // On repositionne la police par defaut

					// VAT Rate
					if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) && empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN))
					{
						$vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails);
						$pdf->SetXY($this->posxtva, $curY);
						$pdf->MultiCell($this->posxup-$this->posxtva-0.8, 3, $vat_rate, 0, 'R');
					}

					// Unit price before discount
					$up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails);
					$pdf->SetXY($this->posxup, $curY);
					$pdf->MultiCell($this->posxqty-$this->posxup-0.8, 3, $up_excl_tax, 0, 'R', 0);

					// Quantity
					$qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
					$pdf->SetXY($this->posxqty, $curY);
					$pdf->MultiCell($this->posxunit-$this->posxqty-0.8, 4, $qty, 0, 'R');  // Enough for 6 chars

					// Unit
					if($conf->global->PRODUCT_USE_UNITS)
					{
						$unit = pdf_getlineunit($object, $i, $outputlangs, $hidedetails, $hookmanager);
						$pdf->SetXY($this->posxunit, $curY);
						$pdf->MultiCell($this->posxdiscount-$this->posxunit-0.8, 4, $unit, 0, 'L');
					}

					// Discount on line
					$pdf->SetXY($this->posxdiscount, $curY);
					if ($object->lines[$i]->remise_percent)
					{
						$pdf->SetXY($this->posxdiscount-2, $curY);
						$remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails);
						$pdf->MultiCell($this->postotalht-$this->posxdiscount+2, 3, $remise_percent, 0, 'R');
					}

					// Total HT line
					$total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);
					$pdf->SetXY($this->postotalht, $curY);
					$pdf->MultiCell($this->page_largeur-$this->marge_droite-$this->postotalht, 3, $total_excl_tax, 0, 'R', 0);

					// Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
					if ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) $tvaligne=$object->lines[$i]->multicurrency_total_tva;
					else $tvaligne=$object->lines[$i]->total_tva;

					$localtax1ligne=$object->lines[$i]->total_localtax1;
					$localtax2ligne=$object->lines[$i]->total_localtax2;
					$localtax1_rate=$object->lines[$i]->localtax1_tx;
					$localtax2_rate=$object->lines[$i]->localtax2_tx;
					$localtax1_type=$object->lines[$i]->localtax1_type;
					$localtax2_type=$object->lines[$i]->localtax2_type;

					if ($object->remise_percent) $tvaligne-=($tvaligne*$object->remise_percent)/100;
					if ($object->remise_percent) $localtax1ligne-=($localtax1ligne*$object->remise_percent)/100;
					if ($object->remise_percent) $localtax2ligne-=($localtax2ligne*$object->remise_percent)/100;

					$vatrate=(string) $object->lines[$i]->tva_tx;

					// Retrieve type from database for backward compatibility with old records
					if ((! isset($localtax1_type) || $localtax1_type=='' || ! isset($localtax2_type) || $localtax2_type=='') // if tax type not defined
					&& (! empty($localtax1_rate) || ! empty($localtax2_rate))) // and there is local tax
					{
						$localtaxtmp_array=getLocalTaxesFromRate($vatrate,0,$object->thirdparty,$mysoc);
						$localtax1_type = $localtaxtmp_array[0];
						$localtax2_type = $localtaxtmp_array[2];
					}

				    // retrieve global local tax
					if ($localtax1_type && $localtax1ligne != 0)
						$this->localtax1[$localtax1_type][$localtax1_rate]+=$localtax1ligne;
					if ($localtax2_type && $localtax2ligne != 0)
						$this->localtax2[$localtax2_type][$localtax2_rate]+=$localtax2ligne;

					if (($object->lines[$i]->info_bits & 0x01) == 0x01) $vatrate.='*';
					if (! isset($this->tva[$vatrate])) 				$this->tva[$vatrate]=0;
					$this->tva[$vatrate] += $tvaligne;

					// Add line
					if (! empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES) && $i < ($nblines - 1))
					{
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash'=>'1,1','color'=>array(80,80,80)));
						//$pdf->SetDrawColor(190,190,200);
						$pdf->line($this->marge_gauche, $nexY+1, $this->page_largeur - $this->marge_droite, $nexY+1);
						$pdf->SetLineStyle(array('dash'=>0));
					}

					$nexY+=2;    // Add space between lines

					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter)
					{
						$pdf->setPage($pagenb);
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code);
						}
						$this->_pagefoot($pdf,$object,$outputlangs,1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
					}
					if (isset($object->lines[$i+1]->pagebreak) && $object->lines[$i+1]->pagebreak)
					{
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code);
						}
						$this->_pagefoot($pdf,$object,$outputlangs,1);
						// New page
						$pdf->AddPage();
						if (! empty($tplidx)) $pdf->useTemplate($tplidx);
						$pagenb++;
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
					}
				}

				// Show square
				if ($pagenb == 1)
				{
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0, $object->multicurrency_code);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}
				else
				{
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0, $object->multicurrency_code);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}
                */

				// Affiche zone infos
				//$posy=$this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);

				// Pied de page
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

				// Add PDF to be merged
				if (!empty($conf->global->ULTIMATEPDF_GENERATE_PRODUCTS_WITH_MERGED_PDF)) {
					dol_include_once('/ultimatepdf/class/documentmergedpdf.class.php');

					$already_merged = array();
					
					if (!empty($object->id) && !(in_array($object->id, $already_merged))) {
						// Find the desire PDF
						$filetomerge = new DocumentMergedPdf($this->db);
						$filetomerge->fetch_by_element($object);
						$already_merged[] = $object->id;
						//var_dump($filetomerge->lines);exit;
						// If PDF is selected and file is not empty
						if (count($filetomerge->lines) > 0) {
							foreach ($filetomerge->lines as $linefile) {
								if (!empty($linefile->id) && !empty($linefile->file_name)) {
									if (!empty($conf->product->enabled))
										$filetomerge_dir = $conf->product->dir_output . '/' . dol_sanitizeFileName($object->ref);
										
									$infile = $filetomerge_dir . '/' . $linefile->file_name;
									dol_syslog(get_class($this) . ':: $upload_dir=' . $filetomerge_dir, LOG_DEBUG);
									// If file really exists
									if (is_file($infile)) {
										$count = $pdf->setSourceFile($infile);
										// import all page
										for ($i = 1; $i <= $count; $i++) {
											// New page
											$pdf->AddPage();
											$tplIdx = $pdf->importPage($i);
											$pdf->useTemplate($tplIdx, 0, 0, $this->page_largeur);
											if (method_exists($pdf, 'AliasNbPages'))
												$pdf->AliasNbPages();
										}
									}
								}
							}
						}
					}
				}

				$pdf->Close();

				$pdf->Output($file, 'F');

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					$this->error = $hookmanager->error;
					$this->errors = $hookmanager->errors;
				}

				if (!empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

				$this->result = array('fullpath' => $file);

				return 1;   // No error
			} else {
				$this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		} else {
			$this->error = $langs->trans("ErrorConstantNotDefined", "PRODUCT_OUTPUTDIR");
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   Show table for lines
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @param		string		$currency		Currency code
	 *   @return	void
	 */
	protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0)
	{
		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom = 0;
		if ($hidetop) $hidetop = - 1;

		$default_font_size = pdf_getPDFFontSize($outputlangs);
		if (!empty($conf->global->ULTIMATE_DASH_DOTTED)) {
			$dashdotted = $conf->global->ULTIMATE_DASH_DOTTED;
		}
		if (!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR)) {
			$bordercolor = html2rgb($conf->global->ULTIMATE_BORDERCOLOR_COLOR);
		}
		$bgcolor = array('170', '212', '255');
		if (!empty($conf->global->ULTIMATE_BGCOLOR_COLOR)) {
			$bgcolor = html2rgb($conf->global->ULTIMATE_BGCOLOR_COLOR);
		}
		$opacity = 0.5;
		if (!empty($conf->global->ULTIMATE_BGCOLOR_OPACITY)) {
			$opacity = $conf->global->ULTIMATE_BGCOLOR_OPACITY;
		}
		if (!empty($conf->global->ULTIMATE_SET_RADIUS)) {
			$roundradius = $conf->global->ULTIMATE_SET_RADIUS;
		}
		$textcolor = array('25', '25', '25');
		if (!empty($conf->global->ULTIMATE_TEXTCOLOR_COLOR)) {
			$textcolor = html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR);
		}
		if (!empty($conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR)) {
			$title_bgcolor =  html2rgb($conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR);
		}
		$this->border_style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted, 'color' => $bordercolor);

		// Amount in (at tab_top - 1)
        $pdf->SetFillColorArray($bgcolor);
		$pdf->SetTextColorArray($textcolor);
        $pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size - 2);

		if ($roundradius == 0)
		{
			$roundradius = 0.1; //RoundedRect don't support $roundradius to be 0
		}
		// Output RoundedRect
		$pdf->SetAlpha($opacity);
		if (!empty($conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR)) {
			$pdf->RoundedRect($this->marge_gauche, $tab_top - 8, $this->page_largeur - $this->marge_gauche - $this->marge_droite, 8, $roundradius, $round_corner = '1001', 'FD', $this->border_style, $title_bgcolor);
		} else {
			$pdf->RoundedRect($this->marge_gauche, $tab_top - 8, $this->page_largeur - $this->marge_gauche - $this->marge_droite, 8, $roundradius, $round_corner = '1001', 'FD', $this->border_style, $bgcolor);
		}
		$pdf->SetAlpha(1);
		//title line
		$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, $roundradius, $round_corner = '0110', 'S', $this->border_style, $bgcolor);

		$this->pdfTabTitles($pdf, $tab_top - 8, $tab_height + 8, $outputlangs, $hidetop);
		
    }

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param	string		$titlekey		Translation key to show as title of document
	 *  @return	void
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $titlekey = "")
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->global->ULTIMATE_DASH_DOTTED)) {
			$dashdotted = $conf->global->ULTIMATE_DASH_DOTTED;
		}
		$bgcolor = array('170', '212', '255');
		if (!empty($conf->global->ULTIMATE_BGCOLOR_COLOR)) {
			$bgcolor = html2rgb($conf->global->ULTIMATE_BGCOLOR_COLOR);
		}
		if (!empty($conf->global->ULTIMATE_SENDER_STYLE)) {
			$senderstyle = $conf->global->ULTIMATE_SENDER_STYLE;
		}
		if (!empty($conf->global->ULTIMATE_RECEIPT_STYLE)) {
			$receiptstyle = $conf->global->ULTIMATE_RECEIPT_STYLE;
		}
		if (!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR)) {
			$bordercolor = html2rgb($conf->global->ULTIMATE_BORDERCOLOR_COLOR);
		}
		$opacity = 0.5;
		if (!empty($conf->global->ULTIMATE_BGCOLOR_OPACITY)) {
			$opacity =  $conf->global->ULTIMATE_BGCOLOR_OPACITY;
		}
		if (!empty($conf->global->ULTIMATE_SET_RADIUS)) {
			$roundradius = $conf->global->ULTIMATE_SET_RADIUS;
		}
		$textcolor = array('25', '25', '25');
		if (!empty($conf->global->ULTIMATE_TEXTCOLOR_COLOR)) {
			$textcolor = html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR);
		}
		$qrcodecolor = array('25', '25', '25');
		if (!empty($conf->global->ULTIMATE_TEXTCOLOR_COLOR)) {
			$qrcodecolor = html2rgb($conf->global->ULTIMATE_QRCODECOLOR_COLOR);
		}

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "products", "companies", "bills", "orders"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		if ($object->type == 1) $titlekey = 'ServiceSheet';
		else $titlekey = 'ProductSheet';

		pdf_new_pagehead($pdf, $outputlangs, $this->page_hauteur);

		// Show Draft Watermark
		if ($object->statut == 0 && (!empty($conf->global->COMMANDE_DRAFT_WATERMARK))) {
			pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->COMMANDE_DRAFT_WATERMARK);
		}

		$pdf->SetTextColorArray($textcolor);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$posy = $this->marge_haute;
		$tab_width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Other Logo
		$id = $conf->global->ULTIMATE_DESIGN;
		$upload_dir	= $conf->ultimatepdf->dir_output . '/otherlogo/' . $id . '/';
		$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', 'name', 0, 1);
		$otherlogo = $conf->ultimatepdf->dir_output . '/otherlogo/' . $id . '/' . $filearray[0]['name'];
		if (!empty($conf->global->ULTIMATE_DESIGN) && !empty($filearray[0]['relativename']) && is_readable($otherlogo) && $conf->global->PDF_DISABLE_ULTIMATE_OTHERLOGO_FILE == 0) {
			$logo_height = max(pdf_getUltimateHeightForOtherLogo($otherlogo, true), 20);
			$pdf->Image($otherlogo, $this->marge_gauche, $posy, 0, $logo_height);	// width=0 (auto)
		// Logo from company
		} elseif (empty($conf->global->PDF_DISABLE_MYCOMPANY_LOGO)) {
			if ($this->emetteur->logo) {
				$logodir = $conf->mycompany->dir_output;
				if (!empty($conf->mycompany->multidir_output[$object->entity])) $logodir = $conf->mycompany->multidir_output[$object->entity];
				if (empty($conf->global->MAIN_PDF_USE_LARGE_LOGO)) {
					$logo = $logodir . '/logos/thumbs/' . $this->emetteur->logo_small;
				} else {
					$logo = $logodir . '/logos/' . $this->emetteur->logo;
				}
				if (is_readable($logo)) {
					$logo_height = max(pdf_getUltimateHeightForLogo($logo, true), 20);
					$pdf->Image($logo, $this->marge_gauche, $posy, 0, $logo_height);	// width=0 (auto)
				} else {
					$pdf->SetTextColor(200, 0, 0);
					$pdf->SetFont('', 'B', $default_font_size - 2);
					$pdf->RoundedRect($this->marge_gauche, $this->marge_haute, 100, 20, $roundradius, $round_corner = '1111', $senderstyle, $this->border_style, $bgcolor);
					$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
					$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
				}
			}
		} else {
			$pdf->RoundedRect($this->marge_gauche, $this->marge_haute, 90, 20, $roundradius, $round_corner = '1111', $senderstyle, $this->border_style, $bgcolor);
			$pdf->SetFont('', 'B', $default_font_size + 3);
			$text =  !empty($conf->global->ULTIMATE_PDF_ALIAS_COMPANY) ? $conf->global->ULTIMATE_PDF_ALIAS_COMPANY : $this->emetteur->name;
			$pdf->MultiCell(90, 8, $outputlangs->convToOutputCharset($text), 0, 'C');
			$logo_height = 20;
		}	
		$rightSideWidth = $tab_width / 2;
		$posx = $this->marge_gauche + $tab_width / 2 ;

		// Example using extrafields for new title of document
		$tmpuser = new User($this->db);
		$tmpuser->fetch($object->user_author_id);
		$title_key = (empty($tmpuser->array_options['options_ultimatetheme'])) ? '' : ($tmpuser->array_options['options_ultimatetheme']);
		//var_dump($title_key);exit;

		$title_key = (empty($object->array_options['options_newtitle'])) ? '' : ($object->array_options['options_newtitle']);
		$extrafields = new ExtraFields($this->db);
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element, true);
		if (is_array($extralabels) && key_exists('newtitle', $extralabels) && !empty($title_key)) {
			$titlekey = $extrafields->showOutputField('newtitle', $title_key);
		}

		//Document name
		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColorArray($textcolor);
		$standardtitle = $outputlangs->transnoentities("PdfCommercialProposalTitle");
		$title = (empty($outputlangs->transnoentities($titlekey)) ? $standardtitle : $outputlangs->transnoentities($titlekey));
		$pdf->MultiCell($rightSideWidth, 3, $title, '', 'R');

		$pdf->SetFont('', 'B', $default_font_size + 2);

		$posy = $pdf->getY();
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColorArray($textcolor);
		$pdf->MultiCell($rightSideWidth, 4, $outputlangs->transnoentities("Ref") . " : " . $outputlangs->convToOutputCharset($object->ref), '', 'R');

		$pdf->SetFont('', '', $default_font_size - 1);

		// Show list of linked objects
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'R', $default_font_size);

		$posy = $this->marge_haute + $logo_height + 10;

		// Loop on each lines to detect if there is at least one image to show
		$id     = GETPOST('id', 'int');
		$ref    = GETPOST('ref', 'alpha');
		if (!empty($conf->global->ULTIMATE_GENERATE_PRODUCTS_WITH_PICTURE)) {
			$object = new Product($this->db);
			if ($id > 0 || !empty($ref)) {
				$object->fetch($id, $ref);
				if (!empty($conf->product->enabled)) $upload_dir = $conf->product->multidir_output[$object->entity] . '/' . get_exdir(0, 0, 0, 1, $object, 'product');
				elseif (!empty($conf->service->enabled)) $upload_dir = $conf->service->multidir_output[$object->entity] . '/' . get_exdir(0, 0, 0, 1, $object, 'product');
				$filearray = dol_dir_list($upload_dir, "files", 0, '\.jpeg|\.jpg|\.png');				
				$productimage = $upload_dir . '/' . $filearray[0]['name'];
				if (!empty($filearray[0]['name']) && is_readable($productimage)) {
					$pdf->Image($productimage, $this->marge_gauche + $tab_width / 3, $posy, 0, $tab_width / 3);
				}			
			}
		}

		if ($showaddress) {
			/*
	        // Sender properties
	        $carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty);

	        // Show sender
	        $posy=42;
	        $posx=$this->marge_gauche;
	        if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->page_largeur-$this->marge_droite-80;
	        $hautcadre=40;

	        // Show sender frame
	        $pdf->SetTextColorArray($textcolor);
	        $pdf->SetFont('','', $default_font_size - 2);
	        $pdf->SetXY($posx,$posy-5);
	        $pdf->MultiCell(66,5, $outputlangs->transnoentities("BillFrom").":", 0, 'L');
	        $pdf->SetXY($posx,$posy);
	        $pdf->SetFillColor(230,230,230);
	        $pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);
	        $pdf->SetTextColorArray($textcolor);

	        // Show sender name
	        $pdf->SetXY($posx+2,$posy+3);
	        $pdf->SetFont('','B', $default_font_size);
	        $pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
	        $posy=$pdf->getY();

	        // Show sender information
	        $pdf->SetXY($posx+2,$posy);
	        $pdf->SetFont('','', $default_font_size - 1);
	        $pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');
	        */
		}

		$pdf->SetTextColorArray($textcolor);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
    /**
     *  Show footer of page. Need this->emetteur object
     *
     *  @param	TCPDF		$pdf     			PDF
     *  @param	Object		$object				Object to show
     *  @param	Translate	$outputlangs		Object lang for output
     *  @param	int			$hidefreetext		1=Hide free text
     *  @return	int								Return height of bottom margin including footer text
     */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		$showdetails = $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_ultimatepagefoot($pdf, $outputlangs, 'PRODUCT_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext, $this->footertextcolor);
	}


/**
	 *   	Define Array Column Field
	 *
	 *   	@param	object			$object    		common object
	 *   	@param	Translate		$outputlangs    langs
	 *      @param	int				$hidedetails	Do not show line details
	 *      @param	int				$hidedesc		Do not show desc
	 *      @param	int				$hideref		Do not show ref
	 *      @return	null
	 */
	public function defineColumnField($object, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $conf, $hookmanager;

		// Default field style for content
		$this->defaultContentsFieldsStyle = array(
			'align' => 'R', // R,C,L
			'padding' => array(0, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
		);

		// Default field style for content
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->defaultTitlesFieldsStyle = array(
				'align' => 'C', // R,C,L
				'padding' => array(0.5, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			);
		} else { 
				$this->defaultTitlesFieldsStyle = array(
					'align' => 'R', // R,C,L
					'padding' => array(0.5, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
				);
			}

		/*
	     * For exemple
	     $this->cols['theColKey'] = array(
	     'rank' => $rank, // int : use for ordering columns
	     'width' => 20, // the column width in mm
	     'title' => array(
	     'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
	     'label' => ' ', // the final label : used fore final generated text
	     'align' => 'L', // text alignement :  R,C,L
	     'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
	     ),
	     'content' => array(
	     'align' => 'L', // text alignement :  R,C,L
	     'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
	     ),
	     );
	     */

		$rank = 0; // do not use negative rank
		$this->cols['num'] = array(
			'rank' => $rank,
			'width' => (empty($conf->global->ULTIMATE_DOCUMENTS_WITH_NUMBERING_WIDTH) ? 10 : $conf->global->ULTIMATE_DOCUMENTS_WITH_NUMBERING_WIDTH), // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Numbering', // use lang key is usefull in somme case with module
				'align' => 'C',
				'padding' => array(0.5, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'content' => array(
				'align' => 'C',
			),
		);
		if (!empty($conf->global->ULTIMATE_PROPOSALS_WITH_LINE_NUMBER)) {
			$this->cols['num']['status'] = true;
		}

		$rank = $rank + 10; // do not use negative rank
		$this->cols['ref'] = array(
			'rank' => $rank,
			'width' => (empty($conf->global->ULTIMATE_DOCUMENTS_WITH_REF_WIDTH) ? 16 : $conf->global->ULTIMATE_DOCUMENTS_WITH_REF_WIDTH), // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'RefShort', // use lang key is usefull in somme case with module
				'align' => 'C',
				'padding' => array(0.5, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'content' => array(
				'align' => 'L',
			),
			'border-left' => false, // remove left line separator
		);

		if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes" && $this->atleastoneref == true) {
			$this->cols['ref']['status'] = true;
		}
		if (!empty($conf->global->ULTIMATE_PROPOSALS_WITH_LINE_NUMBER) && $conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['ref']['border-left'] = true;
		}

		$rank = $rank + 10; // do not use negative rank
		$this->cols['desc'] = array(
			'rank' => $rank,
			'width' => false, // only for desc
			'status' => true,
			'title' => array(
				'textkey' => 'Designation', // use lang key is usefull in somme case with module
				'align' => 'L',
				'padding' => array(0.5, 0.5, 0.5, 1.0), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'content' => array(
				'align' => 'L',
			),
			'border-left' => false, // remove left line separator
		);

		if (!empty($conf->global->ULTIMATE_PROPOSALS_WITH_LINE_NUMBER && $conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') || ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes" && $this->atleastoneref == true && $conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes')) {
			$this->cols['desc']['border-left'] = true;
		}

		$rank = $rank + 10;
		$this->cols['picture'] = array(
			'rank' => $rank,
			'width' => (empty($conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH) ? 20 : $conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH), // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Picture',
				'label' => ' '
			),
			'content' => array(
				'padding' => array(0.5, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'border-left' => false, // remove left line separator
		);

		if (!empty($conf->global->ULTIMATE_GENERATE_PROPOSALS_WITH_PICTURE) && !empty($this->atleastonephoto)) {
			$this->cols['picture']['status'] = true;
		}

		$rank = $rank + 10; //extrafields column
		$this->cols['xtrafields'] = array(
			'rank' => $rank,
			'status' => false, // set status to true to display
			'width' => 15, // in mm
			'title' => array(
				'textkey' => 'Xtrafields'
			),
			'content' => array(
				'align' => 'R'
			),
			'border-left' => true, // add left line separator
		);

		$rank = $rank + 10;
		$this->cols['vat'] = array(
			'rank' => $rank,
			'status' => false,
			'width' => (empty($conf->global->ULTIMATE_DOCUMENTS_WITH_TVA_WIDTH) ? 12 : $conf->global->ULTIMATE_DOCUMENTS_WITH_TVA_WIDTH), // in mm
			'title' => array(
				'textkey' => 'VAT'
			),
			'content' => array(
				'align' => 'R'
			),
			'border-left' => false, // add left line separator
		);

		if (($conf->global->ULTIMATE_GENERATE_DOCUMENTS_WITHOUT_VAT == "no") && empty($conf->global->ULTIMATE_SHOW_HIDE_VAT_COLUMN)) {
			$this->cols['vat']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['vat']['border-left'] = true;
		}

		$rank = $rank + 10;
		$this->cols['subprice'] = array(
			'rank' => $rank,
			'width' => (empty($conf->global->ULTIMATE_DOCUMENTS_WITH_UP_WIDTH) ? 18 : $conf->global->ULTIMATE_DOCUMENTS_WITH_UP_WIDTH), // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'PriceUHT'
			),
			'content' => array(
				'align' => 'R'
			),
			'border-left' => false, // add left line separator
		);

		if (!empty($conf->global->ULTIMATE_GENERATE_PROPOSALS_WITH_PRICEUHT)) {
			$this->cols['subprice']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['subprice']['border-left'] = true;
		}

		$rank = $rank + 10;
		$this->cols['discount'] = array(
			'rank' => $rank,
			'width' => (empty($conf->global->ULTIMATE_DOCUMENTS_WITH_DISCOUNT_WIDTH) ? 12 : $conf->global->ULTIMATE_DOCUMENTS_WITH_DISCOUNT_WIDTH), // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'ReductionShort'
			),
			'content' => array(
				'align' => 'R'
			),
			'border-left' => false, // add left line separator
		);

		if ($this->atleastonediscount && !empty($conf->global->ULTIMATE_GENERATE_PROPOSALS_WITH_DISCOUNT)) {
			$this->cols['discount']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['discount']['border-left'] = true;
		}

		$rank = $rank + 10;
		$this->cols['upafter'] = array(
			'rank' => $rank,
			'width' => (empty($conf->global->ULTIMATE_DOCUMENTS_WITH_UP_WIDTH) ? 19 : $conf->global->ULTIMATE_DOCUMENTS_WITH_UP_WIDTH), // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'PuAfter'
			),
			'content' => array(
				'align' => 'R'
			),
			'border-left' => false, // add left line separator
		);

		if ($this->atleastonediscount && !empty($conf->global->ULTIMATE_GENERATE_PROPOSALS_WITH_PUAFTER)) {
			$this->cols['upafter']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['upafter']['border-left'] = true;
		}

		$rank = $rank + 10;
		$this->cols['qty'] = array(
			'rank' => $rank,
			'width' => (empty($conf->global->ULTIMATE_DOCUMENTS_WITH_QTY_WIDTH) ? 16 : $conf->global->ULTIMATE_DOCUMENTS_WITH_QTY_WIDTH), // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Qty'
			),
			'content' => array(
				'align' => 'R'
			),
			'border-left' => false, // add left line separator
		);

		if (!empty($conf->global->ULTIMATE_GENERATE_PROPOSALS_WITH_QTY)) {
			$this->cols['qty']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['qty']['border-left'] = true;
		}

		$rank = $rank + 10;
		$this->cols['weight'] = array(
			'rank' => $rank,
			'width' => 12, // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Weight'
			),
			'content' => array(
				'align' => 'R'
			),
			'border-left' => false, // add left line separator
		);

		if (!empty($conf->global->ULTIMATE_GENERATE_PROPOSALS_WITH_WEIGHT_COLUMN)) {
			$this->cols['weight']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['weight']['border-left'] = true;
		}

		$rank = $rank + 10;
		$this->cols['unit'] = array(
			'rank' => $rank,
			'width' => (empty($conf->global->ULTIMATE_DOCUMENTS_WITH_UNIT_WIDTH) ? 11 : $conf->global->ULTIMATE_DOCUMENTS_WITH_UNIT_WIDTH), // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Unit'
			),
			'content' => array(
				'align' => 'L'
			),
			'border-left' => false, // add left line separator
		);

		if ($conf->global->PRODUCT_USE_UNITS) {
			$this->cols['unit']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['unit']['border-left'] = true;
		}

		$rank = $rank + 10;
		$this->cols['totalexcltax'] = array(
			'rank' => $rank,
			'width' => 26, // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'TotalHT'
			),
			'content' => array(
				'align' => 'R'
			),
			'border-left' => false, // add left line separator
		);

		if (!$conf->global->ULTIMATE_SHOW_LINE_TTTC) {
			$this->cols['totalexcltax']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['totalexcltax']['border-left'] = true;
		}

		$rank = $rank + 10;
		$this->cols['totalincltax'] = array(
			'rank' => $rank,
			'width' => 26, // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'TotalTTC'
			),
			'content' => array(
				'align' => 'R'
			),
			'border-left' => false, // add left line separator
		);

		if ($conf->global->ULTIMATE_SHOW_LINE_TTTC) {
			$this->cols['totalincltax']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['totalincltax']['border-left'] = true;
		}

		$parameters = array(
			'object' => $object,
			'outputlangs' => $outputlangs,
			'hidedetails' => $hidedetails,
			'hidedesc' => $hidedesc,
			'hideref' => $hideref
		);

		$reshook = $hookmanager->executeHooks('defineColumnField', $parameters, $this);    // Note that $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		} elseif (empty($reshook)) {
			$this->cols = array_replace($this->cols, $hookmanager->resArray); // array_replace is used to preserve keys
		} else {
			$this->cols = $hookmanager->resArray;
		}
	}
}
	
