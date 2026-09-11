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

/**
 *  \file       htdocs/encoursclient/class/encoursclient.class.php
 *  \ingroup    encoursclient
 *  \brief      File of class to manage predefined products sets
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 * Class to manage products or services
 */
class SetupEncoursClient extends CommonObject
{
	
	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $langs;

		$this->db = $db;
	}

	
	function update_siren_from_siret(){
		global $langs;

	$sql='UPDATE '.MAIN_DB_PREFIX.'societe SET siren = SUBSTR(siret,1,9) WHERE siren ="" AND siret !=""';

	$resql = $this->db->query($sql);
		if ( $resql )
		{
			setEventMessages($langs->trans('EnCoursClientNbSIRENUpdated').$nbsirenmaj, null, 'mesgs');
			$this->db->free($resql);
			return 1;
		}else{
			return -1;
		}
			
	}

	
}

