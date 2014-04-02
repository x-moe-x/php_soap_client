<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetItemsPriceLists.class.php';

class SoapCall_GetItemsPriceLists extends PlentySoapCall {

	/**
	 * @var int
	 */
	 const MAX_PRICE_SETS_PER_PAGE = 200;

	/**
	 * @return SoapCall_GetItemsPriceLists
	 */
	public function __construct() {
		parent::__construct(__CLASS__);
	}

	/**
	 * @return void
	 */
	public function execute() {
		try {

			$oRequest_GetItemsPriceLists = new Request_GetItemsPriceLists();

			/*
			 * do soap call
			 */
			$response = $this -> getPlentySoap() -> GetItemsPriceLists($oRequest_GetItemsPriceLists -> getRequest());

			if ($response -> Success == true) {
				// request successful, processing data..
				$this -> getLogger() -> debug(__FUNCTION__ . ' Request Success');
			} else {
				$this -> getLogger() -> debug(__FUNCTION__ . ' Request Error');
			}
		} catch(Exception $e) {
			$this -> onExceptionAction($e);
		}
	}

}
?>