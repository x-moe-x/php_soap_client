<?php
require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once 'ApiHelper.class.php';

class ApiRunningCosts {

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
		$checkValueQuery = "SELECT `Date` AS `date`, `GroupID` AS `groupID`, `AbsoluteCosts` AS `value` FROM RunningCostsNew WHERE `Date` = $date AND `GroupID` = $groupID";

		$isInsertSuccessful = false;
		$returnValue = null;
		$errorMessage = null;

		ob_start();
		try {
			DBQuery::getInstance() -> begin();
			// if group is available ...
			if (DBQuery::getInstance() -> select($groupAvailabilityCheckQuery) -> getNumRows() === 1) {
				// ... insert value
				DBQuery::getInstance() -> insert($insertValueQuery);
				$checkValueDBResult = DBQuery::getInstance() -> select($checkValueQuery);
				// ... if insert successful ...
				if ($checkValueDBResult -> getNumRows() === 1 && ($returnValue = $checkValueDBResult -> fetchAssoc()) && ($returnValue['value'] == $value)) {
					// ... success
					$isInsertSuccessful = true;
					DBQuery::getInstance() -> commit();
				} else {
					// ... otherwise 'insertion failed' error
					$errorMessage = "Update of ($groupID -> $date) = $value failed";
					DBQuery::getInstance() -> rollback();
				}
			} else {
				// ... otherwise 'unknown group' error
				$errorMessage = "Unknown groupID $groupID";
				DBQuery::getInstance() -> rollback();
			}
		} catch(Exception $e) {
			$isInsertSuccessful = false;
			DBQuery::getInstance() -> rollback();
			$errorMessage = $e -> getMessage();
		}
		ob_end_clean();

		if ($isInsertSuccessful) {
			return $returnValue;
		} else {
			throw new RuntimeException($errorMessage);
		}
	}

}
?>
