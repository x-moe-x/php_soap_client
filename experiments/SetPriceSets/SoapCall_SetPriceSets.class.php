<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once ROOT . 'api/ApiHelper.class.php';
require_once ROOT . 'includes/DBUtils2.class.php';
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
		$this -> getLogger() -> debug(__FUNCTION__ . ' writing price updates ...');
		try {
			// get all unwritten updates
			$unwrittenUpdatesDBResult = DBQuery::getInstance() -> select($this -> getQuery());

			// for every 100 updates ...
			for ($page = 0, $maxPage = ceil($unwrittenUpdatesDBResult -> getNumRows() / self::MAX_PRICE_SETS_PER_PAGE); $page < $maxPage; $page++) {

				// prepare a separate request
				$oRequest_SetPriceSets = new Request_SetPriceSets();

				// fill in data
				$currentReferrer = -1;
				$currentPrice = '';
				$aUpdateWrittenMarkings = array();
				while (!$oRequest_SetPriceSets -> isFull() && ($aUnwrittenUpdate = $unwrittenUpdatesDBResult -> fetchAssoc())) {
					if ($aUnwrittenUpdate['ReferrerID'] !== $currentReferrer) {
						$salesOrderReferrerData = ApiHelper::getSalesOrderReferrer($aUnwrittenUpdate['ReferrerID']);
						$currentReferrer = $salesOrderReferrerData['SalesOrderReferrerID'];
						$currentPrice = 'Price' . ($salesOrderReferrerData['PriceColumn'] == 0 ? '' : $salesOrderReferrerData['PriceColumn']);
					}
					$oRequest_SetPriceSets -> addPriceSet(array('PriceSetID' => $aUnwrittenUpdate['PriceID'], $currentPrice => $aUnwrittenUpdate['NewPrice']));
					$aUpdateWrittenMarkings[] = array('PriceId' => $aUnwrittenUpdate['PriceID'], 'ItemID' => $aUnwrittenUpdate['ItemID'], 'Written' => 1);
				}

				// do soap call
				$oPlentySoapResponse_SetPriceSets = $this -> getPlentySoap() -> SetPriceSets($oRequest_SetPriceSets -> getRequest());

				// ... if successful ...
				if ($oPlentySoapResponse_SetPriceSets -> Success == true) {
					// TODO include current timestamp

					// mark updates as written
					DBQuery::getInstance() -> insert('INSERT INTO PriceUpdate' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($aUpdateWrittenMarkings));
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
		return "SELECT
	*
FROM
	PriceUpdate
WHERE
	Written = 0
ORDER BY
	Written ASC";
	}

}
