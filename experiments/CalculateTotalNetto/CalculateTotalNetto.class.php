<?php

require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/DBUtils2.class.php';
require_once ROOT . 'experiments/Common/TotalNettoQuery.class.php';

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

		$this -> aRunningCosts = array();
	}

	/**
	 * @return void
	 */
	public function execute() {
		// for every month currently considered:
		// ... calculate average charged shipping costs
		$totalDBResult = DBQuery::getInstance() -> select(TotalNettoQuery::getTotalNettoAndShippingCostsQuery($this -> oStartDate));
		$totalNettoAndShipping = array();
		while ($currentTotalNettoAndShipping = $totalDBResult -> fetchAssoc()) {
			$totalNettoAndShipping[$currentTotalNettoAndShipping['Date']] = $currentTotalNettoAndShipping;
		}

		// for every (month,warehouse) currently considered:
		// ... get associated total revenue
		$perWarehouseDBResult = DBQuery::getInstance() -> select(TotalNettoQuery::getPerWarehouseNettoQuery($this -> oStartDate));

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
	 *
	 * @return Logger
	 */
	protected function getLogger() {
		return Logger::instance($this -> identifier4Logger);
	}

}
?>
