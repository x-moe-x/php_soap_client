<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetItemsWarehouseSettings.class.php';
require_once ROOT . 'includes/DBLastUpdate.php';

class SoapCall_GetItemsWarehouseSettings extends PlentySoapCall {

	private $oPlentySoapRequest_GetItemsWarehouseSettings = null;

	public function __construct() {
		parent::__construct(__CLASS__);
	}

	public function execute() {
		$this -> getLogger() -> debug(__FUNCTION__);

		try {

			$oRequest_GetItemsWarehouseSettings = new Request_GetItemsWarehouseSettings();

			$this -> oPlentySoapRequest_GetItemsWarehouseSettings = $oRequest_GetItemsWarehouseSettings -> getRequest();

			/*
			 * do soap call
			 */
			$response = $this -> getPlentySoap() -> GetItemsWarehouseSettings($this -> oPlentySoapRequest_GetItemsWarehouseSettings);

			if ($response -> Success == true) {

				$this -> getLogger() -> debug(__FUNCTION__ . ' Request Success');

				// process response
				$this -> responseInterpretation($response);

			} else {
				$this -> getLogger() -> debug(__FUNCTION__ . ' Request Error');
			}
		} catch(Exception $e) {
			$this -> onExceptionAction($e);
		}
	}

	private function responseInterpretation($oPlentySoapResponse_GetItemsWarehouseSettings) {
		$this -> getLogger() -> debug(__FUNCTION__ . ' : not implemented yet');
	}

}
?>