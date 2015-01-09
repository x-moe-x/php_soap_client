<?php
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/DBLastUpdate.php';
require_once ROOT . 'includes/EanGenerator.class.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * @author x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class JansenStockMatchForUpdate {

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
	const DEFAULT_REASON = 0;

	/**
	 * @return JansenStockMatchForUpdate
	 */
	public function __construct() {
		$this -> identifier4Logger = __CLASS__;

		$this -> aMatchedItemVariants = array();

		$this -> aUnmatchedItemVariants = array();
	}

	/**
	 * @return void
	 */
	public function execute() {
		// get all item variants with jansen EAN already matched against jansen data
		$itemVariantsDBResult = DBQuery::getInstance() -> select($this -> getQuery());

		$this -> getLogger() -> debug(__FUNCTION__ . ': found ' . $itemVariantsDBResult -> getNumRows() . ' item variants with jansen ean...');

		// for every item variant ...
		while ($itemVariant = $itemVariantsDBResult -> fetchAssoc()) {
			// ... check if match is ok
			if (!is_null($itemVariant['EAN']) && !is_null($itemVariant['ExternalItemID'])) {
				// ... handle matched item variant
				if ($itemVariant['PhysicalStock'] !== $itemVariant['OldPhysicalStock']) {
					//@formatter:off
					$this->aMatchedItemVariants[] = array(
						'ItemID' =>					$itemVariant['ItemID'],
						'AttributeValueSetID' =>	$itemVariant['AttributeValueSetID'],
						'PriceID' =>				$itemVariant['PriceID'],
						'WarehouseID' =>			self::JANSEN_WAREHOUSE_ID,
						'StorageLocation' =>		self::DEFAULT_STORAGE_LOCATION,
						'PhysicalStock' =>			$itemVariant['PhysicalStock'],
						'Reason' =>					self::DEFAULT_REASON
					);
					//@formatter:on
				}
			} else {
				// ... handle not matched item variants

				//TODO remove after debugging
				//@formatter:off
				$this->aUnmatchedItemVariants[] = array(
					'ItemID' =>					$itemVariant['ItemID'],
					'AttributeValueSetID' =>	$itemVariant['AttributeValueSetID']
				);
				//@formatter:on

				// ... if not already zero stock ...
				if ($itemVariant['OldPhysicalStock'] !== 0) {
					// ... assume physical stock of zero
					//@formatter:off
					$this->aMatchedItemVariants[] = array(
						'ItemID' =>					$itemVariant['ItemID'],
						'AttributeValueSetID' =>	$itemVariant['AttributeValueSetID'],
						'PriceID' =>				$itemVariant['PriceID'],
						'WarehouseID' =>			self::JANSEN_WAREHOUSE_ID,
						'StorageLocation' =>		self::DEFAULT_STORAGE_LOCATION,
						'PhysicalStock' =>			0,
						'Reason' =>					self::DEFAULT_REASON
					);
					//@formatter:on
				}
			}
		}

		$this -> storeToDB();
	}

	private function getQuery() {
		return "SELECT
	i.ItemID,
	CASE WHEN (avs.AttributeValueSetID IS NULL) THEN
			0
		ELSE
			avs.AttributeValueSetID
	END AS AttributeValueSetID,
	ps.PriceID,
	jsd.EAN,
	jsd.ExternalItemID,
	jsd.PhysicalStock,
	cs.PhysicalStock AS OldPhysicalStock
FROM
	ItemsBase AS i
LEFT JOIN
	AttributeValueSets AS avs
ON
	i.ItemID = avs.ItemID
LEFT JOIN
	CurrentStocks AS cs
ON
	i.ItemID = cs.ItemID
AND
	CASE WHEN (avs.AttributeValueSetID IS NULL) THEN
			0
		ELSE
			avs.AttributeValueSetID
	END = cs.AttributeValueSetID
AND
	cs.WarehouseID = " . self::JANSEN_WAREHOUSE_ID . "
LEFT JOIN
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
AND
	LOWER(
		CASE WHEN (avs.AttributeValueSetID IS NULL) THEN
				i.ExternalItemID
			ELSE
				CASE WHEN (avs.AttributeValueSetID = 1) THEN
					REPLACE(i.ExternalItemID,' [R/G] ','G')
				WHEN (avs.AttributeValueSetID = 2) THEN
					REPLACE(i.ExternalItemID,' [R/G] ','R')
				WHEN (avs.AttributeValueSetID = 23) THEN
					REPLACE(i.ExternalItemID,'+[Color]','RED')
				WHEN (avs.AttributeValueSetID = 24) THEN
					REPLACE(i.ExternalItemID,'+[Color]','YELLOW')
				WHEN (avs.AttributeValueSetID = 25) THEN
					REPLACE(i.ExternalItemID,'+[Color]','PURPLE')
				WHEN (avs.AttributeValueSetID = 26) THEN
					REPLACE(i.ExternalItemID,'+[Color]','WHITE')
				WHEN (avs.AttributeValueSetID = 27) THEN
					REPLACE(i.ExternalItemID,'+[Color]','PINK')
				WHEN (avs.AttributeValueSetID = 28) THEN
					REPLACE(i.ExternalItemID,'+[Color]','DARKBLUE')
				WHEN (avs.AttributeValueSetID = 29) THEN
					REPLACE(i.ExternalItemID,'+[Color]','DARKGREEN')
				WHEN (avs.AttributeValueSetID = 30) THEN
					REPLACE(i.ExternalItemID,'+[Color]','ORANGE')
				ELSE
					'xxx'
				END
		END
	) = LOWER(jsd.ExternalItemID)
WHERE
	CASE WHEN (avs.AttributeValueSetID IS NULL) THEN
			i.EAN2
		ELSE
			avs.EAN2
	END BETWEEN 8595578300000 AND 8595578399999
AND
	i.Marking1ID != 4
AND
	i.Inactive = 0";
	}

	private function storeToDB() {
		$countMatched = count($this -> aMatchedItemVariants);
		$countUnMatched = count($this -> aUnmatchedItemVariants);

		if ($countMatched > 0 || $countUnMatched > 0) {
			DBQuery::getInstance() -> truncate('TRUNCATE SetCurrentStocks');
			DBQuery::getInstance() -> truncate('TRUNCATE JansenStockUnmatched');
			DBQuery::getInstance() -> insert('INSERT INTO SetCurrentStocks' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this -> aMatchedItemVariants));
			DBQuery::getInstance() -> insert('INSERT INTO JansenStockUnmatched' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this -> aUnmatchedItemVariants));
			//TODO update CurrentStocks.PhysicalStock (also lastupdate?)

			$this -> getLogger() -> debug(__FUNCTION__ . ": storing $countMatched matched stock records for update and $countUnMatched records for analysis");
		}
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
