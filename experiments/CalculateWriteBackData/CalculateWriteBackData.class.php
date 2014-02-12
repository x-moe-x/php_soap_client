<?php

require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'lib/db/DBQueryResult.class.php';
require_once ROOT . 'includes/SKUHelper.php';

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

	public function __construct() {
		$this -> identifier4Logger = __CLASS__;
	}

	public function execute() {
		$this -> getLogger() -> debug(__FUNCTION__ . ' : Calculating write back data ...');
		$dbResult = DBQuery::getInstance() -> select($this -> getQuery());

		$data = array();
		while ($row = $dbResult -> fetchAssoc()) {
			$dailyNeed = floatval($row['DailyNeed']);
			$supplierDeliveryTime = intval($row['SupplierDeliveryTime']);
			$stockTurnover = intval($row['StockTurnover']);
			$reorderLevel = round($supplierDeliveryTime * $dailyNeed);
			$vpe = intval($row['VPE']);
			$vpe = $vpe == 0 ? 1 : $vpe;
			$supplierMinimumPurchase = ceil($stockTurnover * $dailyNeed);
			$supplierMinimumPurchase = ($supplierMinimumPurchase % $vpe == 0) && ($supplierMinimumPurchase != 0) ? $supplierMinimumPurchase : $supplierMinimumPurchase + $vpe - $supplierMinimumPurchase % $vpe;
			$current = array();

			$current['ItemID'] = $row['ItemID'];
			$current['AttributeValueSetID'] = $row['AttributeValueSetID'];
			$current['Valid'] = 1;

			if ($supplierDeliveryTime !== 0) {
				$current['ReorderLevel'] = $reorderLevel;
				$current['ReorderLevelError'] = 'NULL';
			} else {
				$current['Valid'] = 0;
				$current['ReorderLevel'] = 'NULL';
				$current['ReorderLevelError'] = 'liefer';
			}
			if ($stockTurnover !== 0) {
				$current['SupplierMinimumPurchase'] = $supplierMinimumPurchase;
				$current['MaximumStock'] = 2 * $supplierMinimumPurchase;
				$current['SupplierMinimumPurchaseError'] = 'NULL';
			} else {
				$current['Valid'] = 0;
				$current['SupplierMinimumPurchase'] = 'NULL';
				$current['MaximumStock'] = 'NULL';
				$current['SupplierMinimumPurchaseError'] = 'lager';
			}
			$data[] = "('{$current['ItemID']}','{$current['AttributeValueSetID']}','{$current['Valid']}','{$current['ReorderLevel']}','{$current['SupplierMinimumPurchase']}','{$current['MaximumStock']}','{$current['ReorderLevelError']}','{$current['SupplierMinimumPurchaseError']}')";
		}
		$query = 'REPLACE INTO WriteBackSuggestion (ItemID,AttributeValueSetID,Valid,ReorderLevel,SupplierMinimumPurchase,MaximumStock,ReorderLevelError,SupplierMinimumPurchaseError) VALUES' . implode(',', $data);
		DBQuery::getInstance() -> replace($query);

		$this -> getLogger() -> debug(__FUNCTION__ . ' : ... done');
	}

	/**
	 *
	 * @return query for data necessary for calculation
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