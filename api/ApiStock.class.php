<?php
require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/SKUHelper.php';

class ApiStock {

	/**
	 * @var string
	 */
	const STOCK_DATA_SELECT_BASIC = 'SELECT
    ItemsBase.ItemID,
	CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
		"0"
	ELSE
		AttributeValueSets.AttributeValueSetID
	END AttributeValueSetID';

	const STOCK_DATA_SELECT_ADVANCED = ',
	CONCAT(CASE WHEN (CalculatedDailyNeeds.New = 1) THEN
			"[Neu] "
		ELSE
			""
		END,CASE WHEN (ItemsBase.BundleType = "bundle") THEN
			"[Bundle] "
		WHEN (ItemsBase.BundleType = "bundle_item") THEN
			"[Bundle Artikel] "
		ELSE
			""
		END, ItemsBase.Name, CASE WHEN (AttributeValueSets.AttributeValueSetID IS NOT null) THEN
			CONCAT(", ", AttributeValueSets.AttributeValueSetName)
		ELSE
			""
	END) AS Name,
	ItemsBase.Name AS SortName,
	ItemsBase.ItemNo,
	ItemsBase.Marking1ID,
	ItemsBase.Free4 AS VPE,
	ItemsBase.BundleType,
	CalculatedDailyNeeds.DailyNeed,
	CalculatedDailyNeeds.LastUpdate,
	CalculatedDailyNeeds.QuantitiesA,
	CalculatedDailyNeeds.SkippedA,
	CalculatedDailyNeeds.QuantitiesB,
	CalculatedDailyNeeds.SkippedB,
	CalculatedDailyNeeds.New,
	ItemsWarehouseSettings.ReorderLevel,
	ItemsWarehouseSettings.StockTurnover,
	ItemsWarehouseSettings.MaximumStock,
	ItemSuppliers.SupplierDeliveryTime,
	ItemSuppliers.SupplierMinimumPurchase,
	WritePermissions.WritePermission,
	WritePermissions.Error AS WritePermissionError,
	WriteBackSuggestion.Valid,
    WriteBackSuggestion.ReorderLevelError,
    WriteBackSuggestion.SupplierMinimumPurchaseError,
    WriteBackSuggestion.ReorderLevel AS ProposedReorderLevel,
    WriteBackSuggestion.SupplierMinimumPurchase AS ProposedSupplierMinimumPurchase,
    WriteBackSuggestion.MaximumStock  AS ProposedMaximumStock,
    CurrentStocks.NetStock';

	const STOCK_DATA_FROM_BASIC = '
FROM ItemsBase
LEFT JOIN AttributeValueSets
	ON ItemsBase.ItemID = AttributeValueSets.ItemID
LEFT JOIN ItemsWarehouseSettings
    ON ItemsBase.ItemID = ItemsWarehouseSettings.ItemID
    AND CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
        "0"
    ELSE
        AttributeValueSets.AttributeValueSetID
    END = ItemsWarehouseSettings.AttributeValueSetID';

	const STOCK_DATA_FROM_ADVANCED = '
LEFT JOIN CalculatedDailyNeeds
    ON ItemsBase.ItemID = CalculatedDailyNeeds.ItemID
    AND CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
        "0"
    ELSE
        AttributeValueSets.AttributeValueSetID
    END = CalculatedDailyNeeds.AttributeValueSetID
LEFT JOIN ItemSuppliers
	ON ItemsBase.ItemID = ItemSuppliers.ItemID
LEFT JOIN WritePermissions
    ON ItemsBase.ItemID = WritePermissions.ItemID
    AND CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
        "0"
    ELSE
        AttributeValueSets.AttributeValueSetID
    END = WritePermissions.AttributeValueSetID
LEFT JOIN WriteBackSuggestion
    ON ItemsBase.ItemID = WriteBackSuggestion.ItemID
    AND CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
        "0"
    ELSE
        AttributeValueSets.AttributeValueSetID
    END = WriteBackSuggestion.AttributeValueSetID
LEFT JOIN CurrentStocks
	ON ItemsBase.ItemID = CurrentStocks.ItemID
	AND CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
        "0"
    ELSE
        AttributeValueSets.AttributeValueSetID
    END = CurrentStocks.AttributeValueSetID
    AND CurrentStocks.WarehouseID = 1';

