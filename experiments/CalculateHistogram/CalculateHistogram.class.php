<?php

require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'lib/db/DBQueryResult.class.php';
require_once ROOT . 'includes/SKUHelper.php';
require_once ROOT . 'includes/GetConfig.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * @author x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class CalculateHistogram {

	/**
	 * @var string
	 */
	private $identifier4Logger = '';

	/**
	 * @var array
	 */
	private $aConfig;

	/**
	 * timestamp at construction time
	 * @var int
	 */
	private $currentTime;

	/** article data to be stored to db
	 * @var array
	 */
	private $aArticleData;

	/**
	 * @return CalculateHistogram
	 */
	public function __construct() {
		$this -> identifier4Logger = __CLASS__;

		$this -> currentTime = time();

		$this -> aArticleData = array();

		$this -> aConfig = Config::getAll();

		// set group_concat_max_len to reasonable value to prevent cropping of article quantities list
		DBQuery::getInstance() -> Set('SET SESSION group_concat_max_len = 4096');

		// clear dailyneed db before start so there's no old leftover
		DBQuery::getInstance() -> truncate('TRUNCATE TABLE CalculatedDailyNeeds');
	}

	/**
	 * calculate daily need for every article
	 *
	 * @return void
	 */
	public function execute() {

		// retrieve latest orders from db for calculation time a
		$articleResultA = DBQuery::getInstance() -> select($this -> getIntervalQuery($this -> aConfig['CalculationTimeA']['Value']));
		$articleResultAWithActivationDate = DBQuery::getInstance() -> select($this -> getIntervalQuery($this -> aConfig['CalculationTimeA']['Value'], true));
		$this -> getLogger() -> info(__FUNCTION__ . ' : retrieved ' . $articleResultA -> getNumRows() . ' article variants for calculation time a' . ($articleResultAWithActivationDate -> getNumRows() > 0 ? ' + ' . $articleResultAWithActivationDate -> getNumRows() . ' with activation date' : ''));

		// calculate data for calculation time a
		while ($aCurrentArticle = $articleResultA -> fetchAssoc()) {
			$this -> processArticle($aCurrentArticle, 'A');
		}
		while ($aCurrentArticle = $articleResultAWithActivationDate -> fetchAssoc()) {
			$this -> processArticle($aCurrentArticle, 'A', true);
		}

		// retrieve latest orders from db for calculation time b
		$articleResultB = DBQuery::getInstance() -> select($this -> getIntervalQuery($this -> aConfig['CalculationTimeB']['Value']));
		$articleResultBWithActivationDate = DBQuery::getInstance() -> select($this -> getIntervalQuery($this -> aConfig['CalculationTimeB']['Value'], true));
		$this -> getLogger() -> info(__FUNCTION__ . ' : retrieved ' . $articleResultB -> getNumRows() . ' article variants for calculation time b' . ($articleResultBWithActivationDate -> getNumRows() > 0 ? ' + ' . $articleResultBWithActivationDate -> getNumRows() . ' with activation date' : ''));

		// for every article in calculation time b do:
		// combine a and b
		while ($aCurrentArticle = $articleResultB -> fetchAssoc()) {
			$this -> processArticle($aCurrentArticle, 'B');
		}
		while ($aCurrentArticle = $articleResultBWithActivationDate -> fetchAssoc()) {
			$this -> processArticle($aCurrentArticle, 'B', true);
		}

		$this -> storeToDB();
	}

	/**
	 * store article data to db
	 *
	 * @return void
	 */
	private function storeToDB() {
		DBQuery::getInstance() -> insert('INSERT INTO `CalculatedDailyNeeds`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this -> aArticleData));
	}

	/**
	 * calculate spike cleared daily need and additional raw-data for current article combined from both calculation times as follows: dailyNeed = (dailyNeedA + dailyNeedB) / 2
	 *
	 * @param array $aCurrentArticle associative array of current article
	 * @param string $sAorB select calculation time a or b
	 * @param bool $withActivationDate (default) false, when articles with activation date are to be ignored, true otherwise	 *
	 * @return void
	 */
	private function processArticle(array $aCurrentArticle, $sAorB, $withActivationDate = false) {

		/** holds # of days to be considered during daily need computation */
		$daysBack = $this -> aConfig['CalculationTime' . $sAorB]['Value'];
		$isNewArticle = 0;

		// if activation date given ...
		if ($withActivationDate) {

			// ... potentially update article variant data (if date is in current period)
			$skipArticle = false;
			$this -> updateArticleOnActivationDate($aCurrentArticle, $daysBack, $isNewArticle, $skipArticle);

			if ($skipArticle) {
				return;
			}
		}

		/** current article's ItemID */
		$ItemID;

		/** current article's variant id (0 for non-variant articles) */
		$AttributeValueSetID;

		list($ItemID, , $AttributeValueSetID) = SKU2Values($aCurrentArticle['SKU']);

		/** holds # of skipped orders after adjusting quantity */
		$skippedIndex;

		/** spike-cleared total quantity */
		$adjustedQuantity = $this -> getArticleAdjustedQuantity(explode(',', $aCurrentArticle['quantities']), $aCurrentArticle['quantity'], $aCurrentArticle['range'], $this -> aConfig['MinimumToleratedSpikes' . $sAorB]['Value'], $this -> aConfig['MinimumOrders' . $sAorB]['Value'], $skippedIndex);

		// if processing period is a and article isn't new ...
		if ($sAorB === 'A') {
			// ... add data for calculation time A

			// @formatter:off
			$this->aArticleData[$aCurrentArticle['SKU']] = 
				array(
					'ItemID' => 				$ItemID,
					'AttributeValueSetID' =>	$AttributeValueSetID,
					'DailyNeed' =>				($adjustedQuantity / $daysBack) / 2,
					'LastUpdate' =>				$this -> currentTime,
					'QuantitiesA' =>			$aCurrentArticle['quantities'],
					'SkippedA' =>				$skippedIndex,
					'QuantitiesB' =>			'0',
					'SkippedB' => 				'0',
					'New'	=>					$isNewArticle
			);
			// @formatter:on
		}
		// ... or if period is b ...
		else if ($sAorB === 'B') {
			// ... add data for calculation time B

			// if there's an existing record ...
			if (array_key_exists($aCurrentArticle['SKU'], $this -> aArticleData)) {
				// ... then use existing record
				$this -> aArticleData[$aCurrentArticle['SKU']]['DailyNeed'] += ($adjustedQuantity / $daysBack) / 2;
				$this -> aArticleData[$aCurrentArticle['SKU']]['QuantitiesB'] = $aCurrentArticle['quantities'];
				$this -> aArticleData[$aCurrentArticle['SKU']]['SkippedB'] = $skippedIndex;
			} else {
				// ... otherwise create new one

				// @formatter:off
				$this->aArticleData[$aCurrentArticle['SKU']] =
					array(
						'ItemID' =>					$ItemID,
						'AttributeValueSetID' =>	$AttributeValueSetID,
						'DailyNeed' =>				$adjustedQuantity / $daysBack,
						'LastUpdate' =>				$this -> currentTime,
						'QuantitiesA' =>			'0',
						'SkippedA' =>				'0',
						'QuantitiesB' =>			$aCurrentArticle['quantities'],
						'SkippedB' =>				$skippedIndex,
						'New'	=>					$isNewArticle
				);
				// @formatter:on
			}
		} else {
			$this -> getLogger() -> error(__FUNCTION__ . ' : wrong syntax of $sAorB : ' . $sAorB);
			die();
		}
	}

	private function updateArticleOnActivationDate(&$aCurrentArticle, &$daysBack, &$isNewArticle, &$skipArticle) {
		// calculate (now - ActivationDate) in days
		$activationTimeDifference = $this -> getActivationTimeDifference($aCurrentArticle['ActivationDate']);

		// check if a date is given which doesn't match ...
		if (is_null($activationTimeDifference)) {
			// ... report error and skip article
			$this -> getLogger() -> debug(__FUNCTION__ . " article {$aCurrentArticle['SKU']} has misformed activation date: {$aCurrentArticle['ActivationDate']}");
			$skipArticle = true;
		}
		// ... or if activation date is in the future ...
		else if ($activationTimeDifference < 0) {
			// ... then no further processing is needed, skip article
			$skipArticle = true;
		}
		// ... or if date is in current calculation period ...
		else if ($activationTimeDifference < $daysBack && $activationTimeDifference < $this -> aConfig['CalculationTimeA']['Value']) {
			// ... then adjust calculation period
			$daysBack = $activationTimeDifference;
			$isNewArticle = 1;

			$currentArticleResult = DBQuery::getInstance() -> select($this -> getIntervalQuery($daysBack, true, $aCurrentArticle['SKU']));
			// ... if there's any data for the adjusted period
			if ($aSpecificArticle = $currentArticleResult -> fetchAssoc()) {
				// ... then update the current article
				$aCurrentArticle = $aSpecificArticle;
			} else {
				// ... otherwise skip the article
				$skipArticle = true;
			}
		}
	}

	/**
	 * compute an adjusted total quantity for given quantities (current article) which is cleared of untolerated spikes
	 *
	 * @param array $aQuantities Array of quantities for current article to discard all spikes from
	 * @param int $quantity total quantity of the current article
	 * @param int $range range for quantities, quantities above this range have to be checked for tolerated spikes
	 * @param int $minToleratedSpikes minimum # of spikes that can be tolerated
	 * @param int $minOrders minimum # of orders necessary to consider the current article
	 * @param int $index return value for the # of skipped orders
	 * @return total quantity minus discarded spikes
	 */
	private function getArticleAdjustedQuantity(array $aQuantities, $quantity, $range, $minToleratedSpikes, $minOrders, &$index) {
		// skip all orders if # of orders is below given minimum ...
		if (count($aQuantities) < $minOrders) {
			$index = count($aQuantities);
			return 0;
		}

		$spikeTolerance = Config::get('SpikeTolerance');

		// ... otherwise check quantities in descending order
		for ($index = 0, $maxQuantities = count($aQuantities); $index < $maxQuantities; ++$index) {

			// if we are already below the confidence range ...
			if ($aQuantities[$index] <= $range) {
				// ... then stop the loop
				break;
			} else {
				// ... otherwise we need to check for tolerated spikes

				// get sub array
				$aSpikes = array_slice($aQuantities, $index, $minToleratedSpikes);

				// assume all spikes are in tolerance range
				$tolerateSpikes = true;

				// check subarray
				for ($spikeIndex = 1, $maxSpikes = count($aSpikes); $spikeIndex < $maxSpikes; ++$spikeIndex) {

					// if at least one element is below spike tolerance range ...
					if ($aSpikes[$spikeIndex] < $aSpikes[0] * (1 - $spikeTolerance)) {

						// ... then skip spike and break off the loop to try the next one...
						$quantity -= $aQuantities[$index];
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

	/**
	 * @param string $sActivationDate
	 * @return int|null
	 */
	private function getActivationTimeDifference($sActivationDate) {

		// check if activationdate given (match against regular german date format like dd.mm.yyyy, tolerating -:/. as delimiter) ...
		if (preg_match('/(((?:[0-2]?\d{1})|(?:[3][01]{1}))[-:\/.]([0]?[1-9]|[1][012])[-:\/.]((?:[1]{1}\d{1}\d{1}\d{1})|(?:[2]{1}\d{3})))(?![\d])/', $sActivationDate, $matches)) {

			// ... then return difference to current time in days
			$date = new DateTime();
			$date -> setDate($matches[4], $matches[3], $matches[2]);
			$date -> setTime(0, 0, 0);

			return floor(($this -> currentTime - $date -> format('U')) / 86400);
		} else {
			// ... otherwise return null
			return null;
		}
	}

	/**
	 * prepare query to get total quantities and spike-toleration-data for all articles from db
	 *
	 * @param int $daysBack # of days to consider when collecting order data
	 * @param bool $withActivationDate (default) false, when articles with activation date are to be ignored, true otherwise
	 * @param string $sku (default) null to get all articles, a specific SKU to get just the specific article's variants if $withActivationDate is true
	 * @return string query
	 */
	private function getIntervalQuery($daysBack, $withActivationDate = false, $sku = null) {

		/** holds SQL statement part to adjust query for activation date processing */
		$activationDateString;

		if ($withActivationDate && !isset($sku)) {
			// get all articles with given activation date
			$activationDateString = "ItemsBase.Free5 > \"\"";
		} else if ($withActivationDate && isset($sku)) {
			// get a specific article (with all of it's variants) with given activation date
			$activationDateString = "OrderItem.SKU = \"$sku\"\nAND\n\tItemsBase.Free5 > \"\"";
		} else {
			// get all articles without activation date
			$activationDateString = "\n\tItemsBase.Free5 = \"\"";
		}

		return "SELECT
	OrderItem.ItemID,
	OrderItem.SKU,
	SUM(CAST(OrderItem.Quantity AS SIGNED)) AS `quantity`,
	AVG(`quantity`) + STDDEV(`quantity`) * {$this->aConfig['StandardDeviationFactor']['Value']} AS `range`,
	CAST(GROUP_CONCAT(IF(OrderItem.Quantity > 0 ,CAST(OrderItem.Quantity AS SIGNED),NULL) ORDER BY OrderItem.Quantity DESC SEPARATOR \",\") AS CHAR) AS `quantities`,
	ItemsBase.Marking1ID,
	ItemsBase.Free5 AS ActivationDate
FROM
	OrderItem
LEFT JOIN
	(OrderHead, ItemsBase) ON (OrderHead.OrderID = OrderItem.OrderID AND OrderItem.ItemID = ItemsBase.ItemID)
WHERE
	$activationDateString
AND
	(OrderHead.OrderTimestamp BETWEEN {$this -> currentTime} -( 86400 *  $daysBack ) AND {$this -> currentTime} )
AND
	(OrderHead.OrderStatus < 8 OR OrderHead.OrderStatus >= 9)
AND
	OrderType = \"order\"
AND
	ItemsBase.Inactive = 0
GROUP BY
	OrderItem.SKU
ORDER BY
	ItemID" . PHP_EOL;
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