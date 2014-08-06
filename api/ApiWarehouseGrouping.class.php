<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(-1);

require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once 'ApiHelper.class.php';

class ApiWarehouseGrouping {
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
		$query = 'SELECT `GroupID` AS `id`, `GroupName` AS `name` FROM WarehouseGroups';

		ob_start();
		$dbResult = DBQuery::getInstance() -> select($query);
		ob_end_clean();

		$result = array('standardGroupID' => 1, 'groupData' => array());
		while ($aGroup = $dbResult -> fetchAssoc()) {
			array_push($result['groupData'], $aGroup);
		}

		return $result;
	}

}
?>