<?php
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'api/ApiAmazon.class.php';
require_once ROOT . 'includes/DBUtils2.class.php';
require_once ROOT . 'includes/SKUHelper.php';

/**
 * @author x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class CalculateAmazonQuantities {

	/**
	 * @var int
	 */
	const DAYS_BACK_INTERAL_BEFORE_PRICE_CHANGE = 30;

	/**
	 * @var string
	 */
	private $identifier4Logger;

	/**
	 * @var int
	 */
	private $currentTime;

	/**
	 * @var array
	 */
	private $aQuantities;

	/**
	 * @return CalculateAmazonQuantities
	 */
	public function __construct() {
		$this -> identifier4Logger = __CLASS__;

		$this -> currentTime = time();

		$this -> aQuantities = array();

		DBQuery::getInstance() -> truncate('TRUNCATE PriceUpdateQuantities');
	}

	/**
	 * @return void
	 */
	public function execute() {
		$amazonMeasuringTimeFrame = ApiAmazon::getConfig('MeasuringTimeFrame'); // value J

		// get all amazon quantities
		$calculatedAmazonQuantitiesDBResult = DBQuery::getInstance() -> select($this -> getQuery($amazonMeasuringTimeFrame, ApiAmazon::AMAZON_REFERRER_ID));
		while ($row = $calculatedAmazonQuantitiesDBResult -> fetchAssoc()) {
			list(, $priceID, ) = SKU2Values($row['SKU']);
			$this -> aQuantities[] = array('ItemID' => $row['ItemID'], 'PriceID' => $priceID, 'PriceColumn' => $row['PriceColumn'], 'OldQuantity' => $row['Quantity'], 'NewQuantity' => null);
		}

		$this -> storeToDB();
	}

	private function storeToDB() {
		DBQuery::getInstance() -> insert('INSERT INTO PriceUpdateQuantities' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this -> aQuantities));
	}

	/**
	 *
	 * @return Logger
	 */
	protected function getLogger() {
		return Logger::instance($this -> identifier4Logger);
	}

	/**
	 *
	 * @param int $daysBack
	 * @param int $referrerID
	 * @return string the query
	 */
	private function getQuery($daysBack, $referrerID) {
		return "SELECT
	i.ItemID,
	i.SKU,
	u.PriceColumn,
	SUM(i.Quantity) AS Quantity
FROM
	OrderItem AS i
LEFT JOIN
	OrderHead AS h
ON
	i.OrderID = h.OrderID
LEFT JOIN
	PriceUpdateHistory AS u
ON
	i.ItemID = u.ItemID
WHERE
	OrderType = \"order\"
AND
	CASE WHEN (u.WrittenTimestamp IS null) THEN
		(h.DoneTimestamp BETWEEN {$this -> currentTime} -( 86400 * $daysBack ) AND {$this -> currentTime})
	ELSE
		(h.DoneTimestamp BETWEEN u.WrittenTimestamp -( 86400 * $daysBack ) AND u.WrittenTimestamp )
	END
AND
	(h.OrderStatus < 8 OR h.OrderStatus >= 9)
AND
	h.ReferrerID = $referrerID
GROUP BY
	i.ItemID
ORDER BY
	i.ItemID ASC";
	}

}
?>
