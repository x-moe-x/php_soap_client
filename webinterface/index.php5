<?php
ob_start();
// prevent verbose functions from tainting output

require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/GetConfig.php';

require ('smarty/libs/Smarty.class.php');
$smarty = new Smarty();

function getWarehouseList() {
	$query = 'SELECT * FROM `WarehouseList`';
	$resultWarehouseList = DBQuery::getInstance() -> select($query);

	$result = array();
	while ($warehouse = $resultWarehouseList -> fetchAssoc()) {
		$result[] = array('id' => $warehouse['WarehouseID'], 'name' => $warehouse['Name']);
	}
	return $result;
}

function checkBadVariants() {
	$query = 'SELECT
	ItemsBase.ItemID,
	ItemSuppliers.SupplierMinimumPurchase
FROM
	ItemsBase
LEFT JOIN AttributeValueSets
	ON ItemsBase.ItemID = AttributeValueSets.ItemID
LEFT JOIN ItemSuppliers
	ON ItemsBase.ItemID = ItemSuppliers.ItemID
WHERE
	ItemSuppliers.SupplierMinimumPurchase != 0
AND
	AttributeValueSets.ItemID != 0
GROUP BY
	ItemsBase.ItemID
';

	$badVariants = DBQuery::getInstance() -> select($query);
	if ($badVariants -> getNumRows() != 0) {
		$result = '<ul>';
		while ($badVariant = $badVariants -> fetchAssoc()) {
			$result .= "<li>ItemdID:{$badVariant['ItemID']} has misformed SupplierMinimumPurchase ≠ 0</li>";
		}
		return $result;
	}
}

function checkFailedOrders() {
	$query = '
        select
            *
        from
            `FailedOrderIDRange`';
	$failedOrderRanges = DBQuery::getInstance() -> select($query);
	if ($failedOrderRanges -> getNumRows() != 0) {
		$result = '<ul>';
		while ($failedOrderRange = $failedOrderRanges -> fetchAssoc()) {
			$result .= '<li>' . $failedOrderRange['Reason'] . ' appeared at ' . $failedOrderRange['FromOrderID'] . ' for the next ' . $failedOrderRange['CountOrders'] . ' orders</li>';
		}
		$result .= '</ul>';
		return $result;
	}
}

function checkItemSupplierConfiguration() {
	$query = '
		select
			ItemsBase.ItemID,
			ItemsBase.Name,
			count(ItemSuppliers.ItemID) as counted
		from
			`ItemsBase`
		left join
			`ItemSuppliers`
		on
			ItemsBase.ItemID = ItemSuppliers.ItemID OR
			ItemSuppliers.ItemID = null
		where
			ItemsBase.Marking1ID != 4
		group by
			ItemsBase.ItemID
		having
			counted != 1';
	$misformedItemSuppliers = DBQuery::getInstance() -> select($query);
	if ($misformedItemSuppliers -> getNumRows() != 0) {
		$result = '<ul>';
		while ($misformedItemSupplier = $misformedItemSuppliers -> fetchAssoc()) {
			$result .= '<li>ItemID: ' . $misformedItemSupplier['ItemID'] . ' - ' . $misformedItemSupplier['Name'] . ' has ' . $misformedItemSupplier['counted'] . ' suppliers configured!</li>';
		}
		$result .= '</ul>';
		return $result;
	}
}

$smarty -> setTemplateDir('smarty/templates');
$smarty -> setCompileDir('smarty/templates_c');
$smarty -> setCacheDir('smarty/cache');
$smarty -> setConfigDir('smarty/configs');

$smarty -> assign('warehouseList', getWarehouseList());
$smarty -> assign('config', Config::getAll());
$smarty -> assign('debug', ob_get_clean() . checkItemSupplierConfiguration() . checkFailedOrders(). checkBadVariants());
// make function output available if needed
$smarty -> display('index.tpl');
?>
