<?php
/*
 Reportico - PHP Reporting Tool
 Copyright (C) 2010-2014 Peter Deed

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

 * File:        reportico_report_xls.php
 *
 * Base class for all report output formats.
 * Defines base functionality for handling report
 * page headers, footers, group headers, group trailers
 * data lines
 *
 * @link http://www.reportico.org/
 * @copyright 2010-2014 Peter Deed
 * @author Peter Deed <info@reportico.org>
 * @package Reportico
 * @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @version $Id: swoutput.php,v 1.33 2014/05/17 15:12:31 peter Exp $
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

require_once("reportico_report.php");

class reportico_report_xls extends reportico_report
{
	var $abs_top_margin;
	var $abs_bottom_margin;
	var $abs_left_margin;
	var $abs_right_margin;
	//cambio 2byte.es
	var $csv_file;

	function __construct()
	{
	}

	function start()
	{
		reportico_report::start();

		$this->debug("Excel Start **");

		$this->page_line_count = 0;

		// Start the web page
		if (ob_get_length() > 0) {
			ob_clean();
		}
		header("Content-type: application/vnd.ms-excel");

		/*$attachfile = "reportico.xls";
		if ($this->reporttitle) {
			$attachfile = preg_replace("/ /", "_", $this->reporttitle . ".xls");
		}
		header('Content-Disposition: attachment; filename='.$attachfile);
		header("Pragma: no-cache");
		header("Expires: 0");*/

		$this->debug("Excel Begin Page\n");
		//cambio 2byte.es ini
		//echo '"'."$this->reporttitle".'"';
		//echo "\n";
		//cambio 2byte.es fin
		//require_once PHPEXCEL_PATH . 'PHPExcel.php';
        //$this->csv_file = new PHPExcel();
		if (version_compare(DOL_VERSION, 14) > 0) {
			require_once DOL_DOCUMENT_ROOT.'/includes/phpoffice/phpspreadsheet/src/autoloader.php';
		} else {
			require_once DOL_DOCUMENT_ROOT.'/includes/phpoffice/autoloader.php';
		}
        require_once DOL_DOCUMENT_ROOT.'/includes/Psr/autoloader.php';
        require_once PHPEXCELNEW_PATH.'Spreadsheet.php';
		$this->csv_file = new Spreadsheet();

		$this->csv_file->getProperties()->setCreator("Dolibarr");
		$this->csv_file->getProperties()->setTitle($this->reporttitle);
		$this->csv_file->getProperties()->setSubject($this->reporttitle);
		$this->csv_file->getProperties()->setDescription($this->reporttitle);

		$this->csv_file->setActiveSheetIndex(0);
		$this->csv_file->getActiveSheet()->setTitle(dol_trunc(str_replace('/',' ',$this->reporttitle),25));
		$this->csv_file->getActiveSheet()->getDefaultRowDimension()->setRowHeight(16);
	}

	function finish()
	{
		reportico_report::finish();
		//if ( $this->line_count < 1 )
		//{
		//// No xls data found just return
		//return;
		//}

		if ($this->report_file) {
			$this->debug("Saved to $this->report_file");
		} else {
			$this->debug("No xls file specified !!!");
			$buf = "";
			$len = strlen($buf) + 1;

			print($buf);
			die;
		}

	}

	function format_column_header(& $column_item)
	{

		if (!$this->show_column_header($column_item)) {
			return;
		}

		$padstring = $column_item->derive_attribute("column_title", $column_item->query_name);
		$padstring = str_replace("_", " ", $padstring);
		//cambio 2byte.es ini
		//$padstring = ucwords(strtolower($padstring));
		$padstring = sw_translate($padstring);

		$this->csv_file->getActiveSheet()->getStyle('1')->getFont()->setBold(true);
		$this->csv_file->getActiveSheet()->getStyle('1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

		$this->csv_file->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row + 1, $padstring);
		if (!empty($column_item->column_type) && in_array($column_item->column_type,
						array('Date', 'Numeric', 'char'))
		)        // Set autowidth for some types
		{
			$c = intval($this->col + 1);
			if ($c <= 0) {
				return '';
			}

			while ($c != 0) {
				$p = ($c - 1) % 26;
				$c = intval(($c - $p) / 26);
				$letter = chr(65 + $p) . $letter;
			}
			$this->csv_file->getActiveSheet()->getColumnDimension($letter)->setAutoSize(true);
		}

		$this->col++;
		//$this->row = 1;

		//echo '"'.$padstring.'"'.",";
		//cambio 2byte.es fin
	}

	function format_column(& $column_item)
	{
		if (!$this->show_column_header($column_item)) {
			return;
		}

		$padstring =& $column_item->column_value;
		// Dont allow HTML values in xls output
		if (preg_match("/^<.*>/", $padstring)) {
			$padstring = "";
		}

		$align = Alignment::HORIZONTAL_LEFT;

		//2byte.es
		$padstringtemp = str_replace(',','',$padstring);
		if (is_numeric($padstringtemp)){
			$padstring = $padstringtemp;
			$align = Alignment::HORIZONTAL_RIGHT;
		}

		// Replace Line Feeds with spaces
		$specchars = array("\r\n", "\n", "\r");
		$output = str_replace($specchars, " ", $padstring);

		// Handle double quotes by changing " to ""
		$output = str_replace("\"", "\"\"", $output);
		//cambio 2byte.es ini

		$this->csv_file->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row + 1, (string)$output);
		$coord = $this->csv_file->getActiveSheet()->getCellByColumnAndRow($this->col, $this->row + 1)->getCoordinate();
		$this->csv_file->getActiveSheet()->getStyle($coord)->getNumberFormat()->setFormatCode('@');
		$this->csv_file->getActiveSheet()->getStyle($coord)->getAlignment()->setHorizontal($align);

		$this->col++;


		//echo "\"".$output."\",";
		//cambio 2byte.es fin

	}

	function each_line($val)
	{
		reportico_report::each_line($val);

		// Excel requires group headers are printed as the first columns in the spreadsheet against
		// the detail.
		$this->col = 1;
		foreach ($this->query->groups as $name => $group) {
			if (count($group->headers) > 0) {
				foreach ($group->headers as $gphk => $col) {
					$qn = get_query_column($col["GroupHeaderColumn"]->query_name, $this->query->columns);
					$padstring = $qn->column_value;
					//cambio 2byte.es ini
					$this->csv_file->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row + 1,
							(string)$padstring);
					$coord = $this->csv_file->getActiveSheet()->getCellByColumnAndRow($this->col,
							$this->row + 1)->getCoordinate();
					$this->csv_file->getActiveSheet()->getStyle($coord)->getNumberFormat()->setFormatCode('@');
					$this->csv_file->getActiveSheet()->getStyle($coord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
					$this->col++;
					//echo "\"".$padstring."\"";
					//echo ",";
					//cambio 2byte.es fin
				}
			}
		}


		//foreach ( $this->columns as $col )
		foreach ($this->query->display_order_set["column"] as $col) {
			$this->format_column($col);
		}
		//cambio 2byte.es ini
		$this->row++;
		//echo "\n";
		//cambio 2byte.es fin

	}

	function page_template()
	{
		$this->debug("Page Template");
	}

	function begin_page()
	{
		reportico_report::begin_page();

	}

	function format_criteria_selection($label, $value)
	{
		//cambio 2byte.es ini
		$this->csv_file->getActiveSheet()->SetCellValueByColumnAndRow(0, $this->row + 1, (string)$label);
		$coord = $this->csv_file->getActiveSheet()->getCellByColumnAndRow(0, $this->row + 1)->getCoordinate();
		$this->csv_file->getActiveSheet()->getStyle($coord)->getNumberFormat()->setFormatCode('@');
		$this->csv_file->getActiveSheet()->getStyle($coord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

		$this->csv_file->getActiveSheet()->SetCellValueByColumnAndRow(1, $this->row + 1, (string)$value);
		$coord = $this->csv_file->getActiveSheet()->getCellByColumnAndRow(1, $this->row + 1)->getCoordinate();
		$this->csv_file->getActiveSheet()->getStyle($coord)->getNumberFormat()->setFormatCode('@');
		$this->csv_file->getActiveSheet()->getStyle($coord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
		$this->row++;
		//echo "\"".$label."\"";
		//echo ",";
		//echo "\"".$value."\"";
		//echo "\n";
		//cambio 2byte.es fin
	}

	function after_format_criteria_selection()
	{
		//cambio 2byte.es ini
		$this->row++;
		//echo "\n";
		//cambio 2byte.es fin
	}

	function finish_page()
	{
		$this->debug("Excel Finish Page");
		//pdf_end_page($this->document);
		//cambio 2byte.es ini
		//print $this->csv_file;
		//die;
		//cambio 2byte.es fin
		//require_once PHPEXCEL_PATH . 'PHPExcel/Writer/Excel5.php';
		//$objWriter = new PHPExcel_Writer_Excel5($this->csv_file);
        $objWriter = new Xlsx($this->csv_file);
		$file_temp = dol_buildpath("/reports/includes/reportico/templates_c/", 0) . dol_now() . ".xls";
		$objWriter->save($file_temp);
		$this->csv_file->disconnectWorksheets();
		unset($this->csv_file);


		$gestor = fopen($file_temp, "r");
		$buf = fread($gestor, filesize($file_temp));
		fclose($gestor);

		$len = strlen($buf);

		header("Content-type: application/vnd.ms-excel");
		header('Content-Disposition: attachment; filename='.$this->reporttitle.'.xls');
		//header("Content-Disposition: inline; filename=report.xls");
		header("Pragma: no-cache");
		header("Expires: 0");

		echo $buf;

		unlink($file_temp);

		/*$url = dol_buildpath("/reports/download.php", 2) . "?file=" . $file_temp . "&cvs=2";
		ini_set('display_errors', 'Off');
		print "<meta http-equiv='refresh' content='0;url=" . $url . "'>";
		header("Location: ".$url);
		exit;
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

		} else {
			die;
		}*/
	}

	function format_headers()
	{
		// Excel requires group headers are printed as the first columns in the spreadsheet against
		// the detail.
		$this->col = 1;

		foreach ($this->query->groups as $name => $group) {
			for ($i = 0; $i < count($group->headers); $i++) {
				$col =& $group->headers[$i]["GroupHeaderColumn"];
				$qn = get_query_column($col->query_name, $this->query->columns);
				$tempstring = str_replace("_", " ", $col->query_name);
				//cambio 2byte.es ini
				//$tempstring = ucwords(strtolower($tempstring));
				$this->csv_file->getActiveSheet()->getStyle('1')->getFont()->setBold(true);
				$this->csv_file->getActiveSheet()->getStyle('1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
				$this->csv_file->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row + 1,
						(string)sw_translate($col->derive_attribute("column_title", $tempstring)));
				$c = intval($this->col + 1);

				while ($c != 0) {
					$p = ($c - 1) % 26;
					$c = intval(($c - $p) / 26);
					$letter = chr(65 + $p) . $letter;
				}
				$this->csv_file->getActiveSheet()->getColumnDimension($letter)->setAutoSize(true);
				$this->col++;
				$letter = '';

				//echo "\"".sw_translate($col->derive_attribute("column_title",  $tempstring))."\"";
				//echo ",";
				//cambio 2byte.es fin
			}
		}

		foreach ($this->query->display_order_set["column"] as $w) {
			$this->format_column_header($w);
		}
		$this->coltot = $this->col;

		$this->row++;
		//cambio 2byte.es ini
		//echo "\n";
		//cambio 2byte.es fin
	}

	function format_group_header(&$col, $custom)
	{
		// Excel requires group headers are printed as the first columns in the spreadsheet against
		// the detail.
		return;

		$qn = get_query_column($col->query_name, $this->query->columns);
		$padstring = $qn->column_value;
		$tempstring = str_replace("_", " ", $col->query_name);
		$tempstring = ucwords(strtolower($tempstring));
		//cambio 2byte.es ini
		//echo sw_translate($col->derive_attribute("column_title",  $tempstring));
		//echo ": ";
		//echo "$padstring";
		//echo "\n";

		//cambio 2byte.es fin

		$this->csv_file->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row + 1,
				(string)$group_label . ":" . $padstring);
		$coord = $this->csv_file->getActiveSheet()->getCellByColumnAndRow($this->col, $this->row + 1)->getCoordinate();
		$this->csv_file->getActiveSheet()->getStyle($coord)->getNumberFormat()->setFormatCode('@');
		$this->csv_file->getActiveSheet()->getStyle($coord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

		$this->row++;
	}


	function begin_line()
	{
		return;
	}

	function format_column_trailer_before_line()
	{
		// Excel requires group headers are printed as the first columns in the spreadsheet against
		// the detail.
		$this->col = 1;
		$obj = new ArrayObject($this->query->groups);
		$it = $obj->getIterator();
		foreach ($it as $name => $group) {
			for ($i = 0; $i < count($group->headers); $i++) {
				//cambio 2byte.es ini
				$this->col++;
				//echo ",";
				//cambio 2byte.es fin
			}
		}

		//$this->row++;


	}

	function format_column_trailer(&$trailer_col, &$value_col, $trailer_first = false)
	{
		if ($value_col) {
			$group_label = $value_col["GroupTrailerValueColumn"]->get_attribute("group_trailer_label");
			if (!$group_label) {
				$group_label = $value_col["GroupTrailerValueColumn"]->get_attribute("column_title");
			}
			if (!$group_label) {
				$group_label = $value_col["GroupTrailerValueColumn"]->query_name;
				$group_label = str_replace("_", " ", $group_label);
				$group_label = ucwords(strtolower($group_label));
			}
			$group_label = sw_translate($group_label);
			$padstring = $value_col["GroupTrailerValueColumn"]->old_column_value;
			//cambio 2byte.es ini
			$this->csv_file->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row + 1,
					(string)$group_label . ":" . $padstring);
			$coord = $this->csv_file->getActiveSheet()->getCellByColumnAndRow($this->col,
					$this->row + 1)->getCoordinate();
			$this->csv_file->getActiveSheet()->getStyle($coord)->getNumberFormat()->setFormatCode('@');
			$this->csv_file->getActiveSheet()->getStyle($coord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
			//echo $group_label.":".$padstring;
			//cambio 2byte.es fin
		} else {
			$this->csv_file->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row + 1, (string)'');
			$coord = $this->csv_file->getActiveSheet()->getCellByColumnAndRow($this->col,
					$this->row + 1)->getCoordinate();
			$this->csv_file->getActiveSheet()->getStyle($coord)->getNumberFormat()->setFormatCode('@');
			$this->csv_file->getActiveSheet()->getStyle($coord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
		}
		//cambio 2byte.es ini
		if ($this->col == $this->coltot - 1) {
			$this->col = 1;
			$this->row++;
		} else {
			$this->col++;
		}
		//echo ",";
		//cambio 2byte.es fin
	}

	function end_line()
	{
		//cambio 2byte.es ini
		$this->row++;
		//echo "\n";
		//cambio 2byte.es fin
	}


	function publish()
	{
		reportico_report::publish();
		$this->debug("Publish Excel");
	}


}
