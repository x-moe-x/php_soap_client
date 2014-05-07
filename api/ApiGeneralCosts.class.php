<?php
require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once 'ApiHelper.class.php';

class ApiGeneralCosts {

	const MODE_WITH_GENERAL_COSTS = 0x1;

	const MODE_WITH_AVERAGE = 0x2;

	public static function getCostsTotal($mode = null, array $warehouses = null, array $months = null, DateTime $fromDate = null, $nrOfMonths = 6) {
		// prepare standard parameters
		if (is_null($mode)) {
			$mode = self::MODE_WITH_AVERAGE | self::MODE_WITH_GENERAL_COSTS;
		}

		if (is_null($warehouses)) {
			if (($mode & self::MODE_WITH_GENERAL_COSTS) === self::MODE_WITH_GENERAL_COSTS) {
				$warehouses = array(-1 => array('id' => -1, 'name' => ''));
				$warehouses = $warehouses + ApiHelper::getWarehouseList();
			} else {
				$warehouses = ApiHelper::getWarehouseList();
			}
		} else if (($mode & self::MODE_WITH_GENERAL_COSTS) === self::MODE_WITH_GENERAL_COSTS) {
			$warehouses = array(-1 => array('id' => -1, 'name' => '')) + $warehouses;
		}

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
		$query = 'SELECT
	RunningCosts.Date,
	RunningCosts.WarehouseID,
	RunningCosts.AbsoluteAmount,
	RunningCosts.Percentage,
	TotalNetto.TotalNetto
FROM
	RunningCosts
LEFT JOIN
	TotalNetto
ON
	(RunningCosts.Date = TotalNetto.Date AND RunningCosts.WarehouseID = TotalNetto.WarehouseID)
WHERE
	RunningCosts.Date IN (' . implode(',', $months) . ')
AND
	RunningCosts.WarehouseID IN (' . implode(',', array_map(function($warehouse) {
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
			$maxDate = count($months) - 1;
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

}
?>