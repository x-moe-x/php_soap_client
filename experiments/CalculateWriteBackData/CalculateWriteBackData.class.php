<?php

require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'lib/db/DBQueryResult.class.php';
require_once ROOT . 'includes/SKUHelper.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * @author    x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class CalculateWriteBackData
{
	/**
	 *
	 * @var string
	 */
	private $identifier4Logger = '';

	/**
	 * @var array
	 */
	private $articleData;

	/**
	 * @return CalculateWriteBackData
	 */
	public function __construct()
	{
		$this->identifier4Logger = __CLASS__;
		$this->articleData = array();
	}

	/**
	 * calculate reorder level, minimum purchase and maximum stock suggestions for every article variant
	 *
	 * @return void
	 */
	public function execute()
	{
		$this->getLogger()->debug(__FUNCTION__ . ' : Calculating write back data');

		$dbResult = DBQuery::getInstance()->select($this->getQuery());

		// for every item variant ...
		while ($currentArticleVariant = $dbResult->fetchAssoc())
		{
			$dailyNeed = floatval($currentArticleVariant['DailyNeed']);
			$supplierDeliveryTime = intval($currentArticleVariant['SupplierDeliveryTime']);
			$stockTurnover = intval($currentArticleVariant['StockTurnover']);
			$vpe = intval($currentArticleVariant['VPE']);
			$vpe = $vpe == 0 ? 1 : $vpe;
			$supplierMinimumPurchase = ceil($stockTurnover * $dailyNeed);
			$supplierMinimumPurchase = ($supplierMinimumPurchase % $vpe == 0) && ($supplierMinimumPurchase != 0) ? $supplierMinimumPurchase : $supplierMinimumPurchase + $vpe - $supplierMinimumPurchase % $vpe;

			$result = array('ItemID'              => $currentArticleVariant['ItemID'],
							 'AttributeValueSetID' => $currentArticleVariant['AttributeValueSetID'],
							 'Valid'               => 1
			);

			// if supplier delivery time given ...
			if ($supplierDeliveryTime !== 0)
			{
				// ... then calculate reorder level suggestion

				$result['ReorderLevel'] = round($supplierDeliveryTime * $dailyNeed);
				$result['ReorderLevelError'] = 'NULL';
			} else
			{
				// ... otherwise invalidate record

				$result['Valid'] = 0;
				$result['ReorderLevel'] = 'NULL';
				$result['ReorderLevelError'] = 'liefer';
			}

			// if stock turnover given ...
			if ($stockTurnover !== 0)
			{
				// ... then calculate supplier minimum purchase and maximum stock

				// ... but skip SupplierMinimumPurchase for article variants
				if (intval($currentArticleVariant['AttributeValueSetID']) === 0)
				{
					$result['SupplierMinimumPurchase'] = $supplierMinimumPurchase;
				} else
				{
					$result['SupplierMinimumPurchase'] = 0;
				}
				$result['MaximumStock'] = 2 * $supplierMinimumPurchase;
				$result['SupplierMinimumPurchaseError'] = 'NULL';
			} else
			{
				// ... otherwise invalidate record

				$result['Valid'] = 0;
				$result['SupplierMinimumPurchase'] = 'NULL';
				$result['MaximumStock'] = 'NULL';
				$result['SupplierMinimumPurchaseError'] = 'lager';
			}
			$this->articleData[] = $result;
		}

		$this->storeToDB();
	}

	/**
	 * stores article data to db
	 *
	 * @return void
	 */
	private function storeToDB()
	{
		DBQuery::getInstance()->insert('INSERT INTO `WriteBackSuggestion`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->articleData));
	}

	/**
	 * prepare query for data necessary for calculation
	 *
	 * @return string query
	 */
	private function getQuery()
	{
		return 'SELECT
    ItemsBase.ItemID,
    ItemsBase.Free4 AS VPE,
    ItemSuppliers.SupplierDeliveryTime,
    CalculatedDailyNeeds.DailyNeed,
    ItemsWarehouseSettings.StockTurnover,
    CASE WHEN (AttributeValueSets.AttributeValueSetID IS NULL) THEN
        "0"
    ELSE
        AttributeValueSets.AttributeValueSetID
    END AttributeValueSetID
FROM ItemsBase
LEFT JOIN AttributeValueSets
    ON ItemsBase.ItemID = AttributeValueSets.ItemID
LEFT JOIN ItemSuppliers
    ON ItemsBase.ItemID = ItemSuppliers.ItemID
LEFT JOIN CalculatedDailyNeeds
    ON ItemsBase.ItemID = CalculatedDailyNeeds.ItemID
    AND CASE WHEN (AttributeValueSets.AttributeValueSetID IS NULL) THEN
        "0"
    ELSE
        AttributeValueSets.AttributeValueSetID
    END = CalculatedDailyNeeds.AttributeValueSetID
LEFT JOIN ItemsWarehouseSettings
    ON ItemsBase.ItemID = ItemsWarehouseSettings.ItemID
    AND CASE WHEN (AttributeValueSets.AttributeValueSetID IS NULL) THEN
        "0"
    ELSE
        AttributeValueSets.AttributeValueSetID
    END = ItemsWarehouseSettings.AttributeValueSetID
WHERE
	ItemsBase.Inactive = 0' . PHP_EOL;
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
