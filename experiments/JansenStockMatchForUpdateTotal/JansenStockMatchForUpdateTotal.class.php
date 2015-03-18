<?php
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/DBLastUpdate.php';
require_once ROOT . 'includes/EanGenerator.class.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * @author    x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class JansenStockMatchForUpdateTotal
{
	/**
	 * @var int
	 */
	const JANSEN_WAREHOUSE_ID = 2;

	/**
	 * @var int
	 */
	const DEFAULT_STORAGE_LOCATION = 0;

	/**
	 * @var int
	 */
	const DEFAULT_REASON = 301;

	/**
	 * @var string
	 */
	private $identifier4Logger;

	/**
	 * @var array
	 */
	private $aMatchedItemVariants;

	/**
	 * @var array
	 */
	private $aUnmatchedItemVariants;

	/**
	 * @return JansenStockMatchForUpdateTotal
	 */
	public function __construct()
	{
		$this->identifier4Logger = __CLASS__;

		$this->aMatchedItemVariants = array();
		$this->aUnmatchedItemVariants = array();
	}

	/**
	 * @return void
	 */
	public function execute()
	{
		/*
		 * get all item variants with jansen EAN already matched against
		 * jansen data
		 */
		$itemVariantsDBResult = DBQuery::getInstance()->select($this->getMatchedQuery());

		$this->getLogger()->debug(__FUNCTION__ . ': found ' . $itemVariantsDBResult->getNumRows() . ' item variants with jansen ean that received an update...');

		// for every item variant ...
		while ($itemVariant = $itemVariantsDBResult->fetchAssoc())
		{
			// ... handle matched item variant

			$this->aMatchedItemVariants[] = array(
				'ItemID'              => $itemVariant['ItemID'],
				'AttributeValueSetID' => $itemVariant['AttributeValueSetID'],
				'PriceID'             => $itemVariant['PriceID'],
				'WarehouseID'         => self::JANSEN_WAREHOUSE_ID,
				'StorageLocation'     => self::DEFAULT_STORAGE_LOCATION,
				'PhysicalStock'       => $itemVariant['PhysicalStock'],
				'Reason'              => self::DEFAULT_REASON
			);
		}

		$unmatchedItemVariantsDBResult = DBQuery::getInstance()->select($this->getUnmatchedQuery());

		$this->getLogger()->debug(__FUNCTION__ . ': found ' . $unmatchedItemVariantsDBResult->getNumRows() . ' item variants with jansen ean that didn\'t match...');

		// for every item variant ...
		while ($itemVariant = $unmatchedItemVariantsDBResult->fetchAssoc())
		{
			// ... handle matched item variant

			$this->aUnmatchedItemVariants[] = array(
				'ItemID'              => $itemVariant['ItemID'],
				'AttributeValueSetID' => $itemVariant['AttributeValueSetID']
			);
		}

		$this->storeToDB();
	}

	/**
	 * @return string the query string
	 */
	private function getMatchedQuery()
	{
		return "SELECT
	nx.ItemID,
	nx.AttributeValueSetID,
	nx.PriceID,
	jsd.PhysicalStock
FROM
	JansenStockData AS jsd
	JOIN /* get all nx products (itemID, avsID, priceID, EAN, extID) with a jansen ean, which is active and not marked */
	(
		SELECT
			i.ItemID,
			CASE WHEN (avs.AttributeValueSetID IS NULL) THEN
				0
			ELSE
				avs.AttributeValueSetID
			END AS AttributeValueSetID,
			ps.PriceID,
			CASE WHEN (avs.AttributeValueSetID IS NULL) THEN
				i.EAN2
			ELSE
				avs.EAN2
			END AS EAN,
			i.ExternalItemID
		FROM
			ItemsBase AS i
			LEFT JOIN
			AttributeValueSets AS avs
				ON
					i.ItemID = avs.ItemID
			JOIN
			PriceSets AS ps
				ON
					i.ItemID = ps.ItemID
		WHERE
			CASE WHEN (avs.AttributeValueSetID IS NULL) THEN
				i.EAN2
			ELSE
				avs.EAN2
			END BETWEEN 8595578300000 AND 8595578399999
			AND
			i.Marking1ID != 4
			AND
			i.Inactive = 0
	) AS nx
		ON
			(jsd.EAN = nx.EAN)
			AND
			LOWER(
					CASE WHEN (nx.AttributeValueSetID = 0) THEN
						nx.ExternalItemID
					ELSE
						CASE WHEN (nx.AttributeValueSetID = 1) THEN
							REPLACE(nx.ExternalItemID, ' [R/G] ', 'G')
						WHEN (nx.AttributeValueSetID = 2) THEN
							REPLACE(nx.ExternalItemID, ' [R/G] ', 'R')
						WHEN (nx.AttributeValueSetID = 23) THEN
							REPLACE(nx.ExternalItemID, '+[Color]', 'RED')
						WHEN (nx.AttributeValueSetID = 24) THEN
							REPLACE(nx.ExternalItemID, '+[Color]', 'YELLOW')
						WHEN (nx.AttributeValueSetID = 25) THEN
							REPLACE(nx.ExternalItemID, '+[Color]', 'PURPLE')
						WHEN (nx.AttributeValueSetID = 26) THEN
							REPLACE(nx.ExternalItemID, '+[Color]', 'WHITE')
						WHEN (nx.AttributeValueSetID = 27) THEN
							REPLACE(nx.ExternalItemID, '+[Color]', 'PINK')
						WHEN (nx.AttributeValueSetID = 28) THEN
							REPLACE(nx.ExternalItemID, '+[Color]', 'DARKBLUE')
						WHEN (nx.AttributeValueSetID = 29) THEN
							REPLACE(nx.ExternalItemID, '+[Color]', 'DARKGREEN')
						WHEN (nx.AttributeValueSetID = 30) THEN
							REPLACE(nx.ExternalItemID, '+[Color]', 'ORANGE')
						ELSE
							'xxx'
						END
					END
			) = LOWER(jsd.ExternalItemID)";
	}

	/**
	 *
	 * @return Logger
	 */
	protected function getLogger()
	{
		return Logger::instance($this->identifier4Logger);
	}

	/**
	 * @return string the query string
	 */
	private function getUnmatchedQuery()
	{
		return "SELECT
	i.ItemID,
	CASE WHEN (avs.AttributeValueSetID IS NULL) THEN
			0
		ELSE
			avs.AttributeValueSetID
	END AS AttributeValueSetID,
	jsd.EAN IS NOT NULL AS IsEanMatched
FROM
	ItemsBase AS i
LEFT JOIN
	AttributeValueSets AS avs
ON
	i.ItemID = avs.ItemID
JOIN
	PriceSets AS ps
ON
	i.ItemID = ps.ItemID
LEFT JOIN
	JansenStockData AS jsd
ON
	CASE WHEN (avs.AttributeValueSetID IS NULL) THEN
			i.EAN2
		ELSE
			avs.EAN2
	END = jsd.EAN
WHERE
	CASE WHEN (avs.AttributeValueSetID IS NULL) THEN
			i.EAN2
		ELSE
			avs.EAN2
	END BETWEEN 8595578300000 AND 8595578399999
AND
	i.Marking1ID != 4
AND
	i.Inactive = 0
AND
	jsd.EAN IS NULL";
	}

	private function storeToDB()
	{
		$countMatched = count($this->aMatchedItemVariants);
		$countUnmatched = count($this->aUnmatchedItemVariants);

		if ($countMatched > 0)
		{
			DBQuery::getInstance()->truncate('TRUNCATE SetCurrentStocks');
			DBQuery::getInstance()->insert('INSERT INTO SetCurrentStocks' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->aMatchedItemVariants));

			$this->getLogger()->debug(__FUNCTION__ . ": storing $countMatched matched stock records for update.");
		}

		if ($countUnmatched > 0)
		{
			DBQuery::getInstance()->truncate('TRUNCATE JansenStockUnmatched');
			DBQuery::getInstance()->insert('INSERT INTO JansenStockUnmatched' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->aUnmatchedItemVariants));

			$this->getLogger()->debug(__FUNCTION__ . ": storing $countUnmatched unmatched stock records.");
		}
	}
}
