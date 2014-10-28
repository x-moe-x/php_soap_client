<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_SetCurrentStocks.class.php';

class SoapCall_SetCurrentStocks extends PlentySoapCall {

	/**
	 * @var string
	 */
	private $identifier4Logger;

	public function __construct() {
		$this -> identifier4Logger = __CLASS__;
	}

	/**
	 * @return void
	 */
	public function execute() {
	}

}
