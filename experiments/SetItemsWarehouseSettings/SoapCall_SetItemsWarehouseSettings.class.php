<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_SetItemsWarehouseSettings.class.php';

class SoapCall_SetItemsWarehouseSettings extends PlentySoapCall {

	/**
	 * @return SoapCall_SetItemsWarehouseSettings
	 */
	public function __construct() {
		parent::__construct(__CLASS__);
	}

	/**
	 * @return void
	 */
	public function execute() {
		try {
			
		} catch(Exception $e) {
			$this -> onExceptionAction($e);
		}
	}
}
?>