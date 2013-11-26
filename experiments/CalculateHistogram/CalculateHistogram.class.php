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

		$this -> config = getConfig();
	}

	public function execute() {
		$this -> getLogger() -> debug(__FUNCTION__ . ' : CalculateHistogram');

		$this -> init();

		// retrieve latest orders from db for calculation time a
		$articleResultA = DBQuery::getInstance() -> select($this -> getIntervallQuery($this -> config['CalculationTimeA']['Value']));
		$this -> getLogger() -> info(__FUNCTION__ . ' : retrieved ' . $articleResultA -> getNumRows() . ' article variants for calculation time a');

		// clear pending db before start
		DBQuery::getInstance() -> truncate('TRUNCATE TABLE PendingCalculation');

		// store data to pending db
		while ($currentArticle = $articleResultA -> fetchAssoc()) {
			$this -> processArticle($currentArticle, true);
		}

		// retreive latest orders from db for calculation time b
		$articleResultB = DBQuery::getInstance() -> select($this -> getIntervallQuery($this -> config['CalculationTimeB']['Value']));
		$this -> getLogger() -> info(__FUNCTION__ . ' : retrieved ' . $articleResultB -> getNumRows() . ' article variants for calculation time b');
		// for every article in calculation time b do:
		// combine a and b and store to db
		while ($currentArticle = $articleResultB -> fetchAssoc()) {
			$this -> processArticle($currentArticle, false);
		}

		// for all articles NOT in calculation time b but still in pending do:
		$this -> processPendingArticles();
	}

	private function processPendingArticles() {
		// get remaining articles from pending:
		$pendingResult = DBQuery::getInstance() -> select('SELECT * FROM `PendingCalculation`');
		$this -> getLogger() -> info(__FUNCTION__ . ' : retrieved ' . $pendingResult -> getNumRows() . ' article variants from pending, being not in calculation time b');

		while ($pendingData = $pendingResult -> fetchAssoc()) {
			// store results to db
			// @formatter:off
	        $query = 'REPLACE INTO `CalculatedDailyNeeds` ' .
	            DBUtils::buildInsert(
	                array(
	                    'ItemID'                =>  $pendingData['ItemID'],
	                    'AttributeValueSetID'   =>  $pendingData['AttributeValueSetID'],
	                    'DailyNeed'             =>  $pendingData['DailyNeed']/2,
	                    'LastUpdate'            =>  $this->currentTime,
	                    'QuantitiesA'           =>  $pendingData['Quantities'],
	                    'SkippedA'              =>  $pendingData['Skipped'],
	                    'QuantitiesB'           =>  0,
	                    'SkippedB'              =>  0
	                )
	            );
			// @formatter:on

			DBQuery::getInstance() -> replace($query);
		}

		// delete obsolete pending data
		DBQuery::getInstance() -> truncate('TRUNCATE TABLE PendingCalculation');
	}

	private function processArticle($currentArticle, $storeToPending) {
		list($ItemID, $PriceID, $AttributeValueSetID) = SKU2Values($currentArticle['SKU']);

		$skippedIndex;
		$quantities = explode(',', $currentArticle['quantities']);
		$minToleratedSpikes = $this -> config['MinimumToleratedSpikes' . ($storeToPending ? 'A' : 'B')]['Value'];
		$adjustedQuantity = $this -> getArticleAdjustedQuantity($quantities, $currentArticle['quantity'], $currentArticle['range'], $minToleratedSpikes, $skippedIndex);

		if ($storeToPending) {
			// store results to pending db

			// @formatter:off
			$query = 'REPLACE INTO`PendingCalculation` ' . 
				DBUtils::buildInsert(array(
					'ItemID' => $ItemID,
					'AttributeValueSetID' => $AttributeValueSetID,
					'DailyNeed' => $adjustedQuantity / $this -> config['CalculationTimeA']['Value'],
					'Quantities' => $currentArticle['quantities'],
					'Skipped' => $skippedIndex
				)
			);
			// @formatter:on

			DBQuery::getInstance() -> replace($query);
		} else {
			// get data from pending db

			// @formatter:off
			$queryA = 'SELECT
							DailyNeed, Quantities, Skipped
						FROM
							`PendingCalculation`
						WHERE
							`ItemID` = ' . $ItemID . ' AND
							`AttributeValueSetID` =	' . $AttributeValueSetID;
			// @formatter:on

			$pendingResult = DBQuery::getInstance() -> select($queryA);

			// sanity check:
			if ($pendingResult -> getNumRows() != 1)
				throw new RuntimeException('Missformed pending data for ItemID:' . $ItemID . ' AVSI:' . $AttributeValueSetID . ' numRows:' . $pendingResult -> getNumRows());

			$pendingData = $pendingResult -> fetchAssoc();

			// store results to db
			// @formatter:off
	        $queryB = 'REPLACE INTO `CalculatedDailyNeeds` ' .
	            DBUtils::buildInsert(
	                array(
	                    'ItemID'                =>  $ItemID,
	                    'AttributeValueSetID'   =>  $AttributeValueSetID,
	                    'DailyNeed'             =>  (($adjustedQuantity / $this -> config['CalculationTimeB']['Value']) + $pendingData['DailyNeed'])/2,
	                    'LastUpdate'            =>  $this->currentTime,
	                    'QuantitiesA'           =>  $pendingData['Quantities'],
	                    'SkippedA'              =>  $pendingData['Skipped'],
	                    'QuantitiesB'           =>  $currentArticle['quantities'],
	                    'SkippedB'              =>  $skippedIndex
	                )
	            );
			// @formatter:on

			DBQuery::getInstance() -> replace($queryB);

			DBQuery::getInstance() -> delete('DELETE FROM `PendingCalculation` WHERE `ItemID` = ' . $ItemID . ' AND	`AttributeValueSetID` =	' . $AttributeValueSetID);
		}
	}

	private function getArticleAdjustedQuantity($quantities, $quantity, $range, $minToleratedSpikes, &$index) {

		$spikeTolerance = $this -> config['SpikeTolerance']['Value'];

		// check quantities in descending order
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

						// sḱip normative spike and break off the loop to try the next one...
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
		return '
			SELECT
				OrderItem.ItemID,
				OrderItem.SKU,
				SUM(CAST(OrderItem.Quantity AS SIGNED)) AS `quantity`,
				AVG(`quantity`) + STDDEV(`quantity`) * ' . $rangeConfidenceMultiplyer . ' AS `range`,
				CAST(GROUP_CONCAT(IF(OrderItem.Quantity > 0 ,CAST(OrderItem.Quantity AS SIGNED),NULL) ORDER BY OrderItem.Quantity DESC SEPARATOR ",") AS CHAR) AS `quantities`,
				ItemsBase.Marking1ID
			FROM
				OrderItem
			LEFT JOIN
				(OrderHead, ItemsBase) ON (OrderHead.OrderID = OrderItem.OrderID AND OrderItem.ItemID = ItemsBase.ItemID)
			WHERE
				(OrderHead.OrderTimestamp BETWEEN ' . $startTimestamp . '-(86400*' . $daysBack . ') AND ' . $startTimestamp . ') AND
				(OrderHead.OrderStatus < 8 OR OrderHead.OrderStatus >= 9) AND
				OrderType = "order" AND
				ItemsBase.Marking1ID IN (9,12,16,20) /* yellow, red, green, black */
			GROUP BY
				OrderItem.SKU
			ORDER BY
				ItemID
				';
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