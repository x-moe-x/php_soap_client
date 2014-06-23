<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/SKUHelper.php';
require_once 'ApiHelper.class.php';

class ApiAmazon {

	const PRICE_COMPARISON_ACCURACY = 0.001;

	/**
	 * @var string
	 */
	const PRICE_DATA_SELECT_BASIC = 'SELECT
	ItemsBase.ItemID,
	CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
		"0"
	ELSE
		AttributeValueSets.AttributeValueSetID
	END AttributeValueSetID';

	/**
	 * @var string
	 */
	const PRICE_DATA_SELECT_ADVANCED = ',
	CONCAT(
		CASE WHEN (ItemsBase.BundleType = "bundle") THEN
			"[Bundle] "
		WHEN (ItemsBase.BundleType = "bundle_item") THEN
			"[Bundle Artikel] "
		ELSE
			""
		END,
		ItemsBase.Name,
		CASE WHEN (AttributeValueSets.AttributeValueSetID IS NOT null) THEN
			CONCAT(", ", AttributeValueSets.AttributeValueSetName)
		ELSE
			""
		END
	) AS Name,
	ItemsBase.Name AS SortName,
	ItemsBase.ItemNo,
	ItemsBase.Marking1ID,
	PriceUpdate.NewPrice,
	PriceUpdateHistory.WrittenTimeStamp';

	/**
	 * @var string
	 */
	const PRICE_DATA_FROM_BASIC = "\nFROM ItemsBase
LEFT JOIN AttributeValueSets
	ON ItemsBase.ItemID = AttributeValueSets.ItemID\n";

	/**
	 * @var string
	 */
	const PRICE_DATA_FROM_ADVANCED = "LEFT JOIN PriceSets
	ON ItemsBase.ItemID = PriceSets.ItemID
LEFT JOIN PriceUpdate
	ON (PriceSets.ItemID = PriceUpdate.ItemID) AND (PriceSets.PriceID = PriceUpdate.PriceID)
LEFT JOIN PriceUpdateHistory
	ON (PriceSets.ItemID = PriceUpdateHistory.ItemID) AND (PriceSets.PriceID = PriceUpdateHistory.PriceID)\n";

	/**
	 * @var string
	 */
	const PRICE_DATA_WHERE = "WHERE
	ItemsBase.Inactive = 0\n";

	/**
	 * @var int
	 */
	const AMAZON_REFERRER_ID = 4;

	public static function setConfigJSON($key, $value) {
		header('Content-Type: application/json');
		$result = array('success' => false, 'data' => NULL, 'error' => NULL);

		if (is_null($key)) {
			$result['error'] = "No key to set value = '$value' in stock config";
		} else {;
			if (is_null($value)) {
				$result['error'] = "No value for $key in stock config";
			} else {
				try {
					$result['data'] = self::setConfig($key, $value);
					$result['success'] = true;
				} catch(Exception $e) {
					$result['error'] = $e -> getMessage();
				}
			}
		}

		echo json_encode($result);
	}

	public static function setConfig($key, $value) {
		$query = "SELECT `ConfigType` AS `type`, `Active` AS `active`, `ConfigKey` AS `key`, `ConfigValue` AS `value` FROM MetaConfig WHERE `ConfigKey` = '$key' AND `Domain` = 'amazon'";

		ob_start();
		$dbResult = DBQuery::getInstance() -> select($query);
		ob_end_clean();

		// check if key is available
		if ($dbResult -> getNumRows() === 1 && $row = $dbResult -> fetchAssoc()) {
			// ... then check if it is active
			if (intval($row['active']) === 1) {
				// ... ... then set value
				ob_start();
				DBQuery::getInstance() -> update("UPDATE MetaConfig SET `ConfigValue`='$value' WHERE `ConfigKey` = '$key' AND `Domain` = 'amazon'");
				$dbResult = DBQuery::getInstance() -> select($query);
				ob_end_clean();
				if (($updatedRow = $dbResult -> fetchAssoc()) && ($updatedRow['value'] == $value)) {
					return array($key => $value);
				} else {
					throw new RuntimeException("Unable to update key $key, value is still {$updatedRow['value']}");
				}

			} else {
				// ... ... otherwise: error
				throw new RuntimeException("Trying to set inactive key $key");
			}
		} else {
			// ... otherwise: error
			throw new RuntimeException("Key $key unavailable");
		}
	}

	public static function getConfigJSON($key) {
		header('Content-Type: application/json');
		$result = array('success' => false, 'data' => NULL, 'error' => NULL);

		try {
			$result['data'] = self::getConfig($key);
			$result['success'] = true;
		} catch(Exception $e) {
			$result['error'] = $e -> getMessage();
		}
		echo json_encode($result);
	}

