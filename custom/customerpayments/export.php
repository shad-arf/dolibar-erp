<?php
$res=@include("../main.inc.php");					// For root directory
if (! $res) $res=@include("../../main.inc.php");	// For "custom" directory
if (! $res) die("Include of main fails");
restrictedArea($user,'customerpayments');

$action=GETPOST('action','alpha');
$socid=GETPOST("socid",'int');

dol_include_once("/core/lib/company.lib.php");
dol_include_once("/societe/class/societe.class.php");
dol_include_once('/core/lib/company.lib.php');
dol_include_once('/compta/facture/class/facture.class.php');
dol_include_once('/compta/paiement/class/paiement.class.php');
dol_include_once('/customerpayments/lib/func.php');

$langs->load("payments_status@customerpayments");
$langs->load("companies");
$langs->load("bills");

function replaceWith($str){
	$htmlchar = array('&eacute;','&agrave;','&egrave;','&ecirc;','&ccedil;','&Eacute;');
	$textchar = array('é','à','è','ê','ç','É');

	return str_replace($htmlchar,$textchar,$str);
}
	
$soc = new Societe($db);
$result = $soc->fetch($socid);

require_once DOL_DOCUMENT_ROOT.'/includes/phpoffice/phpspreadsheet/src/autoloader.php';
require_once DOL_DOCUMENT_ROOT.'/includes/Psr/autoloader.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
$styleTitle = array(
						'font' => [
							'name' => 'Arial',
							'size' => 16,
							'bold' => true,
							'color' => ['rgb' => '000000'],
						],
						'alignment' => [
							'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
							'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
						],
				);
$styleCases = array(
						'font' => [
							'name' => 'Arial',
							'size' => 12,
							'bold' => false,
							'color' => ['rgb' => '000000'],
						],
						'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,],
				);
$styleEmpty = [
	'borders' => [
		'outline' => [
			'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
		],
	],
];
$styleHeader = array(
						'font' => [
							'name' => 'Arial',
							'size' => 12,
							'bold' => true,
							'color' => ['rgb' => '000000'],
						],
						'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,],
						'fill' => [
							'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
							'startColor' => ['argb' => 'FF428BCA',],
							'endColor' => ['argb' => 'FFFFFFFF',],
						],
						'borders' => [
							'outline' => [
								'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
							],
						],
				);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet()->setTitle($soc->name);

$sheet->getRowDimension(1)->setRowHeight(30);
$sheet->getRowDimension(3)->setRowHeight(25);

$sheet->getColumnDimension('A')->setWidth('20');
$sheet->getColumnDimension('B')->setWidth('20');
$sheet->getColumnDimension('C')->setWidth('30');
$sheet->getColumnDimension('D')->setWidth('20');
$sheet->getColumnDimension('E')->setWidth('28');
$sheet->getColumnDimension('F')->setWidth('28');
$sheet->getColumnDimension('G')->setWidth('20');
$sheet->getColumnDimension('H')->setWidth('20');

$sheet->getStyle('A1:H1')->applyFromArray($styleTitle);
$sheet->getStyle('A2:H2')->applyFromArray($styleEmpty);
$sheet->getStyle('A3')->applyFromArray($styleHeader);
$sheet->getStyle('B3')->applyFromArray($styleHeader);
$sheet->getStyle('C3')->applyFromArray($styleHeader);
$sheet->getStyle('D3')->applyFromArray($styleHeader);
$sheet->getStyle('E3')->applyFromArray($styleHeader);
$sheet->getStyle('F3')->applyFromArray($styleHeader);
$sheet->getStyle('G3')->applyFromArray($styleHeader);
$sheet->getStyle('H3')->applyFromArray($styleHeader);

$sheet->mergeCells('A1:H1');
$title = '';
switch($action){
	case 'imp':
		$title = replaceWith($langs->trans("EtatdeRegelementCLientImpayed", $soc->name));
		break;
	case 'ret':
		$title = replaceWith($langs->trans("EtatdeRegelementCLientRetard", $soc->name));
		break;
	default:
		$title = replaceWith($langs->trans("EtatdeRegelementCLientTout", $soc->name));
		break;
}
if($action == 'all') ;
$sheet->setCellValue('A1',$title.' '.replaceWith($langs->trans('AtDate', dol_print_date(dol_now(), 'day'))));

$sheet->mergeCells('A2:H2');

$sheet->setCellValue('A3',replaceWith($langs->trans("Ref")));
$sheet->setCellValue('B3',replaceWith($langs->trans("DateInvoice")));
$sheet->setCellValue('C3',replaceWith($langs->trans("DateDue")));
$sheet->setCellValue('D3',replaceWith($langs->trans("AmountHT")));
$sheet->setCellValue('E3',replaceWith($langs->trans("AmountTTC")));
$sheet->setCellValue('F3',replaceWith($langs->trans("Received")));
$sheet->setCellValue('G3',replaceWith($langs->trans("Rest")));
$sheet->setCellValue('H3',replaceWith($langs->trans("Status")));

$factureObj = new Facture($db);
$invoicesList = $factureObj->liste_array(1,0,'',$soc->id);
$total_ht = $total_ttc = $total_received = $total_due = 0;
$i=4;
foreach ($invoicesList as $id => $ref) {
	$factureObj->fetch($id);
	$includeThis = true;
	if(($action=='imp') && ($factureObj->status==Facture::STATUS_CLOSED&&$factureObj->close_code==null)){
		$includeThis = false;
	} elseif(($action=='ret') && (!$factureObj->hasDelay())){
		$includeThis = false;
	}
	if($includeThis){
		$paiement = $factureObj->getSommePaiement();
		$totalcreditnotes = $factureObj->getSumCreditNotesUsed();
		$totaldeposits = $factureObj->getSumDepositsUsed();
		$totalpay = $paiement + $totalcreditnotes + $totaldeposits;
		$remaintopay = price2num($factureObj->total_ttc - $totalpay);
		$total_ht += $factureObj->total_ht;
		$total_ttc += $factureObj->total_ttc;
		$total_received += $totalpay;
		$total_due += $remaintopay;

		$sheet->setCellValue('A'.$i,replaceWith($factureObj->ref));
		$sheet->setCellValue('B'.$i,dol_print_date($factureObj->date, 'day'));
		$sheet->setCellValue('C'.$i,dol_print_date($factureObj->date_lim_reglement, 'day'));
		$sheet->setCellValue('D'.$i,$factureObj->total_ht);
		$sheet->setCellValue('E'.$i,$factureObj->total_ttc);
		$sheet->setCellValue('F'.$i,$totalpay);
		$sheet->setCellValue('G'.$i,$remaintopay);
		$sheet->setCellValue('H'.$i,$factureObj->getLibStatut(1, $totalpay));
		
		$sheet->getStyle('A'.$i)->applyFromArray($styleCases);
		$sheet->getStyle('B'.$i)->applyFromArray($styleCases);
		$sheet->getStyle('C'.$i)->applyFromArray($styleCases);
		$sheet->getStyle('D'.$i)->applyFromArray($styleCases)->getNumberFormat()
		->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
		$sheet->getStyle('E'.$i)->applyFromArray($styleCases)->getNumberFormat()
		->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
		$sheet->getStyle('F'.$i)->applyFromArray($styleCases)->getNumberFormat()
		->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
		$sheet->getStyle('G'.$i)->applyFromArray($styleCases)->getNumberFormat()
		->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
		$sheet->getStyle('H'.$i)->applyFromArray($styleCases);
	
		$i++;
	}
}
$i++;

$sheet->setCellValue('C'.$i,$langs->trans('Total'));
$sheet->setCellValue('D'.$i,$total_ht);
$sheet->setCellValue('E'.$i,$total_ttc);
$sheet->setCellValue('F'.$i,$total_received);
$sheet->setCellValue('G'.$i,$total_due);
$sheet->getStyle('A'.$i)->applyFromArray($styleHeader);
$sheet->getStyle('B'.$i)->applyFromArray($styleHeader);
$sheet->getStyle('C'.$i)->applyFromArray($styleHeader);
$sheet->getStyle('D'.$i)->applyFromArray($styleHeader)->getNumberFormat()
->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
$sheet->getStyle('E'.$i)->applyFromArray($styleHeader)->getNumberFormat()
->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
$sheet->getStyle('F'.$i)->applyFromArray($styleHeader)->getNumberFormat()
->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
$sheet->getStyle('G'.$i)->applyFromArray($styleHeader)->getNumberFormat()
->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
$sheet->getStyle('H'.$i)->applyFromArray($styleHeader);



$writer = new Xlsx($spreadsheet);
header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition:inline;filename='.$langs->trans('Outputfilename').strtolower($soc->name).'-'.dol_print_date(dol_now(), 'day').'.xlsx ');
$writer->save('php://output');
unset($spreadsheet);