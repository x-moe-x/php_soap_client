<?php

require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'lib/db/DBQueryResult.class.php';
require_once ROOT . 'includes/SKUHelper.php';
require_once ROOT . 'includes/GetConfig.php';

/**
 * @author x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class CalculateHistogram {
	/**
	 *
	 * @var string
	 */
	private $identifier4Logger = '';

	private $config = null;

	private $currentTime = null;

	public function __construct() {
		$this -> identifier4Logger = __CLASS__;
	}

	public function init() {
		$this -> currentTime = time();
		//  30.04.2013, 23:59:59 = 1367359199;

		$this -> config = Config::getAll();

		// set group_concat_max_len to reasonable value to prevent cropping of article quantities list
		DBQuery::getInstance() -> Set('SET SESSION group_concat_max_len = 4096');

		// clear dailyneed db before start so there's no old leftover
		DBQuery::getInstance() -> truncate('TRUNCATE TABLE CalculatedDailyNeeds');
	}

	public function execute() {
		$this -> getLogger() -> debug(__FUNCTION__ . ' : CalculateHistogram');

		$this -> init();

		// retrieve latest orders from db for calculation time a
		$articleResultA = DBQuery::getInstance() -> select($this -> getIntervallQuery($this -> config['CalculationTimeA']['Value']));
		$this -> getLogger() -> info(__FUNCTION__ . ' : retrieved ' . $articleResultA -> getNumRows() . ' article variants for calculation time a');

		$articleData = array();
		// calculate data for calculation time a
		while ($currentArticle = $articleResultA -> fetchAssoc()) {
			$this -> processArticle($articleData, $currentArticle, 'A');
		}

		// retreive latest orders from db for calculation time b
		$articleResultB = DBQuery::getInstance() -> select($this -> getIntervallQuery($this -> config['CalculationTimeB']['Value']));
		$this -> getLogger() -> info(__FUNCTION__ . ' : retrieved ' . $articleResultB -> getNumRows() . ' article variants for calculation time b');
		// for every article in calculation time b do:
		// combine a and b
		while ($currentArticle = $articleResultB -> fetchAssoc()) {
			$this -> processArticle($articleData, $currentArticle, 'B');
		}

		$this -> storeToDB($articleData);

		$this -> getLogger() -> debug(__FUNCTION__ . ' : ... done');
	}

	private function storeToDB($articleData) {
		$result = array();
		foreach ($articleData as $sku => $current) {
			$result[] = "('{$current['ItemID']}','{$current['AttributeValueSetID']}','{$current['DailyNeed']}','{$current['LastUpdate']}','{$current['QuantitiesA']}','{$current['SkippedA']}','{$current['QuantitiesB']}','{$current['SkippedB']}')";
		}

		$query = 'REPLACE INTO `CalculatedDailyNeeds` (ItemID,AttributeValueSetID,DailyNeed,LastUpdate,QuantitiesA,SkippedA,QuantitiesB,SkippedB) VALUES' . implode(',', $result);
		DBQuery::getInstance() -> replace($query);
	}

	private function processArticle(&$articleData, $currentArticle, $AorB) {
		list($ItemID, $PriceID, $AttributeValueSetID) = SKU2Values($currentArticle['SKU']);
		$AorB = strtoupper($AorB);
		if ($AorB !== 'A' && $AorB !== 'B') {
			$this -> getLogger() -> info(__FUNCTION__ . ' : wrong syntax of $AorB : ' . $AorB);
			die();
		}

		$skippedIndex;
		$quantities = explode(',', $currentArticle['quantities']);
		$minToleratedSpikes = $this -> config['MinimumToleratedSpikes' . $AorB]['Value'];
		$minOrders = $this -> config['MinimumOrders' . $AorB]['Value'];
		$adjustedQuantity = $this -> getArticleAdjustedQuantity($quantities, $currentArticle['quantity'], $currentArticle['range'], $minToleratedSpikes, $minOrders, $skippedIndex);

		if ($AorB === 'A') {
			// add data for calculation time A
			// @formatter:off
			$articleData[$currentArticle['SKU']] = 
				array(
					'ItemID' => 				$ItemID,
					'AttributeValueSetID' =>	$AttributeValueSetID,
					'DailyNeed' =>				($adjustedQuantity / $this -> config['CalculationTimeA']['Value'])/2,
					'LastUpdate' =>				$this -> currentTime,
					'QuantitiesA' =>			$currentArticle['quantities'],
					'SkippedA' =>				$skippedIndex,
					'QuantitiesB' =>			0,
					'SkippedB' => 				0
				);
			// @formatter:on
		} else {
			// add data for calculation time B
			if (array_key_exists($currentArticle['SKU'], $articleData)) {
				// use existing record
				$articleData[$currentArticle['SKU']]['DailyNeed'] = ($adjustedQuantity / $this -> config['CalculationTimeB']['Value']) / 2 + $articleData[$currentArticle['SKU']]['DailyNeed'];
				$articleData[$currentArticle['SKU']]['QuantitiesB'] = $currentArticle['quantities'];
				$articleData[$currentArticle['SKU']]['SkippedB'] = $skippedIndex;
			} else {
				// create new one
				array('ItemID' => $ItemID, 'AttributeValueSetID' => $AttributeValueSetID, 'DailyNeed' => ($adjustedQuantity / $this -> config['CalculationTimeB']['Value']) / 2, 'LastUpdate' => $this -> currentTime, 'QuantitiesA' => 0, 'SkippedA' => 0, 'QuantitiesB' => $currentArticle['quantities'], 'SkippedB' => $skippedIndex);
			}
		}
	}

	private function getArticleAdjustedQuantity($quantities, $quantity, $range, $minToleratedSpikes, $minOrders, &$index) {

		$spikeTolerance = $this -> config['SpikeTolerance']['Value'];

		// skip all orders if # of orders is below given minimum ...
		if (count($quantities) < $minOrders) {
			$index = count($quantities);
			return 0;
		}

		// ... otherwise check quantities in descending order
		for ($index = 0; $index < count($quantities); ++$index) {

			// if we are already below the confidence range: stop the loop
			if ($quantities[$index] <= $range) {
				break;
			} else {
				// otherwise we need to check for tolerated spikes

				// get sub array
				$spikes = array_slice($quantities, $index, $minToleratedSpikes);

				// assume all spikes are in tolerance range
				$tolerateSpikes = true;

				// check subarray
				for ($spikeIndex = 1; $spikeIndex < count($spikes); ++$spikeIndex) {

					// if at least one element is below spike tolerance range:
					if ($spikes[$spikeIndex] < $spikes[0] * (1 - $spikeTolerance)) {

						// skip normative spike and break off the loop to try the next one...
						$quantity -= $quantities[$index];
						$tolerateSpikes = false;
						break;
					}
				}

				// found min. number of spike fitting in tolerance range, so all the rest is "in"
				if ($tolerateSpikes)
					break;
			}
		}

		return $quantity;
	}

	private function getIntervallQuery($daysBack) {
		$startTimestamp = $this -> currentTime;
		$rangeConfidenceMultiplyer = $this -> config['StandardDeviationFactor']['Value'];

		// @formatter:off
		return "SELECT
	OrderItem.ItemID,
	OrderItem.SKU,
	SUM(CAST(OrderItem.Quantity AS SIGNED)) AS `quantity`,
	AVG(`quantity`) + STDDEV(`quantity`) * $rangeConfidenceMultiplyer AS `range`,
	CAST(GROUP_CONCAT(IF(OrderItem.Quantity > 0 ,CAST(OrderItem.Quantity AS SIGNED),NULL) ORDER BY OrderItem.Quantity DESC SEPARATOR \",\") AS CHAR) AS `quantities`,
	ItemsBase.Marking1ID
FROM
	OrderItem
LEFT JOIN
	(OrderHead, ItemsBase) ON (OrderHead.OrderID = OrderItem.OrderID AND OrderItem.ItemID = ItemsBase.ItemID)
WHERE
	(OrderHead.OrderTimestamp BETWEEN $startTimestamp -( 86400 *  $daysBack ) AND $startTimestamp ) AND
	(OrderHead.OrderStatus < 8 OR OrderHead.OrderStatus >= 9) AND
	OrderType = \"order\" AND
	ItemsBase.Marking1ID IN (9,12,16,20) /* yellow, red, green, black */
GROUP BY
	OrderItem.SKU
ORDER BY
	ItemID".PHP_EOL;
		// @formatter:on
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