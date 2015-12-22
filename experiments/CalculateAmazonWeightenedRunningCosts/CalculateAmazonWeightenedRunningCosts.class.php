<?php

require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'api/ApiGeneralCosts.class.php';
require_once ROOT . 'api/ApiAmazon.class.php';
require_once ROOT . 'api/ApiHelper.class.php';
require_once ROOT . 'experiments/Common/TotalNettoQuery.class.php';

/**
 * @author    x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class CalculateAmazonWeightenedRunningCosts
{
	/**
	 * @var int
	 */
	const DEFAULT_AMAZON_NR_OF_MONTHS_BACKWARDS = 2;
	/**
	 * @var string
	 */
	private $identifier4Logger;
	/**
	 * @var DateTime
	 */
	private $oStartDate;
	/**
	 * @var int
	 */
	private $nrOfDataMonths;
	/**
	 * @var int
	 */
	private $nrOfCalculationMonths;

	/**
	 * @return CalculateAmazonWeightenedRunningCosts
	 */
	public function __construct()
	{
		$this->identifier4Logger = __CLASS__;

		$now = new DateTime();

		$this->oStartDate = new DateTime($now->format('Y-m-01'));
		//$this -> oStartDate = new DateTime("2014-07-01");

		$this->nrOfDataMonths = ApiRunningCosts::DEFAULT_NR_OF_MONTHS_BACKWARDS;

		$this->nrOfCalculationMonths = self::DEFAULT_AMAZON_NR_OF_MONTHS_BACKWARDS;

	}

	/**
	 * @return void
	 */
	public function execute()
	{
		$this->getLogger()->debug(__FUNCTION__ . ' ... starting');

		$groups = $this->prepareGroups();

		// 1. get amazon specific total netto and shipping revenue
		$amazonTotalNettoAndShipping = $this->getAmazonTotalNettoAndShippingByDate();

		// 2. get global cost-revenue-ration per month, per group
		$cRRatData = ApiRunningCosts::getRunningCostsTable($this->oStartDate, $this->nrOfDataMonths);

		// 3. get amazon specific per group total
		$aNData = $this->prepareANData($groups);

		$aTN = function ($month) use (&$amazonTotalNettoAndShipping)
		{
			// for all warehouses: sum netto(month,w,amazon);
			if (isset($amazonTotalNettoAndShipping[$month]))
			{
				return $amazonTotalNettoAndShipping[$month]['amazonTotalNetto'];
			} else
			{
				return 0.0;
			}
		};

		$aTS = function ($month) use (&$amazonTotalNettoAndShipping)
		{
			// shipping(month,amazon);
			if (isset($amazonTotalNettoAndShipping[$month]))
			{
				return $amazonTotalNettoAndShipping[$month]['amazonTotalShipping'];
			} else
			{
				return 0.0;
			}
		};

		$cRRat = function ($month, $group) use (&$cRRatData)
		{
			if (isset($cRRatData[$month][$group]) && $cRRatData[$month][$group]['nettoRevenue'] > 0)
			{
				return $cRRatData[$month][$group]['absoluteCosts'] / $cRRatData[$month][$group]['nettoRevenue'];
			} else
			{
				return 'NaN';
			}
		};

		$aN = function ($month, $group) use (&$aNData)
		{
			if (isset($aNData[$month][$group]))
			{
				return $aNData[$month][$group];
			} else
			{
				return 0.0;
			}
		};

		$months = $this->getRelevantMonths($cRRatData);

		$aWRat = function () use (&$groups, &$aN, &$cRRat, &$aTS, &$aTN, &$months)
		{
			$sumTotal = 0.0;
			foreach ($months as $t_month)
			{
				$sumAWCRRat = 0.0;
				foreach ($groups as $group => $groupList)
				{
					$cRRat_int = floatval($cRRat($t_month, $group));
					if (!is_nan($cRRat_int))
					{
						$sumAWCRRat += $aN($t_month, $group) * $cRRat_int;
					}
				}
				$aTN_int = $aTN($t_month);
				if ($aTN_int > 0)
				{
					$sumTotal += ($sumAWCRRat - $aTS($t_month)) / $aTN_int;
				}
			}

			return $sumTotal / self::DEFAULT_AMAZON_NR_OF_MONTHS_BACKWARDS;
		};


		try
		{

			$valueK = $aWRat();
			$averageCosts = $this->getAverageGeneralCosts($cRRatData);
			ApiAmazon::setConfig('WarehouseRunningCostsAmount', number_format($valueK, 10));
			$this->getLogger()->info(__FUNCTION__ . ' storing to config WarehouseRunningCostsAmount = ' . number_format($valueK, 10));
			ApiAmazon::setConfig('CommonRunningCostsAmount', number_format($averageCosts, 4));
			$this->getLogger()->info(__FUNCTION__ . ' storing to config CommonRunningCostsAmount = ' . number_format($averageCosts, 4));
		} catch (Exception $e)
		{
			$this->getLogger()->debug(__FUNCTION__ . ' Error: ' . $e->getMessage());
		}
	}

	/**
	 * @return array
	 */
	private function prepareGroups()
	{
		$warehouses = ApiHelper::getWarehouseList();
		$groups = array();

		foreach ($warehouses as $warehouse)
		{
			if (!isset($groups[$warehouse['groupID']]))
			{
				$groups[$warehouse['groupID']] = array();
			}
			$groups[$warehouse['groupID']][] = $warehouse['id'];
		}

		return $groups;
	}

	/**
	 * @return array $amazonTotalShippingRatio
	 */
	private function getAmazonTotalNettoAndShippingByDate()
	{
		$amazonTotalNettoAndShippingResult = array();

		$amazonTotalNettoAndShippingDBResult = DBQuery::getInstance()->select(TotalNettoQuery::getTotalNettoAndShippingCostsQuery($this->oStartDate, new DateInterval('P' . $this->nrOfDataMonths . 'M'), ApiAmazon::AMAZON_REFERRER_ID));

		while ($amazonTotalNettoAndShipping = $amazonTotalNettoAndShippingDBResult->fetchAssoc())
		{
			$amazonTotalNettoAndShippingResult[$amazonTotalNettoAndShipping['Date']] = array(
				'amazonTotalNetto'    => floatval($amazonTotalNettoAndShipping['TotalNetto']),
				'amazonTotalShipping' => floatval($amazonTotalNettoAndShipping['TotalShippingNetto'])
			);
		}

		return $amazonTotalNettoAndShippingResult;
	}

	/**
	 * @param $groups
	 *
	 * @return array
	 */
	private function prepareANData(&$groups)
	{
		$amazonPerWarehouseNettoDBResult = DBQuery::getInstance()->select(TotalNettoQuery::getPerWarehouseNettoQuery($this->oStartDate, new DateInterval('P' . $this->nrOfDataMonths . 'M'), ApiAmazon::AMAZON_REFERRER_ID));
		$aNData = array();
		while ($amazonPerWarehouseNetto = $amazonPerWarehouseNettoDBResult->fetchAssoc())
		{
			$groupID = null;
			foreach ($groups as $groupID_int => $groupList_int)
			{
				if (in_array($amazonPerWarehouseNetto['WarehouseID'], $groupList_int))
				{
					$groupID = $groupID_int;
					break;
				}
			}

			$date = $amazonPerWarehouseNetto['Date'];

			if (!isset($aNData[$date]))
			{
				$aNData[$date] = array();
			}

			if (!isset($aNData[$date][$groupID]))
			{
				$aNData[$date][$groupID] = 0.0;
			}

			$aNData[$date][$groupID] += floatval($amazonPerWarehouseNetto['PerWarehouseNetto']);
		}

		return $aNData;
	}

	/**
	 * @param $cRRatData
	 *
	 * @return array
	 */
	private function getRelevantMonths(&$cRRatData)
	{
		// get all months that could be considered
		$consideredMonths = array_reverse(array_keys($cRRatData));

		/** @var int $standardGroup */
		$standardGroup = ApiWarehouseGrouping::getConfig('standardGroup');

		// find first one with actual content
		for (reset($consideredMonths), $idx = 0, $month = current($consideredMonths); (key($consideredMonths) !== null) && ($idx < $this->nrOfDataMonths - $this->nrOfCalculationMonths); $month = next($consideredMonths), $idx++)
		{
			if (!is_null($cRRatData[$month][$standardGroup]['absoluteCosts']))
			{
				// found one!
				return array(
					$month,
					next($consideredMonths)
				);
			}
		}

		// found none...
		throw new RuntimeException('Found no sufficient running cost data within ' . $this->nrOfDataMonths . ' months, cannot comupte value K');
	}

	/**
	 * @param $cRRatData
	 *
	 * @return float
	 */
	private function getAverageGeneralCosts(&$cRRatData)
	{
		// get all months that could be considered
		$aConsideredMonths = array_reverse(array_keys($cRRatData));

		$aGeneralCosts = ApiGeneralCosts::getGeneralCosts($aConsideredMonths);

		// find first one with actual content
		for (reset($aConsideredMonths), $idx = 0, $month = current($aConsideredMonths); (key($aConsideredMonths) !== null) && ($idx < $this->nrOfDataMonths - $this->nrOfCalculationMonths); $month = next($aConsideredMonths), $idx++)
		{
			if (!is_null($aGeneralCosts[$month]['relativeCosts']))
			{
				// found one!
				$averageGeneralCosts = 0.0;
				for ($i = 0; $i < $this->nrOfCalculationMonths; $i++, $month = next($aConsideredMonths))
				{
					$averageGeneralCosts += $aGeneralCosts[$month]['relativeCosts'] / $this->nrOfCalculationMonths;
				}

				return $averageGeneralCosts;
			}
		}

		// found none...
		throw new RuntimeException('Found no sufficient general cost data within ' . $this->nrOfDataMonths . ' months, cannot comute value L');
	}

	/**
	 * @return Logger
	 */
	protected function getLogger()
	{
		return Logger::instance($this->identifier4Logger);
	}

}

