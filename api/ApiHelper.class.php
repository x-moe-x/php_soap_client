<?php

class ApiHelper {
	public static function getMonthDates(DateTime $fromDate, $nrOfMonthDates = 6) {
		$result = array();
		$normalizedDate = new DateTime($fromDate -> format('Ym01'));
		$result[] = $normalizedDate -> format('Ymd');
		for ($i = 1; $i <= $nrOfMonthDates; $i++) {
			$result[] = $normalizedDate -> sub(new DateInterval('P1M')) -> format('Ymd');
		}
		return array_reverse($result);
	}

	public static function getWarehouseList() {
		ob_start();
		$resultWarehouseList = DBQuery::getInstance() -> select('SELECT * FROM `WarehouseList` ORDER BY WarehouseID ASC');
		ob_end_clean();

		$result = array();
		while ($warehouse = $resultWarehouseList -> fetchAssoc()) {
			$result[$warehouse['WarehouseID']] = array('id' => $warehouse['WarehouseID'], 'name' => $warehouse['Name']);
		}
		return $result;
	}
}
?>