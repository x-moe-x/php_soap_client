<?php

class ApiHelper {
	public static function getMonthDates(DateTime $fromDate, $nrOfMonthDates = 6, $omitCurrentMonth = false) {
		$result = array();
		$normalizedDate = new DateTime($fromDate -> format('Ym01'));
		if (!$omitCurrentMonth) {
			$result[] = $normalizedDate -> format('Ymd');
		}
		for ($i = 1; $i <= $nrOfMonthDates; $i++) {
			$result[] = $normalizedDate -> sub(new DateInterval('P1M')) -> format('Ymd');
		}
		return array_reverse($result);
	}

	public static function getWarehouseList() {
		ob_start();
		$resultWarehouseList = DBQuery::getInstance() -> select('SELECT * FROM `WarehouseList` ORDER BY WarehouseID ASC');
		ob_end_clean();

		$result = array();
		while ($warehouse = $resultWarehouseList -> fetchAssoc()) {
			$result[$warehouse['WarehouseID']] = array('id' => $warehouse['WarehouseID'], 'name' => $warehouse['Name']);
		}
		return $result;
	}

	/**
	 * @param int $referrerID
	 * @return array
	 */
	public static function getSalesOrderReferrer($referrerID) {
		ob_start();
		$resultSalesOrderReferrer = DBQuery::getInstance() -> select("SELECT * FROM SalesOrderReferrer WHERE `SalesOrderReferrerID` = $referrerID");
		ob_end_clean();

		if (($resultSalesOrderReferrer -> getNumRows() === 1) && $row = $resultSalesOrderReferrer -> fetchAssoc()) {
			return $row;
		} else {
			throw new RuntimeException("Trying to retrieve sales order referrer record for invalid referrer id $referrerID");
		}

	}

	/**
	 * @param int $itemID
	 * @param int $referrerID
	 * @return float current price
	 */
	public static function getCurrentPriceDataByReferrer($itemID, $referrerID) {
		// get associated price column
		$aAmazonStaticData = self::getSalesOrderReferrer($referrerID);
		$priceString = 'Price' . ($aAmazonStaticData['PriceColumn'] === 0 ? '' : $aAmazonStaticData['PriceColumn']);

		// get associated price
		ob_start();
		$articleVariantPriceDBResult = DBQuery::getInstance() -> select("SELECT $priceString AS Price, PriceID FROM PriceSets WHERE ItemID = $itemID");
		ob_end_clean();
		if ($articleVariantPriceDBResult -> getNumRows() === 0) {
			throw new RuntimeException("Item $itemID: no price set found. Does the arcticle exists?");
		} else if ($articleVariantPriceDBResult -> getNumRows() > 1) {
			throw new RuntimeException("Item $itemID: found " . $articleVariantPriceDBResult -> getNumRows() . " price sets, expected exactly one!");
		} else {
			if ($current = $articleVariantPriceDBResult -> fetchAssoc()) {
				return array($current['Price'], $current['PriceID']);
			} else {
				throw new RuntimeException("Item $itemID: unable to fetch associated row");
			}
		}
	}

}
?>