<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once ROOT . 'includes/SKUHelper.php';
require_once 'Request_SetCurrentStocks.class.php';

class SoapCall_SetCurrentStocks extends PlentySoapCall {

	/**
	 * @var int
	 */
	const MAX_STOCK_RECORDS_PER_PAGE = 100;

	/**
	 * @var boolean
	 */
	const DISABLE_WAREHOUSE_1_UPDATE = true;

	/**
	 * @var string
	 */
	private $identifier4Logger;

	public function __construct() {
		$this -> identifier4Logger = __CLASS__;

		$this -> aStockUpdateEntries = array();
	}

	/**
	 * @return void
	 */
	public function execute() {
		$this -> getLogger() -> debug(__FUNCTION__ . ' writing stock updates ...');
		try {
			// 1. get all stock updates
			$unwrittenUpdatesDBResult = DBQuery::getInstance() -> select($this -> getQuery());

			// 2. for every 100 updates ...
			for ($page = 0, $maxPage = ceil($unwrittenUpdatesDBResult -> getNumRows() / self::MAX_STOCK_RECORDS_PER_PAGE); $page < $maxPage; $page++) {

				// ... prepare a separate request ...
				$oRequest_SetCurrentStocks = new Request_SetCurrentStocks();

				// ... fill in data
				$aWrittenUpdates = array();
				while (!$oRequest_SetCurrentStocks -> isFull() && ($aUnwrittenUpdate = $unwrittenUpdatesDBResult -> fetchAssoc())) {

					// @formatter:off
					$oRequest_SetCurrentStocks -> addStock(array(
						'SKU' =>				Values2SKU($aUnwrittenUpdate['ItemID'], $aUnwrittenUpdate['AttributeValueSetID'], $aUnwrittenUpdate['PriceID']),
						'WarehouseID' =>		$aUnwrittenUpdate['WarehouseID'],
						'StorageLocation' =>	$aUnwrittenUpdate['StorageLocation'],
						'PhysicalStock' =>		$aUnwrittenUpdate['PhysicalStock'],
						'Reason' =>				$aUnwrittenUpdate['Reason']
					));
					// @formatter:on

					$aWrittenUpdates[] = '(' . $aUnwrittenUpdate['ItemID'] . ',' . $aUnwrittenUpdate['AttributeValueSetID'] . ',' . $aUnwrittenUpdate['PriceID'] . ')';
				}

				// 3. write them back via soap
				$oPlentySoapResponse_SetCurrentStocks = $this -> getPlentySoap() -> SetCurrentStocks($oRequest_SetCurrentStocks -> getRequest());

				// 4. if successful ...
				if ($oPlentySoapResponse_SetCurrentStocks -> Success == true) {
					// ... then delete specified elements from setCurrentStocks
					DBQuery::getInstance() -> delete('DELETE FROM SetCurrentStocks WHERE (ItemID, AttributeValueSetID, PriceID) IN (' . implode(',', $aWrittenUpdates) . ')');
				} else {
					// ... otherwise log error and try next request
					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Error');
				}
			}
		} catch(Exception $e) {
			$this -> onExceptionAction($e);
		}
	}

	private function getQuery() {
		return "SELECT * FROM SetCurrentStocks" . (self::DISABLE_WAREHOUSE_1_UPDATE ? ' WHERE WarehouseID != 1' : '');
	}

}
