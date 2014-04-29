<?php

require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * @author x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class CalculateTotalNetto {

	/**
	 * @var DateTime
	 */
	private $startDate;

	/**
	 * @var int
	 */
	private $nrOfMonthsBackwards;

	/**
	 * @var array
	 */
	private $aRunningCosts;

	/**
	 * @return CalculateTotalNetto
	 */
	public function __construct() {
		$now = new DateTime();

		$this -> startDate = new DateTime($now -> format('Y-m-01'));

		$this -> nrOfMonthsBackwards = 6;

		$this -> aRunningCosts = array();
	}

	/**
	 * @return void
	 */
	public function execute() {
		// for every (month,warehouse) currently considered:
		$dbResult = DBQuery::getInstance() -> select($this -> getQuery($this -> startDate, new DateInterval('P' . $this -> nrOfMonthsBackwards . 'M')));

		// ... get associated total revenue
		while ($currentTotelNetto = $dbResult -> fetchAssoc()) {
			$this -> aRunningCosts[] = $currentTotelNetto;
		}

		// ... store to db
		$this -> storeToDB();
	}

	private function storeToDB() {
		DBQuery::getInstance() -> insert('INSERT INTO `TotalNetto`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this -> aRunningCosts));
	}

	/**
	 * for all OrderItems that are:
	 * - from orders in a certain status ( 7 <= status < 8 OR 9 <= status)
	 * - from orders with type 'order'
	 * - from orders of interval [$startAt - $duringBackwardsInterval, $startAt]
	 * do:
	 * sum up their netto value and group them by (date, warehouse)
	 *
	 * @param DateTime $startAt
	 * @param DateInterval $duringBackwardsInterval
	 * @return string query
	 */
	private function getQuery(DateTime $startAt, DateInterval $duringBackwardsInterval) {

		$toDate = $startAt -> format('\'Y-m-d\'');
		$fromDate = $startAt -> sub($duringBackwardsInterval) -> format('\'Y-m-d\'');

		return "SELECT
	DATE_FORMAT(FROM_UNIXTIME(OrderHead.OrderTimestamp), '%Y%m01') AS `Date`,
	OrderItem.WarehouseID,
	SUM(OrderItem.Price * OrderItem.Quantity / (1 + OrderItem.VAT / 100)) AS `TotalNetto`
FROM
	OrderItem
LEFT JOIN
	OrderHead
ON
	OrderItem.OrderID = OrderHead.OrderID
WHERE
	((OrderHead.OrderStatus >= 7 AND OrderHead.OrderStatus < 8) OR OrderHead.OrderStatus >= 9)
AND
	OrderHead.OrderType = 'order'
AND
	FROM_UNIXTIME(OrderHead.OrderTimestamp) BETWEEN $fromDate AND $toDate 
GROUP BY
	`Date`,
	WarehouseID\n";
	}

}
?>