<?php
require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once 'ApiHelper.class.php';
require_once 'ApiWarehouseGrouping.class.php';

class ApiRunningCosts {

	const DEFAULT_NR_OF_MONTHS_BACKWARDS = 6;

	public static function getAverageRunningCostsJSON() {
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

	public static function getAverageRunningCosts(array $aRunningCostsTable = null){
		if (is_null($aRunningCostsTable)){
			$aRunningCostsTable = self::getRunningCostsTable();
		}

		$standardGroup = ApiWarehouseGrouping::getConfig('standardGroup');
		$averageElements = count($aRunningCostsTable);
		$monthsReverse = array_reverse(array_keys($aRunningCostsTable));

		// determine how many elements are to be averaged
		foreach ($monthsReverse as $month) {
			if (is_null($aRunningCostsTable[$month][$standardGroup]['absoluteCosts'])){
				$averageElements--;
			} else {
				break;
			}
		}

		$average = array();
		for(reset($aRunningCostsTable), $idx = 0, $month = current($aRunningCostsTable);$idx < $averageElements; $idx++, $month = next($aRunningCostsTable)){
			foreach ($month as $groupID => $values) {
				if (!isset($average[$groupID])){
					$average[$groupID] = 0.0;
				}

				if (isset($values['absoluteCosts'])) {
					$average[$groupID] += ($values['absoluteCosts'] - $values['shippingRevenue']) / ($averageElements * $values['nettoRevenue']);
				}
			}
		}

		return $average;
	}

	private static function getPrepopulatedTable($months, $groups) {
		$table = array();
		foreach ($months as $month) {
			$table[$month] = array();
			foreach ($groups['groupData'] as $group) {
				$table[$month][$group['id']] = null;
			}
		}
		return $table;
	}

	public static function getRunningCostsTable() {
		$months = ApiHelper::getMonthDates(new DateTime(), self::DEFAULT_NR_OF_MONTHS_BACKWARDS, /* omit current month */true);

		/* tableQuery:
		 *
		 * wr(date  |wid|[netto]|[shipping])
		 *     |      |
		 *     |      V
		 *     |  gm(wid|[gid])
		 *     |           |
		 *     V           V
		 * rc(date  |     gid|[abs])
		 *
		 * -------------------------
		 *
		 * tableQuery(date|gid|[abs]|SUM[netto]|SUM[shipping])		 *
		 */

		$tableQuery = "SELECT wr.Date AS `date`, gm.GroupID AS `groupID`, rc.AbsoluteCosts AS `absoluteCosts`, SUM(wr.PerWarehouseNetto) AS `nettoRevenue`, SUM(wr.PerWarehouseShipping) AS `shippingRevenue` FROM PerWarehouseRevenue AS wr JOIN WarehouseGroupMapping AS gm ON wr.WarehouseID = gm.WarehouseID LEFT JOIN RunningCostsNew AS rc ON (gm.GroupID = rc.GroupID) AND (wr.Date = rc.Date) WHERE wr.Date IN (" . implode(',', $months) . ") GROUP BY wr.Date, gm.GroupID";

		ob_start();
		$tableDBResult = DBQuery::getInstance() -> select($tableQuery);
		ob_end_clean();

		// pre populate result
		$table = self::getPrepopulatedTable($months, ApiWarehouseGrouping::getGroups());

		while ($row = $tableDBResult -> fetchAssoc()) {
			$table[$row['date']][$row['groupID']] = array('absoluteCosts' => (isset($row['absoluteCosts']) ? floatval($row['absoluteCosts']) : null), 'nettoRevenue' => floatval($row['nettoRevenue']), 'shippingRevenue' => floatval($row['shippingRevenue']));
		}

		return $table;
	}

	public static function setRunningCostsJSON($groupID, $date, $value) {
		header('Content-Type: application/json');
		$result = array('success' => false, 'data' => NULL, 'error' => NULL);

		if (!is_null($groupID) && !is_null($date) && !is_null($value)) {
			try {
				$data = self::setRunningCosts($groupID, $date, $value);
				$result['success'] = true;
				$result['data'] = $data;
			} catch(Exception $e) {
				$result['error'] = $e -> getMessage();
			}
		} else {
			$result['error'] = "Missing parameter groupID, month or value\n";
		}
		echo json_encode($result);
	}

	public static function setRunningCosts($groupID, $date, $value) {
		$groupAvailabilityCheckQuery = "SELECT `GroupID` FROM WarehouseGroups WHERE `GroupID` = $groupID";
		$insertValueQuery = "INSERT INTO RunningCostsNew (`Date`, `GroupID`, `AbsoluteCosts`) VALUES($date, $groupID, $value) ON DUPLICATE KEY UPDATE `Date` = $date, `GroupID` = $groupID, `AbsoluteCosts` = $value";
		$deleteOnZeroQuery = "DELETE FROM RunningCostsNew WHERE `Date` = $date AND `GroupID` = $groupID";
		$checkValueQuery = "SELECT `Date` AS `date`, `GroupID` AS `groupID`, `AbsoluteCosts` AS `value` FROM RunningCostsNew WHERE `Date` = $date AND `GroupID` = $groupID";

		$success = false;
		$returnValue = array('groupID'=> $groupID, 'date' => $date, 'value' => null);
		$errorMessage = null;

		ob_start();
		try {
			DBQuery::getInstance() -> begin();
			// if group is available ...
			if (DBQuery::getInstance() -> select($groupAvailabilityCheckQuery) -> getNumRows() === 1) {
				// ... if value is positive
				if ($value > 0) {
					// ... insert value
					DBQuery::getInstance() -> insert($insertValueQuery);
					$checkValueDBResult = DBQuery::getInstance() -> select($checkValueQuery);
					// ... if insert successful ...
					if ($checkValueDBResult -> getNumRows() === 1 && ($row = $checkValueDBResult -> fetchAssoc()) && ($row['value'] == $value)) {
						// ... success
						$returnValue['value'] = floatval($row['value']);
						$success = true;
						DBQuery::getInstance() -> commit();
					} else {
						// ... otherwise 'insertion failed' error
						$errorMessage = "Update of ($groupID -> $date) = $value failed";
						DBQuery::getInstance() -> rollback();
					}
				} else {
					// ... delete value
					DBQuery::getInstance() -> delete($deleteOnZeroQuery);
					$checkValueDBResult = DBQuery::getInstance() -> select($checkValueQuery);
					// ... if delete successful ...
					if ($checkValueDBResult -> getNumRows() === 0){
						$success = true;
						DBQuery::getInstance() -> commit();
					} else {
						// ... otherwise 'deletion failed' error
						$errorMessage = "Deleting of ($groupID -> $date) failed";
						DBQuery::getInstance() -> rollback();
					}
				}
			} else {
				// ... otherwise 'unknown group' error
				$errorMessage = "Unknown groupID $groupID";
				DBQuery::getInstance() -> rollback();
			}
		} catch(Exception $e) {
			$success = false;
			DBQuery::getInstance() -> rollback();
			$errorMessage = $e -> getMessage();
		}
		ob_end_clean();

		if ($success) {
			return $returnValue;
		} else {
			throw new RuntimeException($errorMessage);
		}
	}

}
?>
