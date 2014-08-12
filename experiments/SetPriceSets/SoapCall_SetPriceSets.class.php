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

	/**
	 * @var int
	 */
	private $currentTimeStamp;

	public function __construct() {
		$this -> identifier4Logger = __CLASS__;

		$this -> currentTimeStamp = time();
	}

	/**
	 * overrides PlentySoapCall's execute() method
	 *
	 * 1. get all price updates
	 * 2. for every 100 updates ...
	 * 3. ...	write them back via soap
	 * 4. ...	on success ...
	 * 5. ...	...	mark them as updated now in priceUpdateHistory ...
	 *    ...	...	and update corresponding price set
	 *    ...	...	and delete specified elements from priceUpdate ...
	 *
	 * @return void
	 */
	public function execute() {
		$this -> getLogger() -> debug(__FUNCTION__ . ' writing price updates ...');
		try {
			// 1. get all price updates
			$unwrittenUpdatesDBResult = DBQuery::getInstance() -> select($this -> getQuery());

			// 2. for every 100 updates ...
			for ($page = 0, $maxPage = ceil($unwrittenUpdatesDBResult -> getNumRows() / self::MAX_PRICE_SETS_PER_PAGE); $page < $maxPage; $page++) {

				// ... prepare a separate request ...
				$oRequest_SetPriceSets = new Request_SetPriceSets();

				// ... fill in data
				$aPriceUpdateHistoryEntries = array();
				$aPriceSetsEntries = array();
				while (!$oRequest_SetPriceSets -> isFull() && ($aUnwrittenUpdate = $unwrittenUpdatesDBResult -> fetchAssoc())) {

					$currentPriceName = 'Price' . ($aUnwrittenUpdate['PriceColumn'] == 0 ? '' : $aUnwrittenUpdate['PriceColumn']);

					$oRequest_SetPriceSets -> addPriceSet(array('PriceSetID' => $aUnwrittenUpdate['PriceID'], $currentPriceName => $aUnwrittenUpdate['NewPrice'] * (1 + $aUnwrittenUpdate['VAT'] / 100)));

					// @formatter:off
					$aPriceUpdateHistoryEntries[] = array(
						'ItemID' =>				$aUnwrittenUpdate['ItemID'],
						'PriceID' =>			$aUnwrittenUpdate['PriceID'],
						'PriceColumn' =>		$aUnwrittenUpdate['PriceColumn'],
						'OldPrice' =>			$aUnwrittenUpdate['OldPrice'],
						'WrittenTimestamp' =>	$this->currentTimeStamp
					);

					$aPriceSetsEntries[] = array(
						'ItemID' =>				$aUnwrittenUpdate['ItemID'],
						'PriceID' =>			$aUnwrittenUpdate['PriceID'],
						$currentPriceName =>	$aUnwrittenUpdate['NewPrice'] * (1 + $aUnwrittenUpdate['VAT'] / 100)
					);
					// @formatter:on

				}

				// 3. write them back via soap
				$oPlentySoapResponse_SetPriceSets = $this -> getPlentySoap() -> SetPriceSets($oRequest_SetPriceSets -> getRequest());

				// 4. if successful ...
				if ($oPlentySoapResponse_SetPriceSets -> Success == true) {
					// 5. mark them as updated now in priceUpdateHistory ...
					DBQuery::getInstance() -> insert('INSERT INTO PriceUpdateHistory' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($aPriceUpdateHistoryEntries));

					// ... and update corresponding price set
					DBQuery::getInstance() -> insert('INSERT INTO PriceSets' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($aPriceSetsEntries));

					// ... and delete specified elements from priceUpdate
					DBQuery::getInstance() -> delete('DELETE FROM PriceUpdate WHERE PriceID IN (' . implode(',', array_map(function($current) {
						return $current['PriceID'];
					}, $aPriceUpdateHistoryEntries)) . ')');

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
	 PriceSets.VAT,
	 CASE PriceUpdate.PriceColumn
		WHEN 0 THEN PriceSets.Price / (1 + PriceSets.VAT / 100)
		WHEN 1 THEN PriceSets.Price1 / (1 + PriceSets.VAT / 100)
		WHEN 2 THEN PriceSets.Price2 / (1 + PriceSets.VAT / 100)
		WHEN 3 THEN PriceSets.Price3 / (1 + PriceSets.VAT / 100)
		WHEN 4 THEN PriceSets.Price4 / (1 + PriceSets.VAT / 100)
		WHEN 5 THEN PriceSets.Price5 / (1 + PriceSets.VAT / 100)
		WHEN 6 THEN PriceSets.Price6 / (1 + PriceSets.VAT / 100)
		WHEN 7 THEN PriceSets.Price7 / (1 + PriceSets.VAT / 100)
		WHEN 8 THEN PriceSets.Price8 / (1 + PriceSets.VAT / 100)
		WHEN 9 THEN PriceSets.Price9 / (1 + PriceSets.VAT / 100)
		WHEN 10 THEN PriceSets.Price10 / (1 + PriceSets.VAT / 100)
		WHEN 11 THEN PriceSets.Price11 / (1 + PriceSets.VAT / 100)
		WHEN 12 THEN PriceSets.Price12 / (1 + PriceSets.VAT / 100)
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
