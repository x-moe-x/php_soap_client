<?php
require_once 'ApiRunningCosts.class.php';

class ApiHelper {

	/**
	 * @param string|null $functionName optional function name to get the lastUpdate timestamp from
	 * @return array
	 */
	public static function getLastUpdateJSON($functionName = null) {
		header('Content-Type: application/json');
		$result = array('success' => false, 'data' => NULL, 'error' => NULL);

		try {
			$result['data'] = self::getLastUpdate(array($functionName));
			$result['success'] = true;
		} catch(Exception $e) {
			$result['error'] = $e -> getMessage();
		}
		echo json_encode($result);
	}

	/**
	 * @param string|string[]|null $functionName optional function name or array of function names to get the lastUpdate timestamp from
	 * @return int|array
	 */
	public static function getLastUpdate($functionName = null) {
		$query = 'SELECT `Function` as `name`, `LastUpdate` as `lastUpdate` FROM `MetaLastUpdate`';
		if (is_null($functionName)) {
			// get all elements
		} else if (is_array($functionName)) {
			// get specific elements
			$query .= ' WHERE `Function` IN (\'' . implode('\',\'', $functionName) . '\')';
		} else {
			// get specific element
			$query .= " WHERE `Function` = '$functionName'";
		}

		ob_start();
		$dbResult = DBQuery::getInstance() -> select($query);
		ob_end_clean();

		if ($dbResult -> getNumRows() > 0) {
			if ($dbResult -> getNumRows() === 1 && !is_array($functionName)) {
				if ($row = $dbResult -> fetchAssoc()) {
					return $row['lastUpdate'];
				} else {
					throw new RuntimeException("Could not fetch result for function name $functionName");
				}
			}
			// return multiple values
			else {
				$result = array();

				while ($row = $dbResult -> fetchAssoc()) {
					$result[] = $row;
				}
				return $result;
			}
		} else {
			throw new RuntimeException("Could not fetch result for function name $functionName");
		}
	}

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

	public static function getAverageCosts(array $aRunningCostsTable = null, array $aGeneralCostsTable = null) {
		if (is_null($aRunningCostsTable)) {
			$aRunningCostsTable = ApiRunningCosts::getRunningCostsTable();
			$aGeneralCostsTable = ApiGeneralCosts::getGeneralCosts(array_keys($aRunningCostsTable));
		}

		if (is_null($aGeneralCostsTable)) {
			$aGeneralCostsTable = ApiGeneralCosts::getGeneralCosts(array_keys($aRunningCostsTable));
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

		$averageGeneralCostsElements = count($aGeneralCostsTable);
		$monthsReverseGC = array_reverse(array_keys($aRunningCostsTable));

		foreach ($monthsReverseGC as $month) {
			if (is_null($aGeneralCostsTable[$month]['relativeCosts'])) {
				$averageGeneralCostsElements--;
			} else {
				break;
			}
		}

		$averageGeneralCosts = 0.0;
		for (reset($aGeneralCostsTable), $idx = 0, $month = current($aGeneralCostsTable); $idx < $averageGeneralCostsElements; $idx++, $month = next($aGeneralCostsTable)) {
			$averageGeneralCosts += $month['relativeCosts'] / $averageGeneralCostsElements;
		}

		$averageResult = array('generalCosts' => array('isAverage' => true, 'average' => $averageGeneralCosts), 'runningCosts' => array());
		foreach ($average as $groupID => $value) {
			$averageResult['runningCosts'][] = array('isAverage' => true, 'groupID' => $groupID, 'absoluteCosts' => $value['absoluteCosts'], 'relativeCosts' => $value['relativeCosts']);
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

	public static function getConfigJSON($key, $domain) {
		header('Content-Type: application/json');
		$result = array('success' => false, 'data' => NULL, 'error' => NULL);

		try {
			if (is_null($key)) {
				$result['data'] = self::getConfig(null, $domain);
			} else if (is_array($key)) {
				$result['data'] = self::getConfig($key, $domain);
			} else {
				$result['data'] = array($key => self::getConfig($key, $domain));
			}
			$result['success'] = true;
		} catch(Exception $e) {
			$result['error'] = $e -> getMessage();
		}
		echo json_encode($result);
	}

	public static function getConfig($key = null, $domain) {
		$query = 'SELECT `ConfigType` AS `type`, `ConfigKey` AS `key`, `ConfigValue` AS `value` FROM MetaConfig WHERE `Active` = 1 AND `Domain` = \'' . $domain . '\'';
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

		// return single value
		if ($dbResult -> getNumRows() > 0) {
			if ($dbResult -> getNumRows() === 1 && !is_array($key)) {
				if ($row = $dbResult -> fetchAssoc()) {
					switch ($row['type']) {
						case 'int' :
							return intval($row['value']);
						case 'float' :
							return floatval($row['value']);
						default :
							throw new RuntimeException("ConfigType {$row['type']} not allowed");
					}
				} else {
					throw new RuntimeException("Could not fetch result for key $key");
				}
			}
			// return multiple values
			else {
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
		}
		// error
		else {
			throw new RuntimeException("Could not fetch result for key $key");
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

	/**
	 * @param string    $referrerIDFieldName
	 * @param int|float $referrerID
	 * @return string $referrerIDFieldName BETWEEN
	 */
	public static function getNormalizedReferrerCondition($referrerIDFieldName, $referrerID)
	{
		$minReferrerID = floor($referrerID);
		return "$referrerIDFieldName BETWEEN $minReferrerID AND " . ($minReferrerID + 0.99);
	}
}
