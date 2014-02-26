<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';

class SoapCall_SetItemsSuppliers extends PlentySoapCall {

	/**
	 * @var int
	 */
	public static $MAX_SUPPLIERS_PER_PAGES = 50;

	/**
	 * @var SoapCall_SetItemsSuppliers
	 */
	public function __construct() {
		parent::__construct(__CLASS__);
	}

	/**
	 * overrides PlentySoapCall's execute() method
	 *
	 * @return void
	 */
	public function execute() {
		try {

		} catch(Exception $e) {
			$this -> onExceptionAction($e);
		}
	}
}
