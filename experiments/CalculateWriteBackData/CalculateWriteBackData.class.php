<?php

require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'lib/db/DBQueryResult.class.php';
require_once ROOT . 'includes/SKUHelper.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * @author x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class CalculateWriteBackData {
	/**
	 *
	 * @var string
	 */
	private $identifier4Logger = '';

	/**
	 * @var array
	 */
	private $aArticleData;

	/**
	 * @return CalculateWriteBackData
	 */
	public function __construct() {
		$this -> identifier4Logger = __CLASS__;
		$this -> aArticleData = array();
	}

	/**
	 * calculate reorder level, minimum purchase and maximum stock suggestions for every article variant
	 *
	 * @return void
	 */
	public function execute() {
		$this -> getLogger() -> debug(__FUNCTION__ . ' : Calculating write back data');

		$dbResult = DBQuery::getInstance() -> select($this -> getQuery());

		// for every item variant ...
		while ($aCurrentArticleVariant = $dbResult -> fetchAssoc()) {
			$dailyNeed = floatval($aCurrentArticleVariant['DailyNeed']);
			$supplierDeliveryTime = intval($aCurrentArticleVariant['SupplierDeliveryTime']);
			$stockTurnover = intval($aCurrentArticleVariant['StockTurnover']);
			$vpe = intval($aCurrentArticleVariant['VPE']);
			$vpe = $vpe == 0 ? 1 : $vpe;
			$supplierMinimumPurchase = ceil($stockTurnover * $dailyNeed);
			$supplierMinimumPurchase = ($supplierMinimumPurchase % $vpe == 0) && ($supplierMinimumPurchase != 0) ? $supplierMinimumPurchase : $supplierMinimumPurchase + $vpe - $supplierMinimumPurchase % $vpe;

			$aResult = array('ItemID' => $aCurrentArticleVariant['ItemID'], 'AttributeValueSetID' => $aCurrentArticleVariant['AttributeValueSetID'], 'Valid' => 1);

			// if supplier delivery time given ...
			if ($supplierDeliveryTime !== 0) {
				// ... then calculate reorder level suggestion

				$aResult['ReorderLevel'] = round($supplierDeliveryTime * $dailyNeed);
				$aResult['ReorderLevelError'] = 'NULL';
			} else {
				// ... otherwise invalidate record

				$aResult['Valid'] = 0;
				$aResult['ReorderLevel'] = 'NULL';
				$aResult['ReorderLevelError'] = 'liefer';
			}

			// if stock turnover given ...
			if ($stockTurnover !== 0) {
				// ... then calculate supplier minimum purchase and maximum stock

				$aResult['SupplierMinimumPurchase'] = $supplierMinimumPurchase;
				$aResult['MaximumStock'] = 2 * $supplierMinimumPurchase;
				$aResult['SupplierMinimumPurchaseError'] = 'NULL';
			} else {
				// ... otherwise invalidate record

				$aResult['Valid'] = 0;
				$aResult['SupplierMinimumPurchase'] = 'NULL';
				$aResult['MaximumStock'] = 'NULL';
				$aResult['SupplierMinimumPurchaseError'] = 'lager';
			}
			$this -> aArticleData[] = $aResult;
		}

		$this->storeToDB();
	}

	/**
	 * stores article data to db
	 *
	 * @return void
	 */
	private function storeToDB(){
		DBQuery::getInstance() -> insert('INSERT INTO `WriteBackSuggestion`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this -> aArticleData));
	}

	/**
	 * prepare query for data necessary for calculation
	 *
	 * @return string query
	 */
	private function getQuery() {
		return 'SELECT
    ItemsBase.ItemID,
    ItemsBase.Free4 AS VPE,
    ItemSuppliers.SupplierDeliveryTime,
    CalculatedDailyNeeds.DailyNeed,
    ItemsWarehouseSettings.StockTurnover,
    CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
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
    AND CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
        "0"
    ELSE
        AttributeValueSets.AttributeValueSetID
    END = CalculatedDailyNeeds.AttributeValueSetID
LEFT JOIN ItemsWarehouseSettings
    ON ItemsBase.ItemID = ItemsWarehouseSettings.ItemID
    AND CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
        "0"
    ELSE
        AttributeValueSets.AttributeValueSetID
    END = ItemsWarehouseSettings.AttributeValueSetID' . PHP_EOL;
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