<?php
require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once 'ApiHelper.class.php';

class ApiGeneralCosts {

	const MODE_WITH_GENERAL_COSTS = 0x1;

	const MODE_WITH_AVERAGE = 0x2;

	public static function getCostsJSON($warehouseID, $date) {
		header('Content-Type: application/json');
		$result = array('success' => false, 'data' => NULL, 'error' => NULL);

		if (!is_null($warehouseID) && !is_null($date)) {

			try {
				$data = self::getCostsTotal(self::MODE_WITH_GENERAL_COSTS, array($warehouseID => array('id' => $warehouseID, 'name' => null)), array($date));
				$result['success'] = true;
				$result['data'] = array('warehouseID' => $warehouseID, 'date' => $date, 'value' => $data[$warehouseID][$date]);
			} catch(Exception $e) {
				$result['error'] = $e -> getMessage();
			}
		} else {
			$result['error'] = "Missing parameter warehouse id or date\n";
		}
		echo json_encode($result);
	}

	public static function setCostsJSON($warehouseID, $date, $value) {
		header('Content-Type: application/json');
		$result = array('success' => false, 'data' => NULL, 'error' => NULL);

		if (!is_null($warehouseID) && !is_null($date) && !is_null($value)) {
			try {
				$data = self::setCosts($warehouseID, $date, $value);
				$result['success'] = true;
				$result['data'] = $data;
			} catch(Exception $e) {
				$result['error'] = $e -> getMessage();
			}
		} else {
			$result['error'] = "Missing parameter warehouse id, date or value\n";
		}
		echo json_encode($result);
	}

	public static function getCostsTotal($mode = null, array $warehouses = null, array $months = null, DateTime $fromDate = null, $nrOfMonths = 6) {
		// prepare standard parameter mode
		if (is_null($mode)) {
			$mode = self::MODE_WITH_AVERAGE | self::MODE_WITH_GENERAL_COSTS;
		}

		// prepare standard parameter warehouses
		// if warehouses not set ...
		if (empty($warehouses)) {
			// ... then if in general-costs mode ...
			if (($mode & self::MODE_WITH_GENERAL_COSTS) === self::MODE_WITH_GENERAL_COSTS) {
				// ... ... then init warehouses with warehouse list, prepended with general costs col as warehouse id -1
				$warehouses = array(-1 => array('id' => -1, 'name' => '')) + ApiHelper::getWarehouseList();
			} else {
				// ... ... otherwise just init warehouses with warehouse list
				$warehouses = ApiHelper::getWarehouseList();
			}
		} else {
			// ... otherwise: check for not available warehouse id's in both general costs mode as well as non general costs mode
			if (($mode & self::MODE_WITH_GENERAL_COSTS) === self::MODE_WITH_GENERAL_COSTS) {
				$warehouses = array_intersect_key( array(-1 => array('id' => -1, 'name' => '')) + ApiHelper::getWarehouseList(), $warehouses);
			} else {
				$warehouses = array_intersect_key(ApiHelper::getWarehouseList(), $warehouses);
			}

			if (count($warehouses) === 0) {
				// ... ... then throw exception
				throw new Exception("Not availabe warehouses requested");
			}

			// if in general-costs mode prepend given warehouses with general costs col as warhouse id -1
			if ((($mode & self::MODE_WITH_GENERAL_COSTS) === self::MODE_WITH_GENERAL_COSTS) && !key_exists(-1, $warehouses)) {
				$warehouses = array(-1 => array('id' => -1, 'name' => '')) + $warehouses;
			}
		}

		// prepare standard parameter months
		if (is_null($months)) {
			if (is_null($fromDate)) {
				$fromDate = new DateTime();
			}
			$months = ApiHelper::getMonthDates($fromDate, $nrOfMonths);
		}

		// prepare empty table
		$result = array();

		foreach ($warehouses as $warehouse) {
			$result[$warehouse['id']] = array();
		}

		foreach ($result as &$warehouse) {
			foreach ($months as $month) {
				$warehouse[$month] = array('absolute' => null, 'percentage' => null);
			}
			if (($mode & self::MODE_WITH_AVERAGE) === self::MODE_WITH_AVERAGE) {
				$warehouse['average'] = array('absolute' => null, 'percentage' => null);
			}
		}

		// get data from db
		$query = 'SELECT rc.Date, rc.WarehouseID, rc.AbsoluteAmount, rc.Percentage, tn.TotalNetto FROM RunningCosts AS rc LEFT JOIN	TotalNetto AS tn ON	(rc.Date = tn.Date AND rc.WarehouseID = tn.WarehouseID) WHERE rc.Date IN (' . implode(',', $months) . ') AND rc.WarehouseID IN (' . implode(',', array_map(function($warehouse) {
			return $warehouse['id'];
		}, $warehouses)) . ')';

		ob_start();
		$dbResult = DBQuery::getInstance() -> select($query);
		ob_end_clean();

		// populate table
		while ($runningCostRecord = $dbResult -> fetchAssoc()) {
			if (array_key_exists($runningCostRecord['WarehouseID'], $result) && array_key_exists($runningCostRecord['Date'], $result[$runningCostRecord['WarehouseID']])) {
				if (intval($runningCostRecord['WarehouseID']) === -1) {
					$result[$runningCostRecord['WarehouseID']][$runningCostRecord['Date']]['percentage'] = $runningCostRecord['Percentage'];
				} else {
					$result[$runningCostRecord['WarehouseID']][$runningCostRecord['Date']]['absolute'] = $runningCostRecord['AbsoluteAmount'];
					if (floatval($runningCostRecord['AbsoluteAmount']) > 0) {
						$result[$runningCostRecord['WarehouseID']][$runningCostRecord['Date']]['percentage'] = number_format(100 * $runningCostRecord['AbsoluteAmount'] / $runningCostRecord['TotalNetto'], 2);
					}
				}
			}
		}

		if (($mode & self::MODE_WITH_AVERAGE) === self::MODE_WITH_AVERAGE) {
			// calculate averages
			$maxDate = count($months) - 2;
			// '- (1 for current month + 1 for average row)'
			foreach ($result as &$warehouse) {
				$allMonthsTotalAbsolute = 0;
				$allMonthsTotalPercentage = 0;
				for ($date = 0; $date < $maxDate; $date++) {
					$allMonthsTotalAbsolute += $warehouse[$months[$date]]['absolute'];
					$allMonthsTotalPercentage += $warehouse[$months[$date]]['percentage'];
				}
				$warehouse['average']['absolute'] = number_format($allMonthsTotalAbsolute / $maxDate, 2, '.', '');
				$warehouse['average']['percentage'] = number_format($allMonthsTotalPercentage / $maxDate, 2, '.', '');
			}
		}
		return $result;
	}

	public static function setCosts($warehouseID, $date, $value) {
		// check if warehouse is available
		if (key_exists($warehouseID, array_values(ApiHelper::getWarehouseList())) || $warehouseID == -1) {
			// ... then if general costs are to be updated ...
			if ($warehouseID == -1) {
				// ... ... then update general costs (warehouse id = -1) at (-1 -> date)
				// ... ... and update corresponding percentage-value
				$insertValue = array('AbsoluteAmount' => 'NULL', 'Percentage' => $value != 0 ? $value : 'NULL');
			} else {
				// ... ... otherwise update running costs at (warehouse -> date)
				// ... then update corresponding absolute-value and clear corresponding percentage value
				$insertValue = array('AbsoluteAmount' => $value != 0 ? $value : 'NULL', 'Percentage' => 'NULL');
			}
		} else {
			// ... otherwise: error
			throw new Exception("Unknown warehouse id $warehouseID");
		}
		// perform actual insertion
		// @formatter:off
		ob_start();
		DBQuery::getInstance() -> insert('INSERT INTO `RunningCosts`' . DBUtils::buildInsert(array(
			'Date' =>			$date,
			'WarehouseID' =>	$warehouseID) + $insertValue)
			.'ON DUPLICATE KEY UPDATE' . DBUtils::buildOnDuplicateKeyUpdate(
			$insertValue
		));
		ob_end_clean();
		// @formatter:on

		// check insertion:
		$check = self::getCostsTotal(self::MODE_WITH_GENERAL_COSTS, array($warehouseID => array('id' => $warehouseID, 'name' => '')), array($date));
		if ($warehouseID == -1) {
			$checkValue = $check[$warehouseID][$date]['percentage'];
		} else {
			$checkValue = $check[$warehouseID][$date]['absolute'];
		}
		if ($value != $checkValue) {
			throw new Exception("Update of ($warehouseID -> $date) = $value unsuccessful. Current value still $checkValue");
		}

		return array('warehouseID' => $warehouseID, 'date' => $date, 'value' => $check[$warehouseID][$date]);
	}

}
?>
