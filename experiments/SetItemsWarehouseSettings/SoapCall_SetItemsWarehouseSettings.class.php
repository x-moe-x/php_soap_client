<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_SetItemsWarehouseSettings.class.php';

class SoapCall_SetItemsWarehouseSettings extends PlentySoapCall {
	
	public static $MAX_WAREHOUSE_SETTINGS_PER_PAGE = 100;

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
		$this -> getLogger() -> debug(__FUNCTION__ . ' writing items warehouse settings ...');
		try {

			$this -> getLogger() -> debug(__FUNCTION__ . ' ... done');
		} catch(Exception $e) {
			$this -> onExceptionAction($e);
		}
	}

}
?>