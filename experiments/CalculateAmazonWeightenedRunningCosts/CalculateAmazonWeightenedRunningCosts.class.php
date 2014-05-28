<?php
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'api/ApiGeneralCosts.class.php';
require_once ROOT . 'api/ApiAmazon.class.php';
require_once ROOT . 'api/ApiHelper.class.php';
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
	 * @var float
	 */
	const AMAZON_PROVISION_DEFAULT_VALUE = 0.1785;

	/**
	 * @return CalculateAmazonWeightenedRunningCosts
	 */
	public function __construct() {
		$this -> identifier4Logger = __CLASS__;

		$now = new DateTime();

		$this -> oStartDate = new DateTime($now -> format('Y-m-01'));

		$this -> oInterval = new DateInterval('P' . self::DEFAULT_AMAZON_NR_OF_MONTHS_BACKWARDS . 'M');
	}

	public static function arePrequisitesMet() {
		$netxpressWarehouseID = 1;
		$now = new DateTime();
		$startDate = new DateTime($now -> format('Y-m-01'));
		$months = ApiHelper::getMonthDates($startDate, CalculateAmazonWeightenedRunningCosts::DEFAULT_AMAZON_NR_OF_MONTHS_BACKWARDS, true);
		$generalCostsNetxpressWarehouse = ApiGeneralCosts::getCostsTotal(0, array(1 => array('id' => $netxpressWarehouseID, 'name' => null)), $months, null, null);
		foreach ($months as $date) {
			if (!$generalCostsNetxpressWarehouse[$netxpressWarehouseID][$date]['absolute'] > 0) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @return void
	 */
	public function execute() {
		$amazonPerDatePerWarehouseWeightedPercentage = array();

		// 1. get amazon specific total shipping: shipping_ratio[date] = total_netto / total_charged_shipping_costs
		$amazonTotalNettoAndShipping = $this -> getAmazonTotalNettoAndShippingByDate();

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
			 * amazon_weight[date][warehouseID] = amazonPerWarehouseNetto / amazonTotalNetto
			 *
			 * then apply weight:
			 * amazon_weighted_percentage[date][warehouseID] = amazon_weight[date][warehouseID] * general_cost[warehouseID][date][percentage]
			 */

			if ($amazonTotalNettoAndShipping[$date]['TotalNetto'] > 0) {
				$amazonPerDatePerWarehouseWeightedPercentage[$date][$warehouseID] = $amazonPerWarehouseNetto['PerWarehouseNetto'] / $amazonTotalNettoAndShipping[$date]['TotalNetto'] * $overallPerWarehouseTotalAndPercentage[$warehouseID][$date]['percentage'] / 100;
			}
		}

		// 4. adjust percentage with charged shipping costs and amazon provision
		$amazonWeightedPercentage = 0;
		foreach (array_keys($amazonPerDatePerWarehouseWeightedPercentage) as $date) {

			/*
			 * Perform the following calculation:
			 *
			 * amazon_weighted_percentage[date] = (costs - shippingRevenue) / amazonTotalNetto
			 *
			 * with: term costs = SUM(for all warehouse id's: amazon_weighted_percentage[date][id]) * amazonTotalNetto
			 * 					  + amazon_provision * (amazonTotalNetto + amazonTotalShipping)
			 *
			 * simplifies to:
			 * amazon_weighted_percentage[date] = SUM(for all warehouse id's: amazon_weighted_percentage[date][id])
			 * 									  + amazon_provision
			 * 									  - (1 - amazon_provision) * amazonTotalShipping / amazonTotalNetto
			 *
			 */

			if ($amazonTotalNettoAndShipping[$date]['TotalNetto'] > 0) {
				$amazonProvision = 0.0;
				try {
					$amazonProvisionConfig = ApiAmazon::getConfig('ProvisionCosts');
					$amazonProvision = $amazonProvisionConfig['ProvisionCosts'];
				} catch(Exception $e) {
					$this -> getLogger() -> debug(__FUNCTION__ . ' Error: ' . $e->getMessage() . ', setting amazonProvision to defalut value');
					$amazonProvision = self::AMAZON_PROVISION_DEFAULT_VALUE;
				}

				$amazonWeightedPercentage += (array_sum($amazonPerDatePerWarehouseWeightedPercentage[$date]) + ($amazonProvision - 1) * $amazonTotalNettoAndShipping[$date]['TotalShippingNetto'] / $amazonTotalNettoAndShipping[$date]['TotalNetto']) / 2;
			} else {
				$this -> getLogger() -> debug(__FUNCTION__ . ' Error: TotalNetto ' . $amazonTotalNettoAndShipping[$date]['TotalNetto'] . ' <= 0');
				die();
			}
		}

		try {
			ApiAmazon::setConfig('RunninCostsAmount', number_format($amazonWeightedPercentage, 10));
		} catch(Exception $e) {
			$this -> getLogger() -> debug(__FUNCTION__ . ' Error: ' . $e -> getMessage());
		}
	}

	/**
	 * @return array $amazonTotalShippingRatio
	 */
	private function getAmazonTotalNettoAndShippingByDate() {
		$amazonTotalNettoAndShippingResult = array();

		$amazonTotalNettoAndShippingDBResult = DBQuery::getInstance() -> select(TotalNettoQuery::getTotalNettoAndShippingCostsQuery($this -> oStartDate, $this -> oInterval, self::AMAZON_REFERRER_ID));

		while ($amazonTotalNettoAndShipping = $amazonTotalNettoAndShippingDBResult -> fetchAssoc()) {
			$amazonTotalNettoAndShippingResult[$amazonTotalNettoAndShipping['Date']] = array('TotalNetto' => floatval($amazonTotalNettoAndShipping['TotalNetto']), 'TotalShippingNetto' => floatval($amazonTotalNettoAndShipping['TotalShippingNetto']));
		}

		return $amazonTotalNettoAndShippingResult;
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
