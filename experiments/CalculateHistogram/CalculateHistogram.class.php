<?php

require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'lib/db/DBQueryResult.class.php';
require_once ROOT . 'includes/SKUHelper.php';

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
		$this -> currentTime = 1367359199;
		//  30.04.2013, 23:59:59

		$this -> config = $this -> getConfig();
	}

	public function execute() {
		$this -> getLogger() -> debug(__FUNCTION__ . ' : CalculateHistogram');

		$this -> init();

		// retreive latest orders from db
		$articleResult = DBQuery::getInstance() -> select($this -> getIntervallQuery());

		// for every article do:
		while ($currentArticle = $articleResult -> fetchAssoc()) {
			$this -> processArticle($currentArticle);
		}
	}

	private function getConfig() {
		$query = 'SELECT
                * FROM `MetaConfig`
                WHERE
                    `ConfigKey` = "CalculationTimeSingleWeighted" OR
                    `ConfigKey` = "CalcualtionTimeDoubleWeighted" OR
                    `ConfigKey` = "MinimumToleratedSpikes" OR
                    `ConfigKey` = "SpikeTolerance" OR
                    `ConfigKey` = "StandardDeviationFactor"';
		$resultConfigQuery = DBQuery::getInstance() -> select($query);

		$result = array();
		//TODO add validity check!
		for ($i = 0; $i < $resultConfigQuery -> getNumRows(); ++$i) {
			$configRow = $resultConfigQuery -> fetchAssoc();
			if ($configRow['ConfigKey'] == 'SpikeTolerance' || $configRow['ConfigKey'] == 'StandardDeviationFactor')
				$result[$configRow['ConfigKey']]['Value'] = floatval($configRow['ConfigValue']);
			else
				$result[$configRow['ConfigKey']]['Value'] = intval($configRow['ConfigValue']);

			$result[$configRow['ConfigKey']]['Active'] = intval($configRow['Active']);
		}
		return $result;
	}

    private function processArticle($currentArticle) {
        list($ItemID, $PriceID, $AttributeValueSetID) = SKU2Values($currentArticle['SKU']);

        $skippedIndex;
        $quantities = explode(',', $currentArticle['quantities']);
        $adjustedQuantity = $this -> getArticleAdjustedQuantity($quantities, $currentArticle['quantity'], $currentArticle['range'], $skippedIndex);

        // @formatter:off
            $this -> getLogger() -> info(__FUNCTION__ . ' : Article: ' .         $ItemID .
                                                         ', Set: ' .             $AttributeValueSetID .
                                                         ', skipped ' .          $skippedIndex . '/' . count($quantities) .
                                                         ', orders, total: ' .   $currentArticle['quantity'] .
                                                         ', adjusted: ' .        $adjustedQuantity .
                                                         ', difference: ' .      ($currentArticle['quantity'] - $adjustedQuantity) .
                                                         ', daily sale: ' .      $adjustedQuantity / 90);

        // store results to db
        $query = 'REPLACE INTO `CalculatedDailyNeeds` ' .
            DBUtils::buildInsert(
                array(
                    'ItemID'                =>  $ItemID,
                    'AttributeValueSetID'   =>  $AttributeValueSetID,
                    'DailyNeed'             =>  $adjustedQuantity / 90,
                    'LastUpdate'            =>  $this->currentTime
                )
            );
        // @formatter:on

        DBQuery::getInstance()->replace($query);
    }

	private function getArticleAdjustedQuantity($quantities, $quantity, $range, &$index) {

		$spikeTolerance = $this -> config['SpikeTolerance']['Value'];
		$minToleratedSpikes = $this -> config['MinimumToleratedSpikes']['Value'];

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

	private function getIntervallQuery() {
		$startTimestamp = $this -> currentTime;
		$daysBack = $this -> config['CalculationTimeSingleWeighted']['Value'];
		$rangeConfidenceMultiplyer = $this -> config['StandardDeviationFactor']['Value'];

		return '
			SELECT
				OrderItem.ItemID,
				OrderItem.SKU,
				SUM(CAST(OrderItem.Quantity AS SIGNED)) AS `quantity`,
				AVG(`quantity`) + STDDEV(`quantity`) * ' . $rangeConfidenceMultiplyer . ' AS `range`,
				CAST(GROUP_CONCAT(IF(OrderItem.Quantity > 0 ,CAST(OrderItem.Quantity AS SIGNED),NULL) ORDER BY OrderItem.Quantity DESC SEPARATOR ",") AS CHAR) AS `quantities`,
				ItemsBase.Marking1ID FROM OrderItem LEFT JOIN (OrderHead, ItemsBase) ON (OrderHead.OrderID = OrderItem.OrderID AND OrderItem.ItemID = ItemsBase.ItemID)
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
