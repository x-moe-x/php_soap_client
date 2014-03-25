<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';

class SoapCall_GetSalesOrderReferrer extends PlentySoapCall {

	/**
	 * @var array
	 */
	private $aProcessedSalesOrderReferrer;

	public function __construct() {
		parent::__construct(__CLASS__);
		$this -> aProcessedSalesOrderReferrer = array();
	}

	/**
	 * overrides PlentySoapCall's execute() method
	 *
	 * @return void
	 */
	public function execute() {
		try {
			/*
			 * do soap call
			 */
			$oPlentySoapResponse_GetSalesOrderReferrer = $this -> getPlentySoap() -> GetSalesOrderReferrer();

			if ($oPlentySoapResponse_GetSalesOrderReferrer -> Success == true) {
				$this -> responseInterpretation($oPlentySoapResponse_GetSalesOrderReferrer);
				$this->storeToDB();
			} else {
				$this -> getLogger() -> debug(__FUNCTION__ . ' Request Error');
			}

		} catch(Exception $e) {
			$this -> onExceptionAction($e);
		}
	}

	/**
	 * @param PlentySoapResponse_GetSalesOrderReferrer $oPlentySoapResponse_GetSalesOrderReferrer
	 * @return void
	 */
	private function responseInterpretation(PlentySoapResponse_GetSalesOrderReferrer $oPlentySoapResponse_GetSalesOrderReferrer) {
		if (is_array($oPlentySoapResponse_GetSalesOrderReferrer -> SalesOrderReferrers -> item)) {
			foreach ($oPlentySoapResponse_GetSalesOrderReferrer -> SalesOrderReferrers -> item as $oPlentySoapObject_GetSalesOrderReferrer) {
				$this -> processSalesOrderReferrer($oPlentySoapObject_GetSalesOrderReferrer);
			}
		} else {
			$this -> processSalesOrderReferrer($oPlentySoapResponse_GetSalesOrderReferrer -> SalesOrderReferrers -> item);
		}
	}

	/**
	 * @param PlentySoapObject_GetSalesOrderReferrer $oSalesOrderReferrer
	 * @return void
	 */
	private function processSalesOrderReferrer($oSalesOrderReferrer) {
		// prepare SalesOrderReferrer for persistent storage

		// @formatter:off
		$this -> aProcessedSalesOrderReferrer[] = array(
			'Name' => $oSalesOrderReferrer->Name,
			'PriceColumn' => $oSalesOrderReferrer->PriceColumn,
			'SalesOrderReferrerID' => $oSalesOrderReferrer->SalesOrderReferrerID
		);
		// @formatter:on
	}

	private function storeToDB() {
		print_r($this -> aProcessedSalesOrderReferrer);
	}

}