	public static function getConfig($key = null) {
		$query = 'SELECT `ConfigType` AS `type`, `ConfigKey` AS `key`, `ConfigValue` AS `value` FROM MetaConfig WHERE `Active` = 1 AND `Domain` = \'amazon\'';
		if (is_null($key)) {
			// getting all active k/v-pairs from amazon config
		} else if (is_array($key)) {
			// getting value of $key from amazon config;
			$query .= ' AND `ConfigKey` IN (' . implode(',', $key) . ')';
		} else {
			// getting value of $key from amazon config;
			$query .= ' AND `ConfigKey` = \'' . $key . '\'';
		}

		ob_start();
		$dbResult = DBQuery::getInstance() -> select($query);
		ob_end_clean();

		$result = array();

		while ($row = $dbResult -> fetchAssoc()) {
			switch ($row['type']) {
				case 'int' :
					$result[$row['key']] = intval($row['value']);
					break;
				case 'float' :
					$result[$row['key']] = floatval($row['value']);
					break;
				default :
					throw new RuntimeException("ConfigType {$row['type']} not allowed");
			}
		}

		return $result;
	}

	public static function getPriceJSON($itemID) {
		header('Content-Type: application/json');
		$result = array('success' => false, 'data' => NULL, 'error' => NULL);

		if (!is_null($itemID) && !is_nan($itemID)) {
			try {
				$setPriceData = self::getPrice($itemID);
				$result['data'] = array('setPrice' => $setPriceData['NewPrice'], 'written' => $setPriceData['Written']);
				$result['success'] = true;
			} catch(Exception $e) {
			}
		} else {
			$result['error'] = 'no item id given or wrong format';
		}

		echo json_encode($result);
	}

	/**
	 * @param int $itemID
	 * @param float $newPrice
	 * @return string JSON-String containing informations on the result of the tried action
	 */
	public static function setPriceJSON($itemID, $newPrice) {
		header('Content-Type: application/json');
		$result = array('success' => false, 'data' => NULL, 'error' => NULL);

		if (!is_null($itemID)) {
			if (!is_null($newPrice) && !is_nan($newPrice)) {
				$newPrice = floatval($newPrice);
				try {
					$result['data'] = self::setPrice($itemID, $newPrice);
					$result['success'] = true;
				} catch(Exception $e) {
					$result['error'] = $e -> getMessage();
				}
			} else {
				$result['error'] = "wrong value newPrice = '$newPrice', either none given or not a number";
			}
		} else {
			$result['error'] = 'no itemID given';
		}

		echo json_encode($result);
	}

	/**
	 * perform the following algorithm:
	 *
	 * 1.	check if there's a price update record
	 * 2.	...	then return (priceUpdateNewPrice, priceUpdateWritten)
	 * 3.	... otherwise return (currentPrice,written)
	 *
	 * @param int $itemID
	 */
	public static function getPrice($itemID) {
		$aAmazonPriceData = self::getAmazonPriceData(1, 10, 'ItemID', 'ASC', $itemID);
		$aPriceChanged = $aAmazonPriceData['rows']["$itemID-0-0"]['PriceChanged'];
		return array('NewPrice' => $aPriceChanged['currentPrice'], 'Written' => $aPriceChanged['written'] == 1);
	}

	/**
	 * perform the following algorithm:
	 *
	 * for given itemID and referrerID = AMAZON_REFERRER_ID:
	 *
	 * 1.	get priceID and priceColumn
	 * 2.	if there's a change pending ...
	 * 3.	... store into price update (itemID,priceID,priceColumn,newPrice)
	 * 4.	... otherwise delete item corresponding to (itemID,priceID,priceColumn)
	 *
	 * @param int $itemID
	 * @param float $newPrice
	 * @return array
	 */
	public static function setPrice($itemID, $newPrice) {
		$aPriceUpdate = array('ItemID' => $itemID, 'PriceID' => -1, 'PriceColumn' => -1, 'NewPrice' => $newPrice);

		// 1. get priceColumn ...
		$aAmazonStaticData = ApiHelper::getSalesOrderReferrer(self::AMAZON_REFERRER_ID);
		$aPriceUpdate['PriceColumn'] = $aAmazonStaticData['PriceColumn'];

		// ... and priceID
		ob_start();
		$priceString = "Price" . ($aPriceUpdate['PriceColumn'] === 0 ? '' : $aPriceUpdate['PriceColumn']);
		$priceSetsDBResult = DBQuery::getInstance() -> select("SELECT PriceID, $priceString AS Price FROM PriceSets WHERE ItemID = $itemID");
		ob_end_clean();

		$isChangePending = false;

		if (($priceSetsDBResult -> getNumRows() === 1) && ($aCurrentPriceSet = $priceSetsDBResult -> fetchAssoc())) {
			$isChangePending = abs($newPrice - $aCurrentPriceSet['Price']) > self::PRICE_COMPARISON_ACCURACY;
			$aPriceUpdate['PriceID'] = $aCurrentPriceSet['PriceID'];
		} else {
			if ($priceSetsDBResult -> getNumRows() === 0) {
				throw new RuntimeException("Item $itemID: no price set found. Does the arcticle exists?");
			} else if ($priceSetsDBResult -> getNumRows() > 1) {
				throw new RuntimeException("Item $itemID: found " . $articleVariantPriceDBResult -> getNumRows() . " price sets, expected exactly one!");
			} else {
				throw new RuntimeException("Item $itemID: unable to fetch associated row");
			}
		}

		// 2. if there's a change pending ...
		if ($isChangePending) {
			// 3. ...	then store the change
			ob_start();
			DBQuery::getInstance() -> insert('INSERT INTO PriceUpdate' . DBUtils::buildInsert($aPriceUpdate) . 'ON DUPLICATE KEY UPDATE' . DBUtils::buildOnDuplicateKeyUpdate($aPriceUpdate));
			ob_end_clean();
		} else {
			// 4. ...	or delete if current new price is a reset to the current one...
			DBQuery::getInstance()->delete("DELETE FROM PriceUpdate WHERE ItemID = {$itemID} AND PriceID = {$aPriceUpdate['PriceID']} AND PriceColumn = {$aPriceUpdate['PriceColumn']}");
		}

		return $aPriceUpdate + array('isChangePending' => $isChangePending);
	}

