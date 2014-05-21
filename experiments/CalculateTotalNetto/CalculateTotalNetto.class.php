<?php

require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * @author x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class CalculateTotalNetto {
	/**
	 * @var string
	 */
	private $identifier4Logger = '';

	/**
	 * @var DateTime
	 */
	private $startDate;

	/**
	 * @var int
	 */
	private $defaultNrOfMonthsBackwards;

	/**
	 * @var array
	 */
	private $aRunningCosts;

	/**
	 * @return CalculateTotalNetto
	 */
	public function __construct() {
		$this -> identifier4Logger = __CLASS__;

		$now = new DateTime();

		$this -> startDate = new DateTime($now -> format('Y-m-01'));

		$this -> defaultNrOfMonthsBackwards = 6;

		$this -> aRunningCosts = array();
	}

	/**
	 * @return void
	 */
	public function execute() {
		// for every (month,warehouse) currently considered:
		$dbResult = DBQuery::getInstance() -> select($this -> getQuery($this -> startDate));

		// ... get associated total revenue
		while ($currentTotalNetto = $dbResult -> fetchAssoc()) {
			$this -> aRunningCosts[] = $currentTotalNetto;
		}

		// ... store to db
		$this -> storeToDB();
	}

	private function storeToDB() {
		$recordCount = count($this -> aRunningCosts);

		if ($recordCount > 0) {
			$this -> getLogger() -> debug(__FUNCTION__ . " storing $recordCount total netto records to db");
			DBQuery::getInstance() -> insert('INSERT INTO `PerWarehouseRevenue`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this -> aRunningCosts));
		}
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
	private function getQuery(DateTime $startAt, DateInterval $duringBackwardsInterval = null) {

		if (is_null($duringBackwardsInterval)) {
			$duringBackwardsInterval = new DateInterval('P' . $this -> defaultNrOfMonthsBackwards . 'M');
		}

		$toDate = $startAt -> format('\'Y-m-d\'');
		$fromDate = $startAt -> sub($duringBackwardsInterval) -> format('\'Y-m-d\'');

		$consideredTimestamp = 'DoneTimestamp';

		return "SELECT
	DATE_FORMAT(FROM_UNIXTIME(OrderHead.$consideredTimestamp), '%Y%m01') AS `Date`,
	OrderItem.WarehouseID,
	SUM(OrderItem.Price * OrderItem.Quantity / (1 + OrderItem.VAT / 100)) AS `PerWarehouseNetto`
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
	FROM_UNIXTIME(OrderHead.$consideredTimestamp) BETWEEN $fromDate AND $toDate
GROUP BY
	`Date`,
	WarehouseID\n";
	}

	/**
	 *
	 * @return Logger
	 */
	protected function getLogger() {
		return Logger::instance($this -> identifier4Logger);
	}
}
?>
