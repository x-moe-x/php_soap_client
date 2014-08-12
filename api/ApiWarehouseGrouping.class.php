<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(-1);

require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once 'ApiHelper.class.php';

class ApiWarehouseGrouping {

	const WAREHOUSE_GROUPING_DOMAIN = 'warehouseGrouping';

	public static function setConfigJSON($key, $value) {
		return ApiHelper::setConfigJSON($key, $value, self::WAREHOUSE_GROUPING_DOMAIN);
	}

	public static function setConfig($key, $value) {
		return ApiHelper::setConfig($key, $value, self::WAREHOUSE_GROUPING_DOMAIN);
	}

	public static function getConfigJSON($key) {
		header('Content-Type: application/json');
		$result = array('success' => false, 'data' => NULL, 'error' => NULL);

		try {
			if (is_array($key)) {
				$result['data'] = self::getConfig($key);
			} else {
				$result['data'] = array($key => self::getConfig($key));
			}
			$result['success'] = true;
		} catch(Exception $e) {
			$result['error'] = $e -> getMessage();
		}
		echo json_encode($result);
	}

	public static function getConfig($key) {
		$query = 'SELECT `ConfigType` AS `type`, `ConfigKey` AS `key`, `ConfigValue` AS `value` FROM MetaConfig WHERE `Active` = 1 AND `Domain` = \'warehouseGrouping\'';
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
		else if ($dbResult -> getNumRows() > 1) {
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
		} else {
			throw new RuntimeException("ConfigKey $key not found");
		}
	}

	public static function getGroupsJSON() {
		header('Content-Type: application/json');
		$result = array('success' => false, 'data' => NULL, 'error' => NULL);

		try {
			$result['data'] = self::getGroups();
			$result['success'] = true;
		} catch(Exception $e) {
			$result['error'] = $e -> getMessage();
		}
		echo json_encode($result);
	}

	public static function getGroups() {
		$query = 'SELECT `GroupID` AS `id`, `GroupName` AS `name` FROM WarehouseGroups ORDER BY `id`';

		ob_start();
		$dbResult = DBQuery::getInstance() -> select($query);
		ob_end_clean();

		$result = array('standardGroupID' => 1, 'groupData' => array());
		while ($aGroup = $dbResult -> fetchAssoc()) {
			array_push($result['groupData'], $aGroup);
		}

		return $result;
	}

	public static function createGroupJSON($name) {
		header('Content-Type: application/json');
		$result = array('success' => false, 'data' => NULL, 'error' => NULL);

		try {
			$result['data'] = self::createGroup(str_replace('_',' ',$name));
			$result['success'] = true;
		} catch(Exception $e) {
			$result['error'] = $e -> getMessage();
		}
		echo json_encode($result);
	}

	public static function createGroup($name) {
		$insertQuery = "INSERT INTO WarehouseGroups (`GroupID`, `GroupName`) VALUES('NULL','$name')";
		$checkInsertQuery = "SELECT `GroupID` AS `id`, `GroupName` AS `name` FROM WarehouseGroups WHERE `GroupName` LIKE '$name'";

		$wasCheckSuccessfull = false;
		$wasInsertSuccessfull = false;
		$newGroupData = null;
		$errorMessage = null;

		ob_start();
		try {
			DBQuery::getInstance() -> begin();
			$wasInsertSuccessfull = DBQuery::getInstance() -> insert($insertQuery) === 1;
			$checkInsertResult = DBQuery::getInstance() -> select($checkInsertQuery);

			if ($wasInsertSuccessfull && ($checkInsertResult -> getNumRows() === 1)) {
				DBQuery::getInstance() -> commit();
				$newGroupData = $checkInsertResult -> fetchAssoc();
				$wasCheckSuccessfull = true;
			} else {
				DBQuery::getInstance() -> rollback();
				$errorMessage = "Unable to create group $name";
			}
		} catch(Exception $e) {
			$errorMessage = $e->getMessage();
		}
		ob_end_clean();

		if ($wasInsertSuccessfull && $wasCheckSuccessfull) {
			return $newGroupData;
		} else {
			throw new RuntimeException($errorMessage);
		}
	}

}
?>
