<?php
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'api/ApiGeneralCosts.class.php';
require_once ROOT . 'api/ApiAmazon.class.php';
require_once ROOT . 'experiments/Common/TotalNettoQuery.class.php';

/**
 * @author x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class CalculateAmazonWeightenedRunningCosts {

	/**
	 * @var string
	 */
	private $identifier4Logger;

	/**
	 * @var DateTime
	 */
	private $oStartDate;

	/**
	 * @var DateInterval
	 */
	private $oInterval;

	/**
	 * @var int
	 */
	const AMAZON_REFERRER_ID = 4;

	/**
	 * @var int
	 */
	const DEFAULT_AMAZON_NR_OF_MONTHS_BACKWARDS = 2;

	/**
	 * @return CalculateAmazonWeightenedRunningCosts
	 */
	public function __construct() {
		$this -> identifier4Logger = __CLASS__;

		$now = new DateTime();

		$this -> oStartDate = new DateTime($now -> format('Y-m-01'));

		$this -> oInterval = new DateInterval('P' . self::DEFAULT_AMAZON_NR_OF_MONTHS_BACKWARDS . 'M');
	}

	/**
	 * @return void
	 */
	public function execute() {
		$amazonPerDatePerWarehouseWeightedPercentage = array();

		// 1. get amazon specific total shipping: shipping_ratio[date] = total_netto / total_charged_shipping_costs
		$amazonTotalNetto = $this -> getAmazonTotalNettoByDate();

		// 2. get overall per warehouse total and shipping costs as well as percentage of absolut amount from total
		$overallPerWarehouseTotalAndPercentage = ApiGeneralCosts::getCostsTotal(ApiGeneralCosts::MODE_WITH_TOTAL_NETTO_AND_SHIPPING_VALUE, null, null, $this -> oStartDate, self::DEFAULT_AMAZON_NR_OF_MONTHS_BACKWARDS);

		// 3. get amazon specific per warehouse total
		$amazonPerWarehouseNettoDBResult = DBQuery::getInstance() -> select(TotalNettoQuery::getPerWarehouseNettoQuery($this -> oStartDate, $this -> oInterval, self::AMAZON_REFERRER_ID));
		while ($amazonPerWarehouseNetto = $amazonPerWarehouseNettoDBResult -> fetchAssoc()) {
			$warehouseID = $amazonPerWarehouseNetto['WarehouseID'];
			$date = $amazonPerWarehouseNetto['Date'];

			if (!key_exists($date, $amazonPerDatePerWarehouseWeightedPercentage)) {
				$amazonPerDatePerWarehouseWeightedPercentage[$date] = array();
			}

			/*
			 * Perform the following calculation:
			 *
			 * first calculate weight:
			 * amazon_weight[date][warehouseID] = (amazonPerWarehouseNetto + amazonPerWarehouseShipping) / (amazonTotalNetto + amazonTotalShipping)
			 *
			 * with: term (amazonPerWarehouseNetto + amazonPerWarehouseShipping) = amazonPerWarehouseNetto * (1 + amazonTotalShipping / amazonTotalNetto)
			 * with: term (amazonTotalNetto + amazonTotalShipping) = amazonTotalNetto * (1 + amazonTotalShipping / amazonTotalNetto)
			 *
			 * simplifies to: amazon_weight[date][warehouseID] = amazonPerWarehouseNetto / amazonTotalNetto
			 *
			 * then apply weight:
			 * amazon_weighted_percentage[date][warehouseID] = amazon_weight[date][warehouseID] * general_cost[warehouseID][date][percentage]
			 *
			 * with: term (overallPerWarehouseNetto + overallPerWarehouseShipping) = general_cost[warehouseID][date][total]
			 */

			if ($amazonTotalNetto[$date] > 0) {
				$amazonPerDatePerWarehouseWeightedPercentage[$date][$warehouseID] = $amazonPerWarehouseNetto['PerWarehouseNetto'] / $amazonTotalNetto[$date] * $overallPerWarehouseTotalAndPercentage[$warehouseID][$date]['percentage'];
			}
		}

		// 4. calculate weighted mean
		$amazonWeightedPercentage = 0;
		foreach (array_keys($amazonPerDatePerWarehouseWeightedPercentage) as $date) {
			$amazonWeightedPercentage += array_sum($amazonPerDatePerWarehouseWeightedPercentage[$date]) / 2;
		}

		try {
			ApiAmazon::setConfig('RunninCostsAmount', number_format($amazonWeightedPercentage / 100, 8));
		} catch(Exception $e) {
			$this -> getLogger() -> debug(__FUNCTION__ . ' Error: ' . $e -> getMessage());
		}
	}

	/**
	 * @return array $amazonTotalShippingRatio
	 */
	private function getAmazonTotalNettoByDate() {
		$amazonTotalNettoResult = array();

		$amazonTotalNettoDBResult = DBQuery::getInstance() -> select(TotalNettoQuery::getTotalNettoAndShippingCostsQuery($this -> oStartDate, $this -> oInterval, self::AMAZON_REFERRER_ID));

		while ($amazonTotalNetto = $amazonTotalNettoDBResult -> fetchAssoc()) {
			$amazonTotalNettoResult[$amazonTotalNetto['Date']] = floatval($amazonTotalNetto['TotalNetto']);
		}

		return $amazonTotalNettoResult;
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