	const STOCK_DATA_WHERE = '
WHERE
	ItemsBase.Inactive = 0';

	/**
	 * @param int $page
	 * @param int $rowsPerPage
	 * @param string $sortByColumn
	 * @param string $sortOrder
	 * @param mixed $aItemIDs
	 * @param mixed $aItemNos
	 * @param mixed $aItemNames
	 * @param mixed $aMarking1IDs
	 * @return array
	 */
	public static function getStockData($page = 1, $rowsPerPage = 10, $sortByColumn = 'ItemID', $sortOrder = 'ASC', $aItemIDs = null, $aItemNos = null, $aItemNames = null, $aMarking1IDs = null) {
		$aStockData = array('page' => $page, 'total' => null, 'rows' => array());

		// prepare where condition
		$filterCondition = self::prepareStockDataFilterCondition($aItemIDs, $aItemNos, $aItemNames, $aMarking1IDs);

		ob_start();
		// get total
		$aStockData['total'] = DBQuery::getInstance() -> select(self::STOCK_DATA_SELECT_BASIC . self::STOCK_DATA_FROM_BASIC . self::STOCK_DATA_WHERE . $filterCondition) -> getNumRows();

		// prepare current page
		//TODO check for empty values to prevent errors!
		$sort = 'ORDER BY ' . self::sanitizeStockDataSortingColumn($sortByColumn, $sortOrder) . " $sortOrder\n";
		$start = (($page - 1) * $rowsPerPage);
		$limit = "LIMIT $start,$rowsPerPage";

		//die(self::STOCK_DATA_SELECT_BASIC . self::STOCK_DATA_SELECT_ADVANCED . self::STOCK_DATA_FROM_BASIC . self::STOCK_DATA_FROM_ADVANCED . self::STOCK_DATA_WHERE . $filterCondition . $sort . $limit);
		$aStockDataDBResult = DBQuery::getInstance() -> select(self::STOCK_DATA_SELECT_BASIC . self::STOCK_DATA_SELECT_ADVANCED . self::STOCK_DATA_FROM_BASIC . self::STOCK_DATA_FROM_ADVANCED . self::STOCK_DATA_WHERE . $filterCondition . $sort . $limit);
		ob_end_clean();

		while ($aStockDataRow = $aStockDataDBResult -> fetchAssoc()) {
			$aStockData['rows'][] = self::processStockDataRow($aStockDataRow);
		}

		return $aStockData;
	}

