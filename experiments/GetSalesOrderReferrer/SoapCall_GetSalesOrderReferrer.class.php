<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once ROOT . 'includes/DBUtils2.class.php';

class SoapCall_GetSalesOrderReferrer extends PlentySoapCall {

	/**
	 * @var array
	 */
	private $aProcessedSalesOrderReferrer;

	/**
	 * @return SoapCall_GetSalesOrderReferrer
	 */
	public function __construct() {
		parent::__construct(__CLASS__);

		$this -> aProcessedSalesOrderReferrer = array();

		// truncate table to prevent old leftovers
		DBQuery::getInstance() -> truncate('TRUNCATE TABLE `SalesOrderReferrer`');
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
				$this -> storeToDB();
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

	/**
	 * @return void
	 */
	private function storeToDB() {
		$this -> getLogger() -> debug(__FUNCTION__ . ' storing ' . count($this -> aProcessedSalesOrderReferrer) . ' SalesOrderReferrer to db');
		DBQuery::getInstance() -> insert('INSERT INTO `SalesOrderReferrer`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this -> aProcessedSalesOrderReferrer));
	}

}
