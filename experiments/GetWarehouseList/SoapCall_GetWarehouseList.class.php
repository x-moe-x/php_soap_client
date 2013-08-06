<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetWarehouseList.class.php';
require_once ROOT . 'includes/DBLastUpdate.php';

class SoapCall_GetWarehouseList extends PlentySoapCall {

	private $oPlentySoapRequest_GetWarehouseList = null;

	public function __construct() {
		parent::__construct(__CLASS__);
	}

	public function execute() {
		$this -> getLogger() -> debug(__FUNCTION__);

		try {

			$oRequest_GetWarehouseList = new Request_GetWarehouseList();

			$this -> oPlentySoapRequest_GetWarehouseList = $oRequest_GetWarehouseList -> getRequest();

			/*
			 * do soap call
			 */
			$response = $this -> getPlentySoap() -> GetWarehouseList($this -> oPlentySoapRequest_GetWarehouseList);

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

	private function responseInterpretation($oPlentySoapResponse_GetWarehouseList) {
		$this -> getLogger() -> debug(__FUNCTION__ . ' : not implemented yet');
	}

}
?>