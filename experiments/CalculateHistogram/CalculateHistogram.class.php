<?php

require_once ROOT.'lib/db/DBQuery.class.php';
require_once ROOT.'lib/db/DBQueryResult.class.php';


/**
 * 1. retrteive data
 *
 * 2. stuff in histogram
 *
 * 3. show results
 *
 * @author x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class CalculateHistogram
{
	/**
	 *
	 * @var string
	 */
	private $identifier4Logger = '';

	public function __construct()
	{
		$this->identifier4Logger = __CLASS__;
	}

	public function execute()
	{
		$this->getLogger()->debug(__FUNCTION__.' : CalculateHistogram' );

		$currentTime = 1367359199;
		//	30.04.2013, 23:59:59

		$spikeTolerance = 0.1;

		// retreive latest orders from db
		$query = $this -> getIntervallQuery($currentTime, 90, 1.35);

		$articleResult = DBQuery::getInstance() -> select($query);

		// for every article do:
		while ($currentArticle = $articleResult -> fetchAssoc()) {

			$skipBeforeIndex = 0;
			$index;

			$quantities = explode(',', $currentArticle['quantities']);
			$nrOfQuantities = count($quantities);

			// check quantities in descending order
			for ($index = 0; $index < $nrOfQuantities; ++$index) {
				// if we are already below the confidence range: stop the loop
				if ($quantities[$index] <= $currentArticle['range'])
					break;

			}

			$this -> getLogger() -> debug(__FUNCTION__ . ' : Article: ' . $currentArticle['ItemID'] . ', skipping after index ' . $index);

		}
	}


	private function getIntervallQuery($startTimestamp, $daysBack, $rangeConfidenceMultiplyer) {
		return '
			SELECT
				OrderItem.ItemID,
				SUM(CAST(OrderItem.Quantity AS SIGNED)) AS `quantity`,
				(AVG(`quantity`) + STDDEV(`quantity`))*' . $rangeConfidenceMultiplyer . ' AS `range`,
				CAST(GROUP_CONCAT(IF(OrderItem.Quantity > 0 ,CAST(OrderItem.Quantity AS SIGNED),NULL) ORDER BY OrderItem.Quantity DESC SEPARATOR ",") AS CHAR) AS `quantities`,
				ItemsBase.Marking1ID FROM OrderItem LEFT JOIN (OrderHead, ItemsBase) ON (OrderHead.OrderID = OrderItem.OrderID AND OrderItem.ItemID = ItemsBase.ItemID)
			WHERE
				(OrderHead.OrderTimestamp BETWEEN ' . $startTimestamp . '-(86400*' . $daysBack . ') AND ' . $startTimestamp . ') AND
				(OrderHead.OrderStatus < 8 OR OrderHead.OrderStatus >= 9) AND
				OrderType = "order" AND
				ItemsBase.Marking1ID IN (9,12,16,20) /* yellow, red, green, black */
			GROUP BY
				OrderItem.ItemID
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
