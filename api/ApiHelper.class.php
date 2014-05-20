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