<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once 'ApiHelper.class.php';

class ApiAmazon {
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
	ItemsBase.Marking1ID';

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
	ON ItemsBase.ItemID = PriceSets.ItemID\n";

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

	public static function getAmazonPriceData($page = 1, $rowsPerPage = 10, $sortByColumn = 'ItemID', $sortOrder = 'ASC') {
		$data = array('page' => $page, 'total' => null, 'rows' => array());

		ob_start();

		$data['total'] = DBQuery::getInstance() -> select(self::PRICE_DATA_SELECT_BASIC . self::PRICE_DATA_FROM_BASIC . self::PRICE_DATA_WHERE) -> getNumRows();

		//TODO check for empty values to prevent errors!
		$sort = "ORDER BY $sortByColumn $sortOrder\n";
		$start = (($page - 1) * $rowsPerPage);
		$limit = "LIMIT $start,$rowsPerPage";

		// get associated price id
		$amazonStaticData = ApiHelper::getSalesOrderReferrer(self::AMAZON_REFERRER_ID);
		$amazonPrice = 'Price' . $amazonStaticData['PriceColumn'];
		// add price id to select advanced clause
		$query = self::PRICE_DATA_SELECT_BASIC . self::PRICE_DATA_SELECT_ADVANCED . ",\n\t$amazonPrice AS Price" . self::PRICE_DATA_FROM_BASIC . self::PRICE_DATA_FROM_ADVANCED . self::PRICE_DATA_WHERE . $sort . $limit;
		$amazonPriceDataDBResult = DBQuery::getInstance() -> select($query);
		ob_end_clean();

		while ($amazonPriceData = $amazonPriceDataDBResult -> fetchAssoc()) {
			// @formatter:off		
			$data['rows'][] = array(
				'RowID' => $amazonPriceData['ItemID'] . '-0-' . $amazonPriceData['AttributeValueSetID'],
				'ItemID' => $amazonPriceData['ItemID'],
				'ItemNo' => $amazonPriceData['ItemNo'],
				'Name' => $amazonPriceData['Name'],
				'Marking1ID' => $amazonPriceData['Marking1ID'],
				'Price' => array('currentPrice' => $amazonPriceData['Price'], 'oldPrice' => 'XXX')
			);
			 // @formatter:on
		}
		return $data;
	}

}
?>