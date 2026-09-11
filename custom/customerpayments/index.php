<?php
/**
 *  \file       customerpayments/index.php
 */


$res=@include("../main.inc.php");					// For root directory
if (! $res) $res=@include("../../main.inc.php");	// For "custom" directory
if (! $res) die("Include of main fails");
dol_include_once("/core/lib/company.lib.php");
dol_include_once("/societe/class/societe.class.php");
dol_include_once('/core/lib/company.lib.php');
dol_include_once('/compta/facture/class/facture.class.php');
dol_include_once('/compta/paiement/class/paiement.class.php');
dol_include_once('/customerpayments/lib/func.php');
$langs->load("payments_status@customerpayments");
$langs->load("companies");
$socid=GETPOST("socid",'int');
$now = dol_now();
restrictedArea($user,'customerpayments');
$html = new Form($db);	
$object = new Societe($db);
$result = $object->fetch($socid);

llxHeader("",$object->name.' '.$langs->trans('ETPAY_EtatDeReglementTab'));
$head = societe_prepare_head($object);
print dol_get_fiche_head($head, 'tabetatregclients', $langs->trans("ETPAY_EtatDeReglementTab"), 0, 'company');

dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');
print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield" width="100%">';
		print '<tr><td class="titlefield">'.$langs->trans('NatureOfThirdParty').'</td><td>';
		print $object->getTypeUrl(1);
		print '</td></tr>';
		// Customer code
		if ($object->client) {
			print '<tr><td>';
			print $langs->trans('CustomerCode');
			print '</td>';
			print '<td>';
			print showValueWithClipboardCPButton(dol_escape_htmltag($object->code_client));
			$tmpcheck = $object->check_codeclient();
			if ($tmpcheck != 0 && $tmpcheck != -5) {
				print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
			}
			print '</td>';
			print '</tr>';
		}
		// Supplier code
		if (((!empty($conf->fournisseur->enabled) && !empty($user->rights->fournisseur->lire) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || (!empty($conf->supplier_order->enabled) && !empty($user->rights->supplier_order->lire)) || (!empty($conf->supplier_invoice->enabled) && !empty($user->rights->supplier_invoice->lire))) && $object->fournisseur) {
			print '<tr><td>';
			print $langs->trans('SupplierCode').'</td><td>';
			print showValueWithClipboardCPButton(dol_escape_htmltag($object->code_fournisseur));
			$tmpcheck = $object->check_codefournisseur();
			if ($tmpcheck != 0 && $tmpcheck != -5) {
				print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
			}
			print '</td>';
			print '</tr>';
		}
		print '</table>';
	print '</div>';
	$outStandingOrders = $object->getOutstandingOrders();
	$outStandingInvoices = $object->getOutstandingBills();
	$outStandingNotPayedInvoices = $object->getOutstandingBills('customer', 1);
	$outStandingPayedInvoices = $outStandingInvoices['total_ttc'] - $outStandingInvoices['opened'];
	$outStandingUnbilledOrders = getTotalOrdersUnbilled($outStandingOrders['refs']);
	print '<div class="fichehalfright">';
		print '<div class="ficheaddleft">';
			print '<div class="underbanner clearboth"></div>';
			print '<table class="border tableforfield" width="100%">';
				print '<tr><td class="titlefield">'.$langs->trans('TotalOrders').'</td><td>';
				print price($outStandingOrders['opened'], 1, $langs, 1, -1, -1, $conf->currency);
				print '</td></tr>';
				print '<tr><td class="titlefield">'.$langs->trans('TotalOrdersNotBilled').'</td><td>';
				print price($outStandingUnbilledOrders['total_ttc'], 1, $langs, 1, -1, -1, $conf->currency);
				print '</td></tr>';
				print '<tr><td class="titlefield">'.$langs->trans('TotalInvoices').'</td><td>';
				print price($outStandingInvoices['total_ttc'], 1, $langs, 1, -1, -1, $conf->currency);
				print '</td></tr>';
				print '<tr><td class="titlefield">'.$langs->trans('TotalInvoicesPayed').'</td><td>';
				print price($outStandingPayedInvoices, 1, $langs, 1, -1, -1, $conf->currency);
				print '</td></tr>';
				print '<tr><td class="titlefield">'.$langs->trans('TotalInvoicesNotPayed').'</td><td>';
				print price($outStandingInvoices['opened'], 1, $langs, 1, -1, -1, $conf->currency);
				print '</td></tr>';
				print '<tr><td class="titlefield">'.$langs->trans('TotalLateInvoices').'</td><td>';
				print price($outStandingNotPayedInvoices['opened'], 1, $langs, 1, -1, -1, $conf->currency);
				print '</td></tr>';
			print '</table>';
		print '</div>';
	print '</div>';
print '</div>';
print '<div style="clear:both"></div>';
print dol_get_fiche_end();

if((($object->client==1)||($object->client==3)||($soc->client==1)||($soc->client==3))){
	/* --------------------------- Invoices List -------------------------- */
	print load_fiche_titre($langs->trans("CustomersInvoices").' '.$object->name, '', '');
	print '<div class="div-table-responsive">';
		print '<table class="noborder" width=100%>';
			print '<tr class="liste_titre">';
				print '<td>'.$langs->trans("Ref").'</td>';
				print '<td>'.$langs->trans("DateInvoice").'</td>';
				print '<td>'.$langs->trans("DateDue").'</td>';
				print '<td class="right">'.$langs->trans("AmountHT").'</td>';
				print '<td class="right">'.$langs->trans("AmountTTC").'</td>';
				print '<td class="right">'.$langs->trans("Received").'</td>';
				print '<td class="right">'.$langs->trans("Rest").'</td>';
				print '<td class="right">'.$langs->trans("Status").'</td>';
			print '</tr>';
			$factureObj = new Facture($db);
			$invoicesList = $factureObj->liste_array(1,0,'',$object->id);
			
			foreach ($invoicesList as $id => $ref) {
				$factureObj->fetch($id);
				$paiement = $factureObj->getSommePaiement();
				$totalcreditnotes = $factureObj->getSumCreditNotesUsed();
				$totaldeposits = $factureObj->getSumDepositsUsed();
				$totalpay = $paiement + $totalcreditnotes + $totaldeposits;
				$remaintopay = price2num($factureObj->total_ttc - $totalpay);
				$date_lim_reglement = ($factureObj->hasDelay()) ?  dol_print_date($factureObj->date_lim_reglement, 'day').' '.img_warning($langs->trans('PaymentLate')) : dol_print_date($factureObj->date_lim_reglement, 'day');
				$paymentsList = getPaymentsOnInvoice($id);
				$paymentObj = new Paiement($db);
				print '<tr class="odd">';
					print '<td>'.$factureObj->getNomUrl(1).'</td>';
					print '<td>'.dol_print_date($factureObj->date, 'day').'</td>';
					print '<td>'.$date_lim_reglement.'</td>';
					print '<td class="right">'.price($factureObj->total_ht, 0, $langs, 0, -1, -1, $conf->currency).'</td>';
					print '<td class="right">'.price($factureObj->total_ttc, 0, $langs, 0, -1, -1, $conf->currency).'</td>';
					print '<td class="right">'.price($totalpay, 0, $langs, 0, -1, -1, $conf->currency).'</td>';
					print '<td class="right">'.price($remaintopay, 0, $langs, 0, -1, -1, $conf->currency).'</td>';
					print '<td class="right">'.$factureObj->getLibStatut(5, $totalpay).'</td>';
				print '</tr>';
				print '<tr class="odd">';
					print '<td colspan="4"></td>';
					print '<td colspan="4" style="padding:0">';
						print '<table width="100%" cellpading="0" cellspacing="0" style="border:1px solid rgb(215,215,215)">';
							print '<tr class="liste_titre">';
								print '<td class="center" colspan="4"><strong>'.$langs->trans("ListOf", " ").$langs->trans("Payments").'</strong></td>';
							print '</tr>';
							print '<tr class="liste_titre">';
								print '<td class="right">'.$langs->trans("Ref").'</td>';
								print '<td class="right">'.$langs->trans("DatePayment").'</td>';
								print '<td class="right">'.$langs->trans("Amount").'</td>';
								print '<td class="right">'.$langs->trans("Type").'</td>';
							print '</tr>';
							foreach ($paymentsList as $payment) {
								$paymentObj->fetch('',$payment->ref);
								print '<tr>';
									print '<td class="right">'.$paymentObj->getNomUrl(1).'</td>';
									print '<td class="right">'.dol_print_date($payment->dp, 'day').'</td>';
									print '<td class="right">'.price($payment->amount, 0, $langs, 0, -1, -1, $conf->currency).'</td>';
									print '<td class="right">'.$payment->payment_code.'</td>';
								print '</tr>';
							}
						print '</table>';
					print'</td>';
					
				print '</tr>';
			}
		print '</table>';
		
		print '<div class="inline-block divButAction" style="margin-top:20px; margin-bottom:20px">';
				print '<strong style="font-size:1.2em">'.$langs->trans("ExportToExcel").'</strong>&nbsp;&nbsp;';
				print '<a href="export.php?action=all&socid='.$_GET['socid'].'" class="butAction">'.$langs->trans("ExportAll").'</a>';
				print '<a href="export.php?action=imp&socid='.$_GET['socid'].'" class="butAction">'.$langs->trans("ExportNotPayed").'</a>';
				print '<a href="export.php?action=ret&socid='.$_GET['socid'].'" class="butAction">'.$langs->trans("ExportLate").'</a>';
		print '</div>';
	print '</div>';
}
llxFooter();
$db->close();