<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetItemsSuppliers.class.php';

class SoapCall_GetItemsSuppliers extends PlentySoapCall {

	/**
	 *
	 * @var Request_GetItemsSuppliers
	 */
	private $oPlentySoapRequest_GetItemsSuppliers;

	public function __construct() {
		parent::__construct(__CLASS__);
	}

	public function execute() {
		try {
			$oRequest_GetItemsSuppliers = new Request_GetItemsSuppliers();

			$this -> oPlentySoapRequest_GetItemsSuppliers = $oRequest_GetItemsSuppliers -> getRequest();

			/*
			 * do soap call
			 */
			$response = $this -> getPlentySoap() -> GetItemsSuppliers($this -> oPlentySoapRequest_GetItemsSuppliers);

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

	/**
	 * @param PlentySoapResponse_GetItemsSuppliers $response
	 */
	private function responseInterpretation(PlentySoapResponse_GetItemsSuppliers $response) {
		print_r($response);
	}

}
