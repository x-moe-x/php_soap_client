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
	private $oStartDate;

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

		$this -> oStartDate = new DateTime($now -> format('Y-m-01'));

		$this -> defaultNrOfMonthsBackwards = 6;

		$this -> aRunningCosts = array();
	}

	/**
	 * @return void
	 */
	public function execute() {
		// for every month currently considered:
		// ... calculate average charged shipping costs
		$totalDBResult = DBQuery::getInstance() -> select($this -> getTotalNettoAndShippingCostsQuery($this -> oStartDate));
		$totalNettoAndShipping = array();
		while ($currentTotalNettoAndShipping = $totalDBResult -> fetchAssoc()) {
			$totalNettoAndShipping[$currentTotalNettoAndShipping['Date']] = $currentTotalNettoAndShipping;
		}

		// for every (month,warehouse) currently considered:
		// ... get associated total revenue
		$perWarehouseDBResult = DBQuery::getInstance() -> select($this -> getPerWarehouseNettoQuery($this -> oStartDate));

		while ($currentPerWarehouseNetto = $perWarehouseDBResult -> fetchAssoc()) {
			$currentTotalNetto = $totalNettoAndShipping[$currentPerWarehouseNetto['Date']];
			$currentPerWarehouseShipping = $currentPerWarehouseNetto['PerWarehouseNetto'] / $currentTotalNetto['TotalNetto'] * $currentTotalNetto['TotalShippingNetto'];
			$this -> aRunningCosts[] = array_merge($currentPerWarehouseNetto, array('PerWarehouseShipping' => $currentPerWarehouseShipping));
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
	private function getPerWarehouseNettoQuery(DateTime $startAt, DateInterval $duringBackwardsInterval = null) {

		$internalStartAt = clone $startAt;

		if (is_null($duringBackwardsInterval)) {
			$duringBackwardsInterval = new DateInterval('P' . $this -> defaultNrOfMonthsBackwards . 'M');
		}

		$toDate = $internalStartAt -> format('\'Y-m-d\'');
		$fromDate = $internalStartAt -> sub($duringBackwardsInterval) -> format('\'Y-m-d\'');

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
	 * for all Orders that are:
	 * - in a certain status ( 7 <= status < 8 OR 9 <= status)
	 * - of type 'order'
	 * - of interval [$startAt - $duringBackwardsInterval, $startAt]
	 * do:
	 * sum up their total netto value as well as total netto shipment costs and group them by date
	 *
	 * @param DateTime $startAt
	 * @param DateInterval $duringBackwardsInterval
	 * @return string query
	 */
	private function getTotalNettoAndShippingCostsQuery(DateTime $startAt, DateInterval $duringBackwardsInterval = null) {

		$internalStartAt = clone $startAt;

		if (is_null($duringBackwardsInterval)) {
			$duringBackwardsInterval = new DateInterval('P' . $this -> defaultNrOfMonthsBackwards . 'M');
		}

		$toDate = $internalStartAt -> format('\'Y-m-d\'');
		$fromDate = $internalStartAt -> sub($duringBackwardsInterval) -> format('\'Y-m-d\'');

		$consideredTimestamp = 'DoneTimestamp';

		return "SELECT
	DATE_FORMAT(FROM_UNIXTIME(OrderHead.$consideredTimestamp), '%Y%m01') AS `Date`,
	SUM(TotalNetto) AS `TotalNetto`,
	SUM(TotalInvoice - (TotalVAT + TotalNetto)) AS `TotalShippingNetto`
FROM
	OrderHead
WHERE
	((OrderHead.OrderStatus >= 7 AND OrderHead.OrderStatus < 8) OR OrderHead.OrderStatus >= 9)
AND
	OrderHead.OrderType = 'order'
AND
	FROM_UNIXTIME(OrderHead.$consideredTimestamp) BETWEEN $fromDate AND $toDate
GROUP BY
	`Date`\n";
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
