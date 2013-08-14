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

			$oRequest_GetItemsWarehouseSettings = new Request_GetItemsWarehouseSettings(1);

			$this -> oPlentySoapRequest_GetItemsWarehouseSettings = $oRequest_GetItemsWarehouseSettings -> getRequest(array('15-0-1', '8-0-0'), 1);

			/*
			 * do soap call
			 */
			$response = $this -> getPlentySoap() -> GetItemsWarehouseSettings($this -> oPlentySoapRequest_GetItemsWarehouseSettings);

			if (($response -> Success == true) && isset($response -> ItemList)) {

				$this -> getLogger() -> debug(__FUNCTION__ . ' Request Success');

				// process response
				$this -> responseInterpretation($response);
			} else if (($response -> Success == true) && !isset($response -> ItemList)) {
				$this -> getLogger() -> debug(__FUNCTION__ . ' Request Success but no items available');
			} else {
				if (isset($response -> ResponseMessages -> item) && is_array($response -> ResponseMessages -> item)) {
					$errorString = '';
					foreach ($response -> ResponseMessages -> item as $message) {
						if (isset($message -> ErrorMessages -> item) && is_array($message -> ErrorMessages -> item)) {
							foreach ($message -> ErrorMessages -> item as $errorMessage) {
								$errorString .= $errorMessage -> Key . ': ' . $errorMessage -> Value;
								$errorString .= ', ';
							}

						}
					}
					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Error: ' . ($errorString != '' ? $errorString : 'unable to retreive error messages'));
				} else {
					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Error');
				}
			}
		} catch(Exception $e) {
			$this -> onExceptionAction($e);
		}
	}

	private function responseInterpretation($oPlentySoapResponse_GetItemsWarehouseSettings) {
		$this -> getLogger() -> debug(__FUNCTION__ . ' : not implemented yet');
		print_r($oPlentySoapResponse_GetItemsWarehouseSettings);
	}

}
?>