<?php
// -----------------------------------------------------------------------------
// -- Reportico -----------------------------------------------------------------
// -----------------------------------------------------------------------------
// Module : config.php
//
// General User Configuration Settings for SeekWell Operation
// -----------------------------------------------------------------------------

// Location of SeekWell Top Level Directory From Browser Point of View

global $conf,$langs,$db, $dolibarr_main_db_pass, $mysoc;

define('SW_HTTP_BASEDIR', './');
define('SW_DEFAULT_PROJECT', 'reports');

// Identify whether to always run in into Debug Mode
define('SW_ALLOW_OUTPUT', true);
define('SW_ALLOW_DEBUG', true);

// Identify whether Show Criteria is default option
define('SW_DEFAULT_SHOWCRITERIA', true);

// Specification of Safe Mode. Turn on SAFE mode by specifying true.
// In SAFE mode, design of reports is allowed but Code and SQL Injection
// are prevented. This means that the designer prevents entry of potentially
// cdangerous ustom PHP source in the Custom Source Section or potentially
// dangerous SQL statements in Pre-Execute Criteria sections
define('SW_SAFE_DESIGN_MODE',false);

// If false prevents any designing of reports
define('SW_ALLOW_MAINTAIN', true);

//  Stylesheets
define('SW_STYLESHEET', 'stylesheet/cleanandsimple.css');

//define("SW_LANGUAGE", "en_us");

// DB connection details for ADODB
$sCurrentVersion = phpversion();
if ( version_compare($sCurrentVersion, "5.5.0") >= 0 ) {
	if ($conf->db->type=='mysql')
	{
		define('SW_DB_DRIVER', 'mysqli');
	}
	else
	{
		define('SW_DB_DRIVER', $conf->db->type);
	}
}
else{
	if ($conf->db->type=='mysqli')
	{
		define('SW_DB_DRIVER', 'mysql');
	}
	else
	{
		define('SW_DB_DRIVER', $conf->db->type);
	}
}

define('SW_DB_USER', $conf->db->user);
define('SW_DB_PASSWORD', $dolibarr_main_db_pass); 
if ($conf->db->port)
{
	if ( version_compare($sCurrentVersion, "5.5.0") >= 0 ) {
		define('SW_DB_HOST', $conf->db->host);
		if(!empty($conf->db->port)){
			define('SW_DB_PORT', $conf->db->port);
		}

	} else {
		define('SW_DB_HOST', $conf->db->host.':'.$conf->db->port);
	}

}
else
{
	define('SW_DB_HOST', $conf->db->host);
}

define('SW_DB_DATABASE', $conf->db->name); 
define('SW_DB_CONNECT_FROM_CONFIG', true);
define('SW_DB_DATEFORMAT', 'Y-m-d');
define('SW_PREP_DATEFORMAT', str_replace('%','',$conf->format_date_short));
define('SW_DB_SERVER', '');
define('SW_DB_PROTOCOL', '');
define('SW_DB_ENCODING', 'UTF8');

//HTML Output Encoding
define('SW_OUTPUT_ENCODING', 'UTF8');

// Identify temp area
define('SW_TMP_DIR', "tmp");

// Identify whether to use Smarty Templating Engine
//$langs->load("2reports@2repots");
define('SW_MESSAGE_NODATA', $langs->trans("msgNodata"));
define('SW_MESSAGE_ERRORLIST', $langs->trans("msgErrorList"));
define('SW_MESSAGE_BACK', $langs->trans("msgGoBack"));
define('SW_MESSAGE_DEBUGLIST', 'Debug:');

// SOAP Environment
define('SW_SOAP_NAMESPACE', 'reportico.org');
define('SW_SOAP_SERVICEBASEURL', 'http://www.reportico.co.uk/swsite/site/tutorials');

// Parameter Defaults
define('SW_DEFAULT_PageSize', 'A4');
define('SW_DEFAULT_PageOrientation', 'Landscape');
define('SW_DEFAULT_TopMargin', "1cm");
define('SW_DEFAULT_BottomMargin', "2cm");
define('SW_DEFAULT_LeftMargin', "1cm");
define('SW_DEFAULT_RightMargin', "1cm");
define('SW_DEFAULT_pdfFont', "Helvetica");
define('SW_DEFAULT_pdfFontSize', "10");

// FPDF parameters
define('FPDF_FONTPATH', dol_buildpath('/reports/includes/reportico/fpdf/font/',0));

// Include an image in your PDF output
// This defalt places icon top right of a portrait image and sizes it to 100 pixels wide
define('PDF_HEADER_IMAGE', $conf->mycompany->dir_output.'/logos/'.$mysoc->logo);
define('PDF_HEADER_XPOS', '20');
define('PDF_HEADER_YPOS', '15');
define('PDF_HEADER_WIDTH', '100');

// Graph Defaults
// Default Charting Engine is JpGraph. A slightly modified version 3.0.7 of jpGraph is supplied
// within Reportico. 
// 
// Reportico also supports pChart but the pChart package is not currently provided
// as part of the Reportico bundle. To use pChart you will need to unpack the pChart
// application into the reportico folder named pChart. pChart 2.1.3
// You can get pChart from http://www.pchart.net/
//
define("SW_GRAPH_ENGINE", "PCHART" );
if ( !defined("SW_GRAPH_ENGINE") || SW_GRAPH_ENGINE == "JPGRAPH" )
{
define('SW_DEFAULT_Font', "Arial");
//advent_light
//Bedizen
//Mukti_Narrow
//calibri
//Forgotte
//GeosansLight
//MankSans
//pf_arma_five
//Silkscreen
//verdana
define('SW_DEFAULT_GraphWidth', 800);
define('SW_DEFAULT_GraphHeight', 400);
define('SW_DEFAULT_GraphWidthPDF', 500);
define('SW_DEFAULT_GraphHeightPDF', 250);
define('SW_DEFAULT_GraphColor', "white");
define('SW_DEFAULT_MarginTop', "40");
define('SW_DEFAULT_MarginBottom', "90");
define('SW_DEFAULT_MarginLeft', "60");
define('SW_DEFAULT_MarginRight', "50");
define('SW_DEFAULT_MarginColor', "white");
define('SW_DEFAULT_XTickLabelInterval', "1");
define('SW_DEFAULT_YTickLabelInterval', "2");
define('SW_DEFAULT_XTickInterval', "1");
define('SW_DEFAULT_YTickInterval', "1");
define('SW_DEFAULT_GridPosition', "back");
define('SW_DEFAULT_XGridDisplay', "none");
define('SW_DEFAULT_XGridColor', "gray");
define('SW_DEFAULT_YGridDisplay', "major");
define('SW_DEFAULT_YGridColor', "gray");
define('SW_DEFAULT_TitleFont', SW_DEFAULT_Font);
define('SW_DEFAULT_TitleFontStyle', "Normal");
define('SW_DEFAULT_TitleFontSize', "12");
define('SW_DEFAULT_TitleColor', "black");
define('SW_DEFAULT_XTitleFont', SW_DEFAULT_Font);
define('SW_DEFAULT_XTitleFontStyle', "Normal");
define('SW_DEFAULT_XTitleFontSize', "10");
define('SW_DEFAULT_XTitleColor', "black");
define('SW_DEFAULT_YTitleFont', SW_DEFAULT_Font);
define('SW_DEFAULT_YTitleFontStyle', "Normal");
define('SW_DEFAULT_YTitleFontSize', "10");
define('SW_DEFAULT_YTitleColor', "black");
define('SW_DEFAULT_XAxisFont', SW_DEFAULT_Font);
define('SW_DEFAULT_XAxisFontStyle', "Normal");
define('SW_DEFAULT_XAxisFontSize', "10");
define('SW_DEFAULT_XAxisFontColor', "black");
define('SW_DEFAULT_XAxisColor', "black");
define('SW_DEFAULT_YAxisFont', SW_DEFAULT_Font);
define('SW_DEFAULT_YAxisFontStyle', "Normal");
define('SW_DEFAULT_YAxisFontSize', "8");
define('SW_DEFAULT_YAxisFontColor', "black");
define('SW_DEFAULT_YAxisColor', "black");
}
else // Use jpgraph
{
define('SW_DEFAULT_Font', "Mukti_Narrow.ttf");
//advent_light.ttf
//Bedizen.ttf
//calibri.ttf
//Forgotte.ttf
//GeosansLight.ttf
//MankSans.ttf
//pf_arma_five.ttf
//Silkscreen.ttf
//verdana.ttf
define('SW_DEFAULT_FontSize', "8");
define('SW_DEFAULT_FontColor', "#303030");
define('SW_DEFAULT_LineColor', "#303030");
define('SW_DEFAULT_BackColor', "#eeeeff");
define('SW_DEFAULT_FontStyle', "Normal");
define('SW_DEFAULT_GraphWidth', 800);
define('SW_DEFAULT_GraphHeight', 400);
define('SW_DEFAULT_GraphWidthPDF', 500);
define('SW_DEFAULT_GraphHeightPDF', 300);
define('SW_DEFAULT_GraphColor', SW_DEFAULT_BackColor);
define('SW_DEFAULT_MarginTop', "50");
define('SW_DEFAULT_MarginBottom', "80");
define('SW_DEFAULT_MarginLeft', "70");
define('SW_DEFAULT_MarginRight', "40");
define('SW_DEFAULT_MarginColor', SW_DEFAULT_BackColor);
define('SW_DEFAULT_XTickLabelInterval', "AUTO");
define('SW_DEFAULT_YTickLabelInterval', "2");
define('SW_DEFAULT_XTickInterval', "1");
define('SW_DEFAULT_YTickInterval', "1");
define('SW_DEFAULT_GridPosition', "back");
define('SW_DEFAULT_XGridDisplay', "major");
define('SW_DEFAULT_XGridColor', SW_DEFAULT_LineColor);
define('SW_DEFAULT_YGridDisplay', "major");
define('SW_DEFAULT_YGridColor', SW_DEFAULT_LineColor);
define('SW_DEFAULT_TitleFont', SW_DEFAULT_Font);
define('SW_DEFAULT_TitleFontStyle', SW_DEFAULT_FontStyle);
define('SW_DEFAULT_TitleFontSize', 12); 
define('SW_DEFAULT_TitleColor', SW_DEFAULT_LineColor);
define('SW_DEFAULT_XTitleFont', SW_DEFAULT_Font);
define('SW_DEFAULT_XTitleFontStyle', SW_DEFAULT_FontStyle);
define('SW_DEFAULT_XTitleFontSize', SW_DEFAULT_FontSize);
define('SW_DEFAULT_XTitleColor', SW_DEFAULT_LineColor);
define('SW_DEFAULT_YTitleFont', SW_DEFAULT_Font);
define('SW_DEFAULT_YTitleFontStyle', SW_DEFAULT_FontStyle);
define('SW_DEFAULT_YTitleFontSize', SW_DEFAULT_FontSize);
define('SW_DEFAULT_YTitleColor', SW_DEFAULT_LineColor);
define('SW_DEFAULT_XAxisFont', SW_DEFAULT_Font);
define('SW_DEFAULT_XAxisFontStyle', SW_DEFAULT_FontStyle);
define('SW_DEFAULT_XAxisFontSize', SW_DEFAULT_FontSize);
define('SW_DEFAULT_XAxisFontColor', SW_DEFAULT_FontColor);
define('SW_DEFAULT_XAxisColor', SW_DEFAULT_LineColor);
define('SW_DEFAULT_YAxisFont', SW_DEFAULT_Font);
define('SW_DEFAULT_YAxisFontStyle', SW_DEFAULT_FontStyle);
define('SW_DEFAULT_YAxisFontSize', SW_DEFAULT_FontSize);
define('SW_DEFAULT_YAxisFontColor', SW_DEFAULT_LineColor);
define('SW_DEFAULT_YAxisColor', SW_DEFAULT_LineColor);
}
?>
