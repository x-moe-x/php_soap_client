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
	 PriceUpdate.ItemID,
	 PriceUpdate.PriceID,
	 PriceUpdate.PriceColumn,
	 PriceUpdate.NewPrice,
	 CASE PriceUpdate.PriceColumn
		WHEN 0 THEN PriceSets.Price
		WHEN 1 THEN PriceSets.Price1
		WHEN 2 THEN PriceSets.Price2
		WHEN 3 THEN PriceSets.Price3
		WHEN 4 THEN PriceSets.Price4
		WHEN 5 THEN PriceSets.Price5
		WHEN 6 THEN PriceSets.Price6
		WHEN 7 THEN PriceSets.Price7
		WHEN 8 THEN PriceSets.Price8
		WHEN 9 THEN PriceSets.Price9
		WHEN 10 THEN PriceSets.Price10
		WHEN 11 THEN PriceSets.Price11
		WHEN 12 THEN PriceSets.Price12
	 END AS OldPrice
FROM
	PriceUpdate
LEFT JOIN
	PriceSets
ON
	(PriceUpdate.ItemID = PriceSets.ItemID)
AND
	(PriceUpdate.PriceID = PriceSets.PriceID)
";
	}

}
