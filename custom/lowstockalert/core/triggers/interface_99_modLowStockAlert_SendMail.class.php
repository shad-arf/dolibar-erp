<?php
/* Copyright (C) 2021       Marcello Gribaudo <marcello.gribaudo@opigi.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modLowStockAlert_LowStockAlertTriggers.class.php
 * \ingroup lowstockalert
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modLowStockAlert_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 * - The constructor method must be named InterfaceMytrigger
 * - The name property name must be MyTrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for LowStockAlert module
 */
class InterfaceSendMail extends DolibarrTriggers {
    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)  {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "demo";
        $this->description = "LowStockAlert triggers.";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version = 'dolibarr';
        $this->picto = 'lowstockalert@lowstockalert';
    }

    /**
     * Trigger name
     *
     * @return string Name of trigger file
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * @return string Description of trigger file
     */
    public function getDesc() {
        return $this->description;
    }


    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "runTrigger" are triggered if file
     * is inside directory core/triggers
     *
     * @param string 		$action 	Event action code
     * @param CommonObject 	$object 	Object
     * @param User 			$user 		Object user
     * @param Translate 	$langs 		Object langs
     * @param Conf 			$conf 		Object conf
     * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf) {
        if (empty($conf->lowstockalert) || empty($conf->lowstockalert->enabled)) {
            return 0; // If module is not enabled, we do nothing
        }

        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action

        // You can isolate code for each action in a separate method: this method should be named like the trigger in camelCase.
        // For example : COMPANY_CREATE => public function companyCreate($action, $object, User $user, Translate $langs, Conf $conf)
        $methodName = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($action)))));
        $callback = array($this, $methodName);
        if (is_callable($callback)) {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            return call_user_func($callback, $action, $object, $user, $langs, $conf);
        };

        // Or you can execute some code here
        switch ($action) {

            //Stock mouvement
            case 'STOCK_MOVEMENT':
                
                // Get Stock
                require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
                $langs->load("lowstockalert@lowstockalert");

                $objproduct = new Product($object->db);
                $objproduct->fetch($object->product_id);
                $option = 'nobatch';
                if (false) {
                    $option .= ',novirtual';
                }
                if ($objproduct->type != 1) {
                    require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
                    $objwarehouse = new Entrepot($object->db);
                    $objwarehouse->fetch($object->warehouse_id);
                    $enabled = $objwarehouse->array_options["options_lowstock"];

                    if ($enabled) {
                        $objproduct->load_stock($option);
                        $qty = $objproduct->stock_warehouse[$object->warehouse_id]->real;
                        if ($objproduct->seuil_stock_alerte != '' &&  $qty < (float) $objproduct->seuil_stock_alerte) {
                            // Send a mail
                            require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

                            $subject = $conf->global->LOWSTOCKALERT_EMAILOBJECT;
                            $sendto = $conf->global->LOWSTOCKALERT_EMAILTO;
                            $from = $conf->global->LOWSTOCKALERT_EMAILFROM;
                            $message = $conf->global->LOWSTOCKALERT_EMAILMESSAGE;
                            $alert = $conf->global->LOWSTOCKALERT_ALERT;
                            $message = str_replace('[ref]', $objproduct->ref, $message);
                            $message = str_replace('[label]', $objproduct->label, $message);
                            $message = str_replace('[stock]', $qty, $message);
                            $message = str_replace('[warehouse]', $objwarehouse->label, $message);

                            $mailfile = new CMailFile($subject, $sendto, $from, $message);
                            if ($mailfile->error) {
                                $langs->load("other");
                                setEventMessages($mailfile->error, $mailfile->errors, 'errors');
                                $action = 'presend';
                            } else {
                                $result = $mailfile->sendfile();
                                if (!$result) {
                                    $error = $langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), dol_escape_htmltag($sendto));
                                    $error .= $mailfile->error;
                                    setEventMessages($error, $errors, 'errors');
                                }
                            }
                            if ($alert)
                                setEventMessages($langs->trans('LowStock', $objproduct->ref, $objproduct->label, $qty, $objwarehouse->label), null, 'mesgs');
                        }
                    }
                }
            default:
                    dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
                    break;
        }

        return 0;
    }
}