	/**
	 * @param mixed $aItemIDs
	 * @param mixed $aItemNos
	 * @param mixed $aItemNames
	 * @param mixed $aMarking1IDs
	 * @return string
	 */
	private static function prepareStockDataFilterCondition($aItemIDs, $aItemNos, $aItemNames, $aMarking1IDs) {
		$filterCondition = "\n";
		// prepare filter conditions
		if (!is_null($aMarking1IDs)) {
			if (!is_array($aMarking1IDs)) {
				$aMarking1ID = array($aMarking1IDs);
			}
			$filterCondition .= "AND\n\tItemsBase.Marking1ID IN  (" . implode(',', $aMarking1IDs) . ")\n";
		}

		if (!is_null($aItemIDs)) {
			if (!is_array($aItemIDs)) {
				$aItemIDS = array($aItemIDs);
			}
			$filterCondition .= "AND\n\tItemsBase.ItemID IN (" . implode(',', $aItemIDs) . ")\n";
		} else if (!is_null($aItemNos)) {
			if (!is_array($aItemNos)) {
				$aItemNos = array($aItemNos);
			}
			$filterCondition .= "AND\n\tItemsBase.ItemNo REGEXP '^" . implode('|^', $aItemNos) . "'\n";
		} else if (!is_null($aItemNames)) {
			if (!is_array($aItemNames)) {
				$aItemNames = array($aItemNames);
			}

			foreach ($aItemNames as $name) {
				$filterCondition .= "AND\n\tCONCAT(
		CASE WHEN (ItemsBase.BundleType = \"bundle\") THEN
			\"[Bundle] \"
		WHEN (ItemsBase.BundleType = \"bundle_item\") THEN
			\"[Bundle Artikel] \"
		ELSE
			\"\"
		END,
		ItemsBase.Name,
		CASE WHEN (AttributeValueSets.AttributeValueSetID IS NOT null) THEN
			CONCAT(\", \", AttributeValueSets.AttributeValueSetName)
		ELSE
			\"\"
		END\n\t) LIKE \"%$name%\"\n";
			}
		}

		return $filterCondition;
	}

	private static function sanitizeStockDataSortingColumn($sortByColumn, $sortOrder) {
		switch ($sortByColumn) {
			case 'ItemID' :
			case 'ItemNo' :
			case 'DailyNeed' :
			case 'Marking1ID' :
				return $sortByColumn;
				break;
			case 'Name' :
				return 'Sortname';
			case 'MonthlyNeed' :
				return 'DailyNeed';
			case 'CurrentStock' :
				return 'NetStock';
			case 'Date' :
				return 'LastUpdate';
			default :
				throw new RuntimeException("Unknown sort name: $sortByColumn");
		}
	}

	private static function processStockDataRow(array $aStockDataRow) {
		$sku = Values2SKU($aStockDataRow['ItemID'], $aStockDataRow['AttributeValueSetID']);
		//@formatter:off
		return array(
			'rowID' => $sku,
			'itemID' => intval($aStockDataRow['ItemID']),
			'itemNo' => $aStockDataRow['ItemNo'],
			'name' => $aStockDataRow['Name'],
			'rawData' => array(
					'A' => array(
						'isValid' => isset($aStockDataRow['QuantitiesA']) && $aStockDataRow['QuantitiesA'] !== '0',
						'skipped' => intval($aStockDataRow['SkippedA']),
						'quantities' => array_map('intval', explode(',', $aStockDataRow['QuantitiesA']))
					),
					'B' => array(
						'isValid' => isset($aStockDataRow['QuantitiesB']) && $aStockDataRow['QuantitiesB'] !== '0',
						'skipped' => intval($aStockDataRow['SkippedB']),
						'quantities' => array_map('intval', explode(',', $aStockDataRow['QuantitiesB']))
					)
			),
			'dailyNeed' => floatval($aStockDataRow['DailyNeed']),
			'currentStock' => intval($aStockDataRow['NetStock']),
			'marking1ID' => intval($aStockDataRow['Marking1ID']),
			'writeBackData' => array(
				'isWritingPermitted' => intval($aStockDataRow['WritePermission']) === 1,
				'reorderLevel' => array(
					'old' => intval($aStockDataRow['ReorderLevel']),
					'current' => isset($aStockDataRow['ProposedReorderLevel']) ? intval($aStockDataRow['ProposedReorderLevel']) : null,
					'error' => isset($aStockDataRow['ReorderLevelError']) ? 'Lieferzeit nicht konfiguriert' : null
				),
				'maxStockSuggestion' => array(
					'old' => intval($aStockDataRow['MaximumStock']),
					'current' => isset($aStockDataRow['ProposedMaximumStock']) ? intval($aStockDataRow['ProposedMaximumStock']) : null,
					'error' => isset($aStockDataRow['SupplierMinimumPurchaseError']) ? 'Lagerreichweite nicht konfiguriert' : null
				),
				'supplierMinimumPurchase' => array(
					'old' => intval($aStockDataRow['SupplierMinimumPurchase']),
					'current' => isset($aStockDataRow['ProposedSupplierMinimumPurchase']) ? intval($aStockDataRow['ProposedSupplierMinimumPurchase']) : null,
					'error' => isset($aStockDataRow['SupplierMinimumPurchaseError']) ? 'Lagerreichweite nicht konfiguriert' : null
				)
			),
			'vpe' => $aStockDataRow['VPE'] == 0 ? 1 : intval($aStockDataRow['VPE']),
			'lastUpdate' => isset($aStockDataRow['LastUpdate']) ? date('d.m.y, H:i:s', $aStockDataRow['LastUpdate']) : null
		);
		//@formatter:on
	}

	public static function getConfigJSON($key = null) {
		header('Content-Type: application/json');
		$result = array('success' => false, 'data' => NULL, 'error' => NULL);

		list($data, $error) = array_pad(self::getConfig($key), 2, 'unexpected padding occurred: getConfigJSON()');

		if (!is_null($error)) {
			$result['error'] = $error;
		} else {
			$result['success'] = true;
			$result['data'] = $data;
		}
		echo json_encode($result);
	}

	public static function setConfigJSON($key = null, $value = null) {
		header('Content-Type: application/json');
		$result = array('success' => false, 'data' => NULL, 'error' => NULL);

		if (is_null($key)) {
			$result['error'] = "No key to set value = '$value' in stock config";
		} else {;
			if (is_null($value)) {
				$result['error'] = "No value for $key in stock config";
			} else {
				list($data, $error) = array_pad(self::setConfig($key, $value), 2, 'unexpected padding occurred: setConfigJSON()');

				if (!is_null($error)) {
					$result['error'] = $error;
				} else {
					$result['success'] = true;
					$result['data'] = $data;
				}
			}
		}

		echo json_encode($result);
	}

	public static function getConfig($key = null) {
		$query = 'SELECT `ConfigType` AS `type`, `ConfigKey` AS `key`, `ConfigValue` AS `value` FROM MetaConfig WHERE `Active` = 1 AND `Domain` = \'stock\'';
		if (is_null($key)) {
			// getting all active k/v-pairs from stock config
		} else if (is_array($key)) {
			// getting value of $key from stock config;
			$query .= ' AND `ConfigKey` IN (' . implode(',', $key) . ')';
		} else {
			// getting value of $key from stock config;
			$query .= ' AND `ConfigKey` = \'' . $key . '\'';
		}

		ob_start();
		$dbResult = DBQuery::getInstance() -> select($query);
		ob_end_clean();

		$result = array();
		$error = '';

		while ($row = $dbResult -> fetchAssoc()) {
			switch ($row['type']) {
				case 'int' :
					$result[$row['key']] = intval($row['value']);
					break;
				case 'float' :
					$result[$row['key']] = floatval($row['value']);
					break;
				default :
					$error .= "ConfigType {$row['type']} not allowed\n";
			}
		}

		if (count($result) === 0) {
			$error .= "No data available for keys: " . implode(', ', $key) . "\n";
		}

		return array($result, $error === '' ? NULL : $error);
	}

	public static function setConfig($key, $value) {
		$query = "SELECT `ConfigType` AS `type`, `Active` AS `active`, `ConfigKey` AS `key`, `ConfigValue` AS `value` FROM MetaConfig WHERE `ConfigKey` = '$key' AND `Domain` = 'stock'";

		ob_start();
		$dbResult = DBQuery::getInstance() -> select($query);
		ob_end_clean();

		$result = array($key => NULL);
		$error = '';

		// check if key is available
		if ($dbResult -> getNumRows() === 1 && $row = $dbResult -> fetchAssoc()) {
			// ... then check if it is active
			if (intval($row['active']) === 1) {
				// ... ... then set value
				ob_start();
				DBQuery::getInstance() -> update("UPDATE MetaConfig SET `ConfigValue`='$value' WHERE `ConfigKey` = '$key' AND `Domain` = 'stock'");
				$dbResult = DBQuery::getInstance() -> select($query);
				ob_end_clean();
				if (($updatedRow = $dbResult -> fetchAssoc()) && ($updatedRow['value'] == $value)) {
					$result[$key] = $value;
				} else {
					$result[$key] = $updatedRow['value'];
					$error .= "unable to update key $key, value is still {$updatedRow['value']}\n";
				}

			} else {
				// ... ... otherwise: error
				$error .= "trying to set inactive key $key\n";
			}
		} else {
			// ... otherwise: error
			$error .= "key $key unavailable\n";
		}

		return array($result, $error === '' ? NULL : $error);
	}

};
?>