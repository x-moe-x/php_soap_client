<?php

/**
 * @author x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class CalculateRunningCosts {

	/**
	 * @return CalculateRunningCosts
	 */
	public function __construct() {
	}

	/**
	 * @return void
	 */
	public function execute() {
		// for every (month,warehouse) currently considered do ...

		// ... get associated total revenue

		// ... compute percentage value
	}

	private function getQuery(DateTime $startFrom, DateInterval $duringInterval) {
		// search for all orderitems that are ...
		// ... from orders in a certain status
		// ... from orders from a certain time interval
		// ... from actual orders

		// sum up their netto value

		// group them by (date,warehouseID)

		$result = 'SELECT
	SUM(OrderItem.Price) AS `TotalNetto`,
	DATE_FORMAT(FROM_UNIXTIME(OrderHead.OrderTimestamp), \'%Y%m01\') AS `Date`,
	OrderItem.WarehouseID
FROM
	OrderItem
LEFT JOIN
	OrderHead
ON
	OrderItem.OrderID = OrderHead.OrderID
WHERE
	OrderHead.OrderType = \'order\'
GROUP BY
	`Date`,
	WarehouseID' . PHP_EOL;

		return $result;
	}

}
?>