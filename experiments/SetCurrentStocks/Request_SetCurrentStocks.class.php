<?php

require_once ROOT . 'includes/FillObjectFromArray.php';

class Request_SetCurrentStocks {

	/**
	 * @var array
	 */
	private $aStockRecords;

	/**
	 * @return Request_SetCurrentStocks
	 */
	public function __construct() {
		$this -> aStockRecords = array();
	}

	/**
	 * @param array $aStock
	 * @return void
	 */
	public function addStock(array $aStock) {
		if (count($this -> aStockRecords) < SoapCall_SetCurrentStocks::MAX_STOCK_RECORDS_PER_PAGE) {
			$this -> aStockRecords[] = $aStock;
		}
	}

	/**
	 * @return boolean
	 */
	public function isFull() {
		return count($this -> aStockRecords) === SoapCall_SetCurrentStocks::MAX_STOCK_RECORDS_PER_PAGE;
	}

	/**
	 * @return PlentySoapRequest_SetCurrentStocks
	 */
	public function getRequest() {
		$oPlentySoapRequest_SetCurrentStocks = new PlentySoapRequest_SetCurrentStocks();
		$oPlentySoapRequest_SetCurrentStocks -> CurrentStocks = array();

		foreach ($this->aStockRecords as &$aStock) {/* @var $aStock array */

			$oPlentySoapObject_SetCurrentStocks = new PlentySoapObject_SetCurrentStocks();

			fillObjectFromArray($oPlentySoapObject_SetCurrentStocks, $aStock);

			$oPlentySoapRequest_SetCurrentStocks -> CurrentStocks[] = $oPlentySoapObject_SetCurrentStocks;
		}

		return $oPlentySoapRequest_SetCurrentStocks;
	}

}
?>