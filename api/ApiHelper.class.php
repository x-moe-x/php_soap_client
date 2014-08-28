<?php
require_once 'ApiRunningCosts.class.php';

class ApiHelper {

	public static function getAverageCostsJSON() {
		header('Content-Type: application/json');
		$result = array('success' => false, 'data' => NULL, 'error' => NULL);

		try {
			$result['data'] = self::getAverageRunningCosts();
			$result['success'] = true;
		} catch(Exception $e) {
			$result['error'] = $e -> getMessage();
		}
		echo json_encode($result);
	}

	public static function getAverageCosts(array $aRunningCostsTable = null) {
		if (is_null($aRunningCostsTable)) {
			$aRunningCostsTable = ApiRunningCosts::getRunningCostsTable();
		}

		$standardGroup = ApiWarehouseGrouping::getConfig('standardGroup');
		$averageElements = count($aRunningCostsTable);
		$monthsReverse = array_reverse(array_keys($aRunningCostsTable));

		// determine how many elements are to be averaged
		foreach ($monthsReverse as $month) {
			if (is_null($aRunningCostsTable[$month][$standardGroup]['absoluteCosts'])) {
				$averageElements--;
			} else {
				break;
			}
		}

		$average = array();
		for (reset($aRunningCostsTable), $idx = 0, $month = current($aRunningCostsTable); $idx < $averageElements; $idx++, $month = next($aRunningCostsTable)) {
			foreach ($month as $groupID => $values) {
				if (!isset($average[$groupID])) {
					$average[$groupID] = array('absoluteCosts' => 0.0, 'relativeCosts' => 0.0);
				}

				if (isset($values['absoluteCosts'])) {
					$average[$groupID]['relativeCosts'] += ($values['absoluteCosts'] - $values['shippingRevenue']) / ($averageElements * $values['nettoRevenue']);
					$average[$groupID]['absoluteCosts'] += $values['absoluteCosts'] / $averageElements;
				}
			}
		}

		$averageResult = array();
		foreach ($average as $groupID => $value) {
			$averageResult[] = array('isAverage' => true, 'groupID' => $groupID, 'absoluteCosts' => $value['absoluteCosts'], 'relativeCosts' => $value['relativeCosts']);
		}
		return $averageResult;
	}

	public static function setConfigJSON($key, $value, $domain) {
		header('Content-Type: application/json');
		$result = array('success' => false, 'data' => NULL, 'error' => NULL);

		if (is_null($key)) {
			$result['error'] = "No key to set value = '$value' in stock config";
		} else {;
			if (is_null($value)) {
				$result['error'] = "No value for $key in stock config";
			} else {
				try {
					$result['data'] = self::setConfig($key, $value, $domain);
					$result['success'] = true;
				} catch(Exception $e) {
					$result['error'] = $e -> getMessage();
				}
			}
		}

		echo json_encode($result);
	}

	public static function setConfig($key, $value, $domain) {
		$selectQuery = "SELECT `ConfigType` AS `type`, `Active` AS `active`, `ConfigKey` AS `key`, `ConfigValue` AS `value` FROM MetaConfig WHERE `ConfigKey` = '$key' AND `Domain` = '$domain'";
		$updateQuery = "UPDATE MetaConfig SET `ConfigValue`='$value' WHERE `ConfigKey` = '$key' AND `Domain` = '$domain'";

		$errorMessage = null;
		$success = false;

		ob_start();
		try {
			DBQuery::getInstance() -> begin();
			$checkKeyAvailabilityDBResult = DBQuery::getInstance() -> select($selectQuery);

			// check if key is available
			if ($checkKeyAvailabilityDBResult -> getNumRows() === 1 && $row = $checkKeyAvailabilityDBResult -> fetchAssoc()) {
				// ... then check if it is active
				if (intval($row['active']) === 1) {
					// ... ... then set value
					DBQuery::getInstance() -> update($updateQuery);
					$checkUpdateDBResult = DBQuery::getInstance() -> select($selectQuery);
					if (($updatedRow = $checkUpdateDBResult -> fetchAssoc()) && ($updatedRow['value'] == $value)) {
						DBQuery::getInstance() -> commit();
						$success = true;
						switch ($updatedRow['type']) {
							case 'int' :
								$value = intval($value);
								break;
							case 'float' :
								$value = floatval($value);
								break;
						}
					} else {
						DBQuery::getInstance() -> rollback();
						$errorMessage = "Unable to update key $key, value is still {$updatedRow['value']}";
					}
				} else {
					// ... ... otherwise: error
					DBQuery::getInstance() -> rollback();
					$errorMessage = "Trying to set inactive key $key";
				}
			} else {
				// ... otherwise: error
				DBQuery::getInstance() -> rollback();
				$errorMessage = "Key $key unavailable";
			}
		} catch (Exception $e) {
			DBQuery::getInstance() -> rollback();
			$errorMessage = $e -> getMessage();
		}
		ob_end_clean();

		if ($success) {
			return array($key => $value);
		} else {
			throw new RuntimeException($errorMessage);
		}
	}

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
		$resultWarehouseList = DBQuery::getInstance() -> select('SELECT WarehouseList.WarehouseID, WarehouseList.Name, WarehouseGroupMapping.GroupID FROM WarehouseList LEFT JOIN WarehouseGroupMapping ON WarehouseList.WarehouseID = WarehouseGroupMapping.WarehouseID ORDER BY WarehouseID ASC');
		ob_end_clean();

		$result = array();
		while ($warehouse = $resultWarehouseList -> fetchAssoc()) {
			$result[$warehouse['WarehouseID']] = array('id' => intval($warehouse['WarehouseID']), 'name' => $warehouse['Name'], 'groupID' => (isset($warehouse['GroupID']) ? intval($warehouse['GroupID']) : null));
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

}
?>