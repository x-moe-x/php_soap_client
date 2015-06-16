<?php
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'api/ApiAmazon.class.php';
require_once ROOT . 'includes/DBUtils2.class.php';
require_once ROOT . 'includes/SKUHelper.php';

/**
 * @author    x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class CalculateAmazonQuantities
{
	/**
	 * @var int
	 */
	const DAYS_BACK_INTERVAL_BEFORE_PRICE_CHANGE = 30;

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
	private $quantities;

	/**
	 * @return CalculateAmazonQuantities
	 */
	public function __construct()
	{
		$this->identifier4Logger = __CLASS__;

		$this->currentTime = time();
		$this->quantities = array();

		DBQuery::getInstance()->truncate('TRUNCATE PriceUpdateQuantities');
	}

	/**
	 * @return void
	 */
	public function execute()
	{
		/** @var int $amazonMeasuringTimeFrame */
		$amazonMeasuringTimeFrame = ApiAmazon::getConfig('MeasuringTimeFrame');
		// value J

		// get all amazon pre-change-date quantities
		$preChangeDateQuantitiesDBResult = DBQuery::getInstance()->select($this->getQuery($amazonMeasuringTimeFrame, ApiAmazon::AMAZON_REFERRER_ID));
		while ($row = $preChangeDateQuantitiesDBResult->fetchAssoc())
		{
			list($itemId, $priceId, $attributeValueSetId) = SKU2Values($row['SKU']);

			$this->quantities[$row['SKU']] = array(
				'ItemID'              => $itemId,
				'AttributeValueSetID' => $attributeValueSetId,
				'PriceID'             => $priceId,
				'OldQuantity'         => ($row['Quantity'] / $amazonMeasuringTimeFrame) * self::DAYS_BACK_INTERVAL_BEFORE_PRICE_CHANGE,
				'NewQuantity'         => 0
			);
		}

		// get all amazon post-change-date quantities
		$postChangeDateQuantitiesDBResult = DBQuery::getInstance()->select($this->getQuery($amazonMeasuringTimeFrame, ApiAmazon::AMAZON_REFERRER_ID, $this->currentTime));
		while ($row = $postChangeDateQuantitiesDBResult->fetchAssoc())
		{
			list($itemId, $priceId, $attributeValueSetId) = SKU2Values($row['SKU']);
			if (array_key_exists($row['SKU'], $this->quantities))
			{
				$this->quantities[$row['SKU']]['NewQuantity'] = ($row['Quantity'] / $amazonMeasuringTimeFrame) * self::DAYS_BACK_INTERVAL_BEFORE_PRICE_CHANGE;
			} else
			{
				$this->quantities[$row['SKU']] = array(
					'ItemID'              => $itemId,
					'AttributeValueSetID' => $attributeValueSetId,
					'PriceID'             => $priceId,
					'OldQuantity'         => 0,
					'NewQuantity'         => ($row['Quantity'] / $amazonMeasuringTimeFrame) * self::DAYS_BACK_INTERVAL_BEFORE_PRICE_CHANGE
				);
			}
		}

		$this->storeToDB();
	}

	/**
	 *
	 * @param int       $daysBack
	 * @param int|float $referrerID *
	 * @param int       $fromTimeStamp
	 *
	 * @return string the query
	 */
	private function getQuery($daysBack, $referrerID, $fromTimeStamp = null)
	{
		if (is_null($fromTimeStamp))
		{
			$timingCondition = "CASE WHEN (u.WrittenTimestamp IS null) THEN
		(h.DoneTimestamp BETWEEN {$this->currentTime} -( 86400 * $daysBack ) AND {$this->currentTime})
	ELSE
		(h.DoneTimestamp BETWEEN u.WrittenTimestamp -( 86400 * $daysBack ) AND u.WrittenTimestamp )
	END";
		} else
		{
			$timingCondition = "(h.DoneTimestamp BETWEEN $fromTimeStamp -( 86400 * $daysBack ) AND $fromTimeStamp)";
		}

		return "SELECT
	i.ItemID,
	i.SKU,
	SUM(i.Quantity) AS Quantity,
	CAST(CAST(h.ReferrerID AS SIGNED) AS DECIMAL (8,2)) AS NormalizedReferrerID
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
	$timingCondition
AND
	(h.OrderStatus < 8 OR h.OrderStatus >= 9)
AND
	" . ApiHelper::getNormalizedReferrerCondition('h.ReferrerID', $referrerID) . "
GROUP BY
	i.SKU,
	NormalizedReferrerID
ORDER BY
	i.ItemID ASC";
	}

	private function storeToDB()
	{
		if (($quantitiesCount = count($this->quantities)) > 0)
		{
			$this->getLogger()->info(__FUNCTION__ . " : storing $quantitiesCount amazon quantity records");
			DBQuery::getInstance()->insert('INSERT INTO PriceUpdateQuantities' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->quantities));
		}
	}

	/**
	 *
	 * @return Logger
	 */
	protected function getLogger()
	{
		return Logger::instance($this->identifier4Logger);
	}

}
