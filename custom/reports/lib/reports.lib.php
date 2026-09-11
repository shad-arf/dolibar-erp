<?php
/* Copyright (C) 2012      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014      Ferran Marcet        <fmarcet@2byte.es>
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


function reportsadmin_prepare_head()
{
	global $langs, $conf, $user;
	$langs->load("reports@reports");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/reports/admin/reports.php',1);
	$head[$h][1] = $langs->trans("ReportsSetup");
	$head[$h][2] = 'configuration';
	$h++;

	return $head;
}



function check_reports_config_table(){
	global $db, $conf;

	// Create if not exist
	$sql = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."reports_config(
			  rowid           	integer AUTO_INCREMENT PRIMARY KEY,
			  entity            integer  DEFAULT 1 NOT NULL,
			  type_field        varchar(60),
			  value             varchar(255),
			  label             varchar(255)
			)ENGINE=innodb;";
	$result = $db->query($sql);
	return $result;
}


function populate_pricelevel_info(){
	global $db, $conf, $langs;
	$langs->loadLangs(array('companies'));

	// PriceLevels
	$reports_multiprices_limit = null;
	$dolibarr_multiprices_limit = null;
	$pricelevel_label = $langs->trans("PriceLevel");

	// Multiprices limit in reports_config
	$sql = "SELECT * FROM ".MAIN_DB_PREFIX."reports_config WHERE type_field LIKE 'PRODUIT_MULTIPRICES_LIMIT' AND entity = ".$conf->entity;
	$result = $db->query($sql);

	if ($result) {
		$num = $db->num_rows($result);

		if ( $num) {
			$obj = $db->fetch_object($result);
			$reports_multiprices_limit = $obj->value;

		}
	}

	// Multiprices limit in dolibarr
	if ($conf->global->PRODUIT_MULTIPRICES) {
		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "const WHERE name = 'PRODUIT_MULTIPRICES_LIMIT' AND entity = " . $conf->entity;
		$result = $db->query($sql);

		if ($result) {
			$num = $db->num_rows($result);

			if ($num) {
				$obj = $db->fetch_object($result);
				$dolibarr_multiprices_limit = $obj->value;
			} else {
				$dolibarr_multiprices_limit = 1;
			}
		}
	} else {
		$dolibarr_multiprices_limit = 1;
	}

	// If we dont have multiprices info in reports_config
	if(! $reports_multiprices_limit){

		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."reports_config WHERE type_field = 'PRODUIT_MULTIPRICES_LIMIT' AND entity = ".$conf->entity;
		$result = $db->query($sql);

		if ($result) {
			$num = $db->num_rows($result);
			if (! $num) {
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."reports_config(type_field, value, entity) VALUES('PRODUIT_MULTIPRICES_LIMIT', ".$dolibarr_multiprices_limit.", ".$conf->entity.")";
				$resql = $db->query($sql);
			}
		}
	}

	// If we do have info but it's not updated
	if($reports_multiprices_limit != $dolibarr_multiprices_limit){
		$sql = "UPDATE ".MAIN_DB_PREFIX."reports_config SET value = ".$dolibarr_multiprices_limit." WHERE type_field = 'PRODUIT_MULTIPRICES_LIMIT' AND entity = ".$conf->entity;
		$resql = $db->query($sql);

		// Reset
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."reports_config WHERE type_field LIKE 'PriceLevel' AND entity = ".$conf->entity;
		$resql = $db->query($sql);

		// Populate
		for ($pricelevel = 1; $pricelevel <= $dolibarr_multiprices_limit; $pricelevel++){
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."reports_config (";
			$sql.= "type_field, value, label, entity)";
			$sql.= " VALUES(";
			$sql.= "'PriceLevel', ";
			$sql.= $pricelevel.", ";
			$sql.= "'".$pricelevel_label.' '.$pricelevel."',";
			$sql.= $conf->entity;
			$sql.= ")";
			$resql = $db->query($sql);
		}
	}

	return $result;
}
?>
