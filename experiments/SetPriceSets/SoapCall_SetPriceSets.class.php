<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_SetPriceSets.class.php';

class SoapCall_SetPriceSets extends PlentySoapCall {

	/**
	 * @var int
	 */
	const MAX_PRICE_SETS_PER_PAGE = 100;

	/**
	 * @var string
	 */
	private $identifier4Logger;

	public function __construct() {
		$this -> identifier4Logger = __CLASS__;
	}

	/**
	 * overrides PlentySoapCall's execute() method
	 *
	 * @return void
	 */
	public function execute() {

	}

}
