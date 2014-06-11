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
	CASE WHEN (PriceUpdate.Written IS NOT null) THEN
		PriceUpdate.Written
	ELSE
		"1"
	END AS Written';

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
	ON (PriceSets.ItemID = PriceUpdate.ItemID) AND (PriceSets.PriceID = PriceUpdate.PriceID)\n";

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

	public static function setPriceJSON($sku, $newPrice) {
		header('Content-Type: application/json');
		$result = array('success' => false, 'data' => NULL, 'error' => NULL);

		if (!is_null($sku)) {
			if (!is_null($newPrice) && !is_nan($newPrice)) {
				$newPrice = floatval($newPrice);
				if (preg_match('/\d+-\d+-\d+/', $sku) == 1) {
					try {
						$setPriceData = self::setPrice($sku, $newPrice);
						$result['data'] = array('setPrice' => $setPriceData['NewPrice'], 'written' => $setPriceData['Written']);
						$result['success'] = true;
					} catch(Exception $e) {
						$result['error'] = $e -> getMessage();
					}
				} else {
					$result['error'] = 'invalid SKU format';
				}
			} else {
				$result['error'] = "wrong value newPrice = '$newPrice', either none given or not a number";
			}
		} else {
			$result['error'] = 'no sku given';
		}

		echo json_encode($result);
	}

	/**
	 * perform the following algorithm:
	 *
	 * for given sku and referrerID = AMAZON_REFERRER_ID:
	 *
	 * 1.	get currentPrice
	 * 2.	if currentPrice != newPrice
	 * 3.	... then insert into db the following record:
	 * 		...	...	(itemID,priceID,referrerID,currentPrice,newPrice,!written)
	 *		...	...	without changing any possibly existing timestamp
	 * 4.	... otherwise insert into db the following record:
	 * 		...	... (itemID,priceID,referrerID,currentPrice,currentPrice,written)
	 *		...	...	without changing any possibly existing timestamp
	 *
	 * timestamp is only updated on successful writeback operation to plenty
	 *
	 * @param string $sku
	 * @param float $newPrice
	 * @return array price data (NewPrice, Written)
	 */
	public static function setPrice($sku, $newPrice) {
		// 1. get current price
		list($itemID, , ) = SKU2Values($sku);
		list($currentPrice, $priceID) = ApiHelper::getCurrentPriceDataByReferrer($itemID, self::AMAZON_REFERRER_ID);

		$aPriceUpdate = array('ItemID' => $itemID, 'PriceID' => $priceID, 'OldPrice' => $currentPrice, 'ReferrerID' => self::AMAZON_REFERRER_ID, 'NewPrice' => null, 'Written' => null);

		// 2. if currentPrice != newPrice
		if (abs($currentPrice - $newPrice) > self::PRICE_COMPARISON_ACCURACY) {
			// 3. ... then prepare (sku,referrerID,currentPrice,newPrice,!written)
			$aPriceUpdate['NewPrice'] = $newPrice;
			$aPriceUpdate['Written'] = 0;
		} else {
			// 4.	... otherwise prepare (sku,referrerID,currentPrice,currentPrice,written)
			$aPriceUpdate['NewPrice'] = $currentPrice;
			$aPriceUpdate['Written'] = 1;
		}

		// and insert to db...
		ob_start();
		DBQuery::getInstance() -> insert('INSERT INTO PriceUpdate' . DBUtils::buildInsert($aPriceUpdate) . 'ON DUPLICATE KEY UPDATE' . DBUtils::buildOnDuplicateKeyUpdate($aPriceUpdate));
		ob_end_clean();

		return array('NewPrice' => $aPriceUpdate['NewPrice'], 'Written' => $aPriceUpdate['Written'] == 1);
	}

	public static function getAmazonPriceData($page = 1, $rowsPerPage = 10, $sortByColumn = 'ItemID', $sortOrder = 'ASC', $itemID = null) {
		$data = array('page' => $page, 'total' => null, 'rows' => array());

		ob_start();
		$whereCondition = "";
		if (!is_null($itemID)) {
			$whereCondition = "AND\n\tItemsBase.ItemID = $itemID\n";
		}

		$data['total'] = DBQuery::getInstance() -> select(self::PRICE_DATA_SELECT_BASIC . self::PRICE_DATA_FROM_BASIC . self::PRICE_DATA_WHERE . $whereCondition) -> getNumRows();

		//TODO check for empty values to prevent errors!
		$sort = "ORDER BY $sortByColumn $sortOrder\n";
		$start = (($page - 1) * $rowsPerPage);
		$limit = "LIMIT $start,$rowsPerPage";

		// get associated price id
		$amazonStaticData = ApiHelper::getSalesOrderReferrer(self::AMAZON_REFERRER_ID);
		$amazonPrice = 'Price' . $amazonStaticData['PriceColumn'];
		// add price id to select advanced clause
		$query = self::PRICE_DATA_SELECT_BASIC . self::PRICE_DATA_SELECT_ADVANCED . ",\n\t$amazonPrice AS Price" . self::PRICE_DATA_FROM_BASIC . self::PRICE_DATA_FROM_ADVANCED . self::PRICE_DATA_WHERE . $whereCondition . $sort . $limit;
		$amazonPriceDataDBResult = DBQuery::getInstance() -> select($query);
		ob_end_clean();

		while ($amazonPriceData = $amazonPriceDataDBResult -> fetchAssoc()) {
			$sku = $amazonPriceData['ItemID'] . '-0-' . $amazonPriceData['AttributeValueSetID'];

			// @formatter:off		
			$data['rows'][$sku] = array(
				'RowID' => $sku,
				'ItemID' => $amazonPriceData['ItemID'],
				'ItemNo' => $amazonPriceData['ItemNo'],
				'Name' => $amazonPriceData['Name'],
				'Marking1ID' => $amazonPriceData['Marking1ID'],
				'PriceOldCurrent' => array('currentPrice' => $amazonPriceData['Price'], 'oldPrice' => 'XXX'),
				'PriceChanged' => array('currentPrice' => ((bool) $amazonPriceData['Written'] ? $amazonPriceData['Price'] : $amazonPriceData['NewPrice']), 'written' => (bool) $amazonPriceData['Written'] )
			);
			 // @formatter:on
		}
		return $data;
	}

}
?>