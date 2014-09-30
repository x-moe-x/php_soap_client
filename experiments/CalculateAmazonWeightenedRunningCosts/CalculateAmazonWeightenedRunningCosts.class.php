<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);
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
	const DEFAULT_AMAZON_NR_OF_MONTHS_BACKWARDS = 2;

	/**
	 * @return CalculateAmazonWeightenedRunningCosts
	 */
	public function __construct() {
		$this -> identifier4Logger = __CLASS__;

		$now = new DateTime();

		$this -> oStartDate = new DateTime($now -> format('Y-m-01'));
		//$this -> oStartDate = new DateTime("2014-05-01");
		
		$this -> oInterval = new DateInterval('P' . self::DEFAULT_AMAZON_NR_OF_MONTHS_BACKWARDS . 'M');
	}

	private function prepareGroups() {
		$warehouses = ApiHelper::getWarehouseList();
		$groups = array();

		foreach ($warehouses as $warehouse) {
			if (!isset($groups[$warehouse['groupID']])) {
				$groups[$warehouse['groupID']] = array();
			}
			$groups[$warehouse['groupID']][] = $warehouse['id'];
		}

		return $groups;
	}

	private function arePrequisitesMet(&$cRRatData) {
		$standardGroup = ApiWarehouseGrouping::getConfig('standardGroup');

		// check if standard group fields are filled properly
		foreach ($cRRatData as $month => $month_data) {
			if (!isset($month_data[$standardGroup]['absoluteCosts'])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @return void
	 */
	public function execute() {
		$groups = $this -> prepareGroups();

		// 1. get amazon specific total shipping: shipping_ratio[date] = total_netto / total_charged_shipping_costs
		$amazonTotalNettoAndShipping = $this -> getAmazonTotalNettoAndShippingByDate();

		// 2. get global cost-revenue-ration per month, per group
		$cRRatData = ApiRunningCosts::getRunningCostsTable($this -> oStartDate, self::DEFAULT_AMAZON_NR_OF_MONTHS_BACKWARDS);

		if (!$this -> arePrequisitesMet($cRRatData)) {
			// std group is not filled properly
			$this -> getLogger() -> info(__FUNCTION__ . ' prequisites for value k calculation not met. Please fill standard group running costs fields');
			return;
		}

		// 3. get amazon specific per group total
		$aNData = $this -> prepareANData($groups);

		$aTN = function($month) use (&$amazonTotalNettoAndShipping) {
			// for all warehouses: sum netto(month,w,amazon);
			if (isset($amazonTotalNettoAndShipping[$month])) {
				return $amazonTotalNettoAndShipping[$month]['amazonTotalNetto'];
			} else {
				return 0.0;
			}
		};

		$aTS = function($month) use (&$amazonTotalNettoAndShipping) {
			// shipping(month,amazon);
			if (isset($amazonTotalNettoAndShipping[$month])) {
				return $amazonTotalNettoAndShipping[$month]['amazonTotalShipping'];
			} else {
				return 0.0;
			}
		};

		$cRRat = function($month, $group) use (&$cRRatData) {
			if (isset($cRRatData[$month][$group]) && $cRRatData[$month][$group]['nettoRevenue'] > 0) {
				return $cRRatData[$month][$group]['absoluteCosts'] / $cRRatData[$month][$group]['nettoRevenue'];
			} else {
				return 'NaN';
			}
		};

		$aN = function($month, $group) use (&$aNData) {
			if (isset($aNData[$month][$group])) {
				return $aNData[$month][$group];
			} else {
				return 0.0;
			}
		};

		$months = array_keys($cRRatData);

		$aWRat = function() use (&$groups, &$aN, &$cRRat, &$aTS, &$aTN, &$months) {
			$sumTotal = 0.0;
			foreach ($months as $t_month) {
				$sumAWCRRat = 0.0;
				foreach ($groups as $group => $groupList) {
					$cRRat_int = floatval($cRRat($t_month, $group));
					if (!is_nan($cRRat_int)) {
						$sumAWCRRat += $aN($t_month, $group) * $cRRat_int;
					}
				}
				$aTN_int = $aTN($t_month);
				if ($aTN_int > 0) {
					$sumTotal += ($sumAWCRRat - $aTS($t_month)) / $aTN_int;
				}
			}
			return $sumTotal / self::DEFAULT_AMAZON_NR_OF_MONTHS_BACKWARDS;
		};

		$generalCosts = ApiGeneralCosts::getGeneralCosts($months);
		$averageGeneralCosts = 0.0;
		foreach ($generalCosts as $generalCost) {
			$averageGeneralCosts += $generalCost['relativeCosts'] / self::DEFAULT_AMAZON_NR_OF_MONTHS_BACKWARDS;
		}

		try {
			$valueK = $aWRat();
			ApiAmazon::setConfig('WarehouseRunningCostsAmount', number_format($valueK, 10));
			$this -> getLogger() -> info(__FUNCTION__ . ' storing to config WarehouseRunningCostsAmount = ' . number_format($valueK, 10));
			ApiAmazon::setConfig('CommonRunningCostsAmount', number_format($averageGeneralCosts, 4));
			$this -> getLogger() -> info(__FUNCTION__ . ' storing to config CommonRunningCostsAmount = ' . number_format($averageGeneralCosts, 4));
		} catch(Exception $e) {
			$this -> getLogger() -> debug(__FUNCTION__ . ' Error: ' . $e -> getMessage());
		}
	}

	/**
	 * @return array $amazonTotalShippingRatio
	 */
	private function getAmazonTotalNettoAndShippingByDate() {
		$amazonTotalNettoAndShippingResult = array();

		$amazonTotalNettoAndShippingDBResult = DBQuery::getInstance() -> select(TotalNettoQuery::getTotalNettoAndShippingCostsQuery($this -> oStartDate, $this -> oInterval, ApiAmazon::AMAZON_REFERRER_ID));

		while ($amazonTotalNettoAndShipping = $amazonTotalNettoAndShippingDBResult -> fetchAssoc()) {
			$amazonTotalNettoAndShippingResult[$amazonTotalNettoAndShipping['Date']] = array('amazonTotalNetto' => floatval($amazonTotalNettoAndShipping['TotalNetto']), 'amazonTotalShipping' => floatval($amazonTotalNettoAndShipping['TotalShippingNetto']));
		}

		return $amazonTotalNettoAndShippingResult;
	}

	private function prepareANData(&$groups) {
		$amazonPerWarehouseNettoDBResult = DBQuery::getInstance() -> select(TotalNettoQuery::getPerWarehouseNettoQuery($this -> oStartDate, $this -> oInterval, ApiAmazon::AMAZON_REFERRER_ID));
		$aNData = array();
		while ($amazonPerWarehouseNetto = $amazonPerWarehouseNettoDBResult -> fetchAssoc()) {
			$groupID = null;
			foreach ($groups as $groupID_int => $groupList_int) {
				if (in_array($amazonPerWarehouseNetto['WarehouseID'], $groupList_int)) {
					$groupID = $groupID_int;
					break;
				}
			}

			$date = $amazonPerWarehouseNetto['Date'];

			if (!isset($aNData[$date])) {
				$aNData[$date] = array();
			}

			if (!isset($aNData[$date][$groupID])) {
				$aNData[$date][$groupID] = 0.0;
			}

			$aNData[$date][$groupID] += floatval($amazonPerWarehouseNetto['PerWarehouseNetto']);
		}

		return $aNData;
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