	public static function getAmazonPriceData($page = 1, $rowsPerPage = 10, $sortByColumn = 'ItemID', $sortOrder = 'ASC', $itemID = null) {
		$data = array('page' => $page, 'total' => null, 'rows' => array());

		ob_start();
		$whereCondition = "";
		if (!is_null($itemID)) {
			$whereCondition = "AND\n\tItemsBase.ItemID = $itemID\n";
		}

		$data['total'] = DBQuery::getInstance() -> select(self::PRICE_DATA_SELECT_BASIC . self::PRICE_DATA_FROM_BASIC . self::PRICE_DATA_WHERE . $whereCondition) -> getNumRows();
		$config = self::getConfig();

		//TODO check for empty values to prevent errors!
		$sort = "ORDER BY $sortByColumn $sortOrder\n";
		$start = (($page - 1) * $rowsPerPage);
		$limit = "LIMIT $start,$rowsPerPage";

		// get associated price id
		$amazonStaticData = ApiHelper::getSalesOrderReferrer(self::AMAZON_REFERRER_ID);
		$amazonPrice = 'Price' . $amazonStaticData['PriceColumn'];
		$amazonPriceSelect = ",\n\tPriceSets.$amazonPrice AS Price,\n\tCASE WHEN (PriceUpdateHistory.OldPrice IS null) THEN\n\t\tPriceSets.$amazonPrice\n\tELSE\n\t\tPriceUpdateHistory.OldPrice\n\tEND OldPrice"; 
		// add price id to select advanced clause
		$query = self::PRICE_DATA_SELECT_BASIC . self::PRICE_DATA_SELECT_ADVANCED . $amazonPriceSelect . self::PRICE_DATA_FROM_BASIC . self::PRICE_DATA_FROM_ADVANCED . self::PRICE_DATA_WHERE . $whereCondition . $sort . $limit;
		$amazonPriceDataDBResult = DBQuery::getInstance() -> select($query);
		ob_end_clean();

		while ($amazonPriceData = $amazonPriceDataDBResult -> fetchAssoc()) {
			$sku = Values2SKU($amazonPriceData['ItemID'], $amazonPriceData['AttributeValueSetID']);
			$isChangePending = !is_null($amazonPriceData['NewPrice']) && (abs($amazonPriceData['NewPrice'] - $amazonPriceData['Price']) > self::PRICE_COMPARISON_ACCURACY);
			$isWrittenTimeValid = !empty($amazonPriceData['WrittenTimeStamp']);
			if ($isWrittenTimeValid){
				$writtenDate = new DateTime('@' . $amazonPriceData['WrittenTimeStamp']);
				$writtenDateToNowDifference = $writtenDate -> diff(new DateTime());
				$currentDays = $writtenDateToNowDifference -> format('%a') + $writtenDateToNowDifference -> format('%h') / 24;
			}

			// @formatter:off		
			$data['rows'][$sku] = array(
				'RowID' => $sku,
				'ItemID' => $amazonPriceData['ItemID'],
				'ItemNo' => $amazonPriceData['ItemNo'],
				'Name' => $amazonPriceData['Name'],
				'Marking1ID' => $amazonPriceData['Marking1ID'],
				'PriceOldCurrent' => array('price' => $amazonPriceData['Price'], 'oldPrice' => $amazonPriceData['OldPrice']),
				'PriceChange' => array(
					'price' => $isChangePending ? $amazonPriceData['NewPrice'] : $amazonPriceData['Price'],
					'isChangePending' => $isChangePending
				),
				'TimeData' => array(
					'writtenTime' => $isWrittenTimeValid ? $writtenDate->format('d.m.Y') : '-',
					'targetDays' => $config['MeasuringTimeFrame'],
					'currentDays' => $isWrittenTimeValid ? number_format($currentDays, 1) : '-'
				)
			);
			 // @formatter:on
		}
		return $data;
	}

}
?>