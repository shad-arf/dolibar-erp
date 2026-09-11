<?php
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';
class modCustomerpayments extends DolibarrModules
{
	public function __construct($db)
	{
        global $langs,$conf;
		$langs->load("payments_status@customerpayments");
        $this->db = $db;
		$this->numero = 302904;
		$this->rights_class = 'customerpayments';
		$this->family = "AL MISBAH INFORMATIQUE ++";
		$this->familyinfo = array(
			'core' => array(
				'position' => '002',
				'label' => $langs->trans("AL MISBAH INFORMATIQUE ++")
			)
		);
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "customerpaymentsDescription";

		$this->editor_name = 'AL MISBAH INFORMATIQUE';
		$this->editor_url = 'https://www.almisbah.ma';
		$this->version = '1.1';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto='payments_status@customerpayments';

		$this->hidden = false;			// A condition to hide module
		$this->depends = array();		// List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array();	// List of module class names to disable if this one is disabled
		$this->conflictwith = array();	// List of module class names as string this module is in conflict with
		$this->langfiles = array("payments_status@customerpayments");
		$this->phpmin = array(5,3);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(4,0);	// Minimum version of Dolibarr required by module
		$this->warnings_activation = array();
		$this->warnings_activation_ext = array();
		if (! isset($conf->customerpayments) || ! isset($conf->customerpayments->enabled))
		{
			$conf->customerpayments=new stdClass();
			$conf->customerpayments->enabled=0;
		}
		$this->module_parts = array();
		$this->const = array();
        $this->tabs = array('thirdparty:+tabetatregclients:ETPAY_EtatDeReglementTab:payments_status@customerpayments:$user->rights->customerpayments->lire&&(($object->client==1)||($object->client==3)||($soc->client==1)||($soc->client==3)):/customerpayments/?socid=__ID__');
		$this->dictionaries=array();
        $this->boxes = array();
		$this->cronjobs = array();

		$this->rights = array();
		$r=0;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->trans("ETPAY_ReadEtatDesReglements");
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'lire';
		$r++;
		
		$this->menu = array();
		$r=0;
	}



	/**
	 *	Function called when module is enabled.
	 *	The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *	It also creates data directories
	 *
     *	@param      string	$options    Options when enabling module ('', 'noboxes')
	 *	@return     int             	1 if OK, 0 if KO
	 */
	public function init($options='')
	{
		$sql = array();
		return $this->_init($sql, $options);
	}

	/**
	 *	Function called when module is disabled.
	 *	Remove from database constants, boxes and permissions from Dolibarr database.
	 *	Data directories are not deleted
	 *
	 *	@param      string	$options    Options when enabling module ('', 'noboxes')
	 *	@return     int             	1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}

}