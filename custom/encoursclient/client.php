<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
 * Copyright (C) 2022 Julien Marchand <julien.marchand@iouston.com>
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

$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

dol_include_once("/encoursclient/class/encoursclient.class.php");

$langs->load("encours@encours");
$langs->load("companies");
$langs->load("bills");
$langs->load("orders");

$id = GETPOST('id') ? GETPOST('id','int') : GETPOST('socid','int');

// Security check
if ($user->societe_id) $id = $user->societe_id;
$result = restrictedArea($user, 'societe', $id, '&societe');

$hookmanager->initHooks(array('orderlist'));

$object = new Societe($db);
if ($id > 0) $object->fetch($id);

/*
 * Actions
 */


/*
 *	View
 */
$colspan=8;
$colspantotal=4;
$form = new Form($db);
$userstatic = new User($db);

$title = $langs->trans("ThirdParty").' - '.$langs->trans("Summary");
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title = $object->name.' - '.$langs->trans("Summary");
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';

llxHeader('', $title, $help_url);

if ($id > 0)
{
    /*
     * Affichage onglets
     */
    $param = '';
    if ($id > 0) $param.='&socid='.$id;

    $head = societe_prepare_head($object);

	dol_fiche_head($head, 'customer', $langs->trans("ThirdParty"), 0, 'company');
	dol_banner_tab($object, 'socid', '', ($user->societe_id ? 0 : 1), 'rowid', 'nom', '', '', 0, '', '', 1);
    dol_fiche_end();
    
    
    $encoursclient = new EncoursClient($db);
    $encoursclient->fetch($object->id,$object->idprof1);
        
    //on utilise le siren pour calculer l'encours
    if($conf->global->USE_IDPROF1_FOR_OUTSTANDING){
            $parent=new Societe($db);
            $nbforsiren=$encoursclient->getNumberSocieteForThisSiren($object->idprof1);
            $usesiren=1;
            
            //si il y a une maison mère, on récupère la limite d'encours de la maison mère
            if(!empty($object->parent)){
                $parent->fetch($object->parent);
                $encourslimit=$encoursclient->getOutstandingLimit($object->parent);

            }else{
                $encourslimit=$encoursclient->getOutstandingLimit($object->id);
            }
            
            if($nbforsiren>1 && empty($object->parent) && $encoursclient->getOutstandingLimit($object->id)==0 ){
                setEventMessages($langs->trans('NoParent'), null, 'warnings');
            }    
    
    // on utilise pas le siren        
    }else{
        $encourslimit=$encoursclient->getOutstandingLimit($object->id);
    }

    if($usesiren==1 && $nbforsiren>1){
        $colspan=9;
        $colspantotal=5;
    }
    
    $entite=$encoursclient->entite;

    $invoices = $encoursclient->invoices;
    $orders = $encoursclient->orders;
    $discounts = $encoursclient->discounts;

    // Sort by date
    $objects = array();
    $balance = 0;
    if (sizeof($invoices))
    {



        foreach ($invoices as $key => $invoice)
        {
            


            $invoicestatic = new Facture($db);
            $invoicestatic->fetch($invoice->id);
            $invoicestatic->entite=$invoice->entite;    
            $invoicestatic->nom=$invoice->nom; 

            $objects[$invoicestatic->date][] = $invoicestatic;
            $paiement = $invoicestatic->getSommePaiement();
            $creditnotes = $invoicestatic->getSumCreditNotesUsed();
            $deposits = $invoicestatic->getSumDepositsUsed();

        }
    }

    if (sizeof($orders))
    {
        foreach ($orders as $order)
        {
            $orderstatic = new Commande($db);
            $orderstatic->fetch($order->id);
            $orderstatic->entite=$order->entite;
            $orderstatic->nom=$order->nom;

            $objects[$orderstatic->date][] = $orderstatic;
        }
    }

    if (sizeof($discounts))
    {
        foreach ($discounts as $discount)
        {
            $discountstatic = new DiscountAbsolute($db);
            $discountstatic->fetch($discount->id);
            $discountstatic->entite=$discount->entite;
            $discountstatic->nom=$discount->nom;

            $objects[$discountstatic->datec][] = $discountstatic;
        }
    }

    
    arsort($objects);

    print load_fiche_titre($langs->trans("CustomerPreview"));
    if($usesiren==1){
        print '- '.$langs->trans('InfoOutstandingModeCalculation').$object->idprof1;

        if($nbforsiren>1 ){
            print ' '.$langs->trans('WhichIsShareWith').$nbforsiren.'  '.$langs->trans('NumberEtab');
            print '</br>- '.$langs->trans('OutStandingParent');

            if(!empty($object->parent) && empty($object->outstanding_limit)){
                print $parent->getNomUrl();
            }elseif(empty($object->parent) && !empty($object->outstanding_limit)){
                print '<span style="color:green;">'.$langs->trans('ThisisaParent').'</span>';
            }else{
                print '<span style="color:red;">'.$langs->trans('AddAParent').'</span>';
            }
            
        }
        
        
    }

    if(! $conf->global->USE_TTC_FOR_OUTSTANDING){
        print '<br>- '.$langs->trans('OutStandingModeHT');
        $mode=' ('.$langs->trans('EnCoursClientModeHT').')';

    }else{
        print '<br>- '.$langs->trans('OutStandingModeTTC');
        $mode=' ('.$langs->trans('EnCoursClientModeTTC').')';
    }
    
    print '<div class="div-table-responsive-no-min">';
    print '<table width="100%" id="tablelines" class="liste">'."\n";

    print '<tr class="liste_titre nodrag nodrop">';
    print '<td>'.$langs->trans("Societe").'</td>';
    if($usesiren==1 && $nbforsiren>1){
        print '<td>'.$langs->trans("Thirdparty").'</td>';
    }
    print '<td>'.$langs->trans("Date").'</td>';
    print '<td>'.$langs->trans("Element").'</td>';
    print '<td>'.$langs->trans("Status").'</td>';
    print '<td align="right">'.$langs->trans("Debit").$mode.'</td>';
    print '<td align="right">'.$langs->trans("Credit").$mode.'</td>';
    print '<td align="center">'.$langs->trans("Balance").'</td>';
    print '<td align="right">'.$langs->trans("Author").'</td>';
    print '</tr>';

    $totaldebit = 0;
    $totalcredit = 0;
    $balance = 0;
    $total_entities=array();
    $total_etab=array();

    if (sizeof($objects))
    {
        foreach ($objects as $time => $elements)
        {
            
            foreach ($elements as $element)
            {
                
                $userstatic = new User($db);

                $status = '';
                $date = '';
                $user = '';
                $link = '';
                $debit = '';
                $credit = '';
                $entite= $element->entite;
                $tiers = $element->nom;
                                         
                //on compile au niveau de l'entité
                if(!array_key_exists($entite, $total_entities)){
                    $total_entities[$entite]['name']=$entite;
                }  

                //on compile au niveau de l'établissement
                if(!array_key_exists($tiers, $total_etab)){
                    $total_etab[$tiers]['name']=$tiers;
                }   
                
                if ($element->element == 'facture')
                {                    
                    $paid = $element->getSommePaiement();
                    $creditnotes = $element->getSumCreditNotesUsed();
                    $deposits = $element->getSumDepositsUsed();
                    $balance += $element->total_ttc - $paiement - $creditnotes - $deposits;
                    $path=dol_buildpath('compta/facture/card.php?id=',1).$element->id;
                    $status = $element->getLibStatut(2, $paid);
                    $date = $element->date;
                    $link = $element->getNomUrl(1);
                    $tiers = $element->nom;
                    if(! $conf->global->USE_TTC_FOR_OUTSTANDING){
                        $debit = price($element->total_ht);
                        $totaldebit += $element->total_ht;
                        $total_entities[$entite]['debit']+= $element->total_ht;
                        $total_etab[$tiers]['debit']+= $element->total_ht;
                    }else{
                        $debit = price($element->total_ttc);
                        $totaldebit += $element->total_ttc;
                        $total_entities[$entite]['debit']+= $element->total_ttc;
                        $total_etab[$tiers]['debit']+= $element->total_ttc;
                    }

                    $userstatic->fetch($element->user_author);
                    $user = $userstatic->getLoginUrl(1);

                    
                               
                }
                else if ($element->element == 'commande')
                {
                    $status = $element->getLibStatut(2);
                    $date = $element->date;
                    $path=dol_buildpath('commande/card.php?id=',1).$element->id;
                    $link = $element->getNomUrl(1);
                    
                    if(! $conf->global->USE_TTC_FOR_OUTSTANDING){
                        $debit = price($element->total_ht);
                        $totaldebit += $element->total_ht;
                        $total_entities[$entite]['debit']+= $element->total_ht;
                        $total_etab[$tiers]['debit']+= $element->total_ht;
                    }else{
                        $debit = price($element->total_ttc);
                        $totaldebit += $element->total_ttc;
                        $total_entities[$entite]['debit']+= $element->total_ttc;
                        $total_etab[$tiers]['debit']+= $element->total_ttc;
                    }
                   

                    $userstatic->fetch($element->user_author_id);
                    $user = $userstatic->getLoginUrl(1); 

                    $balance += $element->total_ttc;
                                    }
                else
                {
                    
                    $date = $element->datec;
                    $link = $element->getNomUrl(0, 'discount');
                    $credit = price($element->amount_ttc);
                    if(! $conf->global->USE_TTC_FOR_OUTSTANDING){
                        $totalcredit += $element->amount_ht;
                        $total_entities[$entite]['credit']+= $element->amount_ht;
                        $total_etab[$tiers]['credit']+= $element->amount_ht;
                    }else{
                        $totalcredit += $element->amount_ttc;
                        $total_entities[$entite]['credit']+= $element->amount_ttc;
                        $total_etab[$tiers]['credit']+= $element->amount_ttc;
                    }
                    
                    $userstatic->fetch($element->fk_user);
                    $user = $userstatic->getLoginUrl(1); 
                    
                    $balance -= $element->amount_ttc;
                    
                }




                print '<tr class="oddeven">';
                print '<td>'.$entite.'</td>';
                if($usesiren==1 && $nbforsiren>1){
                   print '<td>'.$tiers.'</td>'; 
                }
                print '<td>'.dol_print_date($date,'day').'</td>';
                print '<td><a href="'.$path.'" target="_blank">'.$link.'</a></td>';
                print '<td>'.$status.'</td>';
                print '<td align="right">'.$debit.'</td>';
                print '<td align="right">'.$credit.'</td>';
                print '<td align="right">'.$current.'</td>';
                print '<td align="right">'.$userstatic->getLoginUrl(1).'</td>';
                print '</tr>'; 


            }      
        }
        
        
        //total par tiers si encours groups
        if($usesiren==1 && $nbforsiren>1){
            print '<tr class="liste_titre">';
                print '<td colspan="'.$colspan.'">'.$langs->trans("TotalByEtablissement").'</td>';
            print '</tr>';
            foreach($total_etab as $total_et){
                print '<tr class="liste_total">';
                print '<td colspan="'.$colspantotal.'">'.$langs->trans("TotalByEtab").' '.$total_et['name'].'</td>';
                print '<td align="right">'.price($total_et['debit']).'</td>';
                print '<td align="right">'.price($total_et['credit']).'</td>';
                print '<td align="right"><b>'.price(price2num($total_et['debit'] - $total_et['credit'], 'MT')).'</b> / '.price($encourslimit).'</td>';
                print '<td></td>';
                print '</tr>'; 
                } 

        }    

        //total by entity
        if($conf->global->TAKE_ALL_ENTITY_IN_OUTSTANDING){
        print '<tr class="liste_titre">';
        print '<td colspan="'.$colspan.'">'.$langs->trans("TotalByEntite").'</td>';
        print '</tr>'; 
        foreach($total_entities as $total_e){
            print '<tr class="liste_total">';
            print '<td colspan="'.$colspantotal.'">'.$langs->trans("Total").' '.$total_e['name'].'</td>';
            print '<td align="right">'.price($total_e['debit']).'</td>';
            print '<td align="right">'.price($total_e['credit']).'</td>';
            print '<td align="right"><b>'.price(price2num($total_e['debit'] - $total_e['credit'], 'MT')).'</b> / '.price($encourslimit).'</td>';
            print '<td></td>';
            print '</tr>'; 
        }
        
        }
        

        //total
        print '<tr class="liste_titre">';
        print '<td colspan="'.$colspantotal.'">'.$langs->trans("Total").'</td>';
        print '<td align="right">'.price($totaldebit).'</td>';
        print '<td align="right">'.price($totalcredit).'</td>';
        print '<td align="right"><b>'.price(price2num($totaldebit - $totalcredit, 'MT')).'</b> / '.price($encourslimit).'</td>';
        print '<td></td>';
        print '</tr>';

    }
    else
    {
        print "<tr ".$bc[false].'><td colspan="7" class="opacitymedium">'.$langs->trans("NoResults")."</td></tr>";
    }    

    print "</table>";
    print '</div>';
}
else
{
	dol_print_error($db);
}

llxFooter();

$db->close();

