<?php

require_once 'includes/basic_forward.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';

function getWarehouseList() {
	$query = 'SELECT * FROM `WarehouseList` ORDER BY WarehouseID ASC';
	$resultWarehouseList = DBQuery::getInstance() -> select($query);

	$result = array();
	while ($warehouse = $resultWarehouseList -> fetchAssoc()) {
		$result[$warehouse['WarehouseID']] = array('id' => $warehouse['WarehouseID'], 'name' => $warehouse['Name']);
	}
	return $result;
}

function getReorderSums() {
	$query = 'SELECT
	SUM(ItemSuppliers.ItemSupplierPrice * ItemsWarehouseSettings.ReorderLevel) AS currentReorderStock,
	SUM(ItemSuppliers.ItemSupplierPrice * WriteBackSuggestion.ReorderLevel) AS proposedReorderStock,
	SUM(ItemSuppliers.ItemSupplierPrice * ItemsWarehouseSettings.MaximumStock ) AS maxStock
FROM
	ItemSuppliers
LEFT JOIN
	ItemsWarehouseSettings
ON
	ItemSuppliers.ItemID = ItemsWarehouseSettings.ItemID
LEFT JOIN
	WriteBackSuggestion
ON
	ItemsWarehouseSettings.ItemID = WriteBackSuggestion.ItemID
AND
	ItemsWarehouseSettings.AttributeValueSetID = WriteBackSuggestion.AttributeValueSetID
LEFT JOIN
	WritePermissions
ON
	ItemsWarehouseSettings.ItemID = WritePermissions.ItemID
AND
	ItemsWarehouseSettings.AttributeValueSetID = WritePermissions.AttributeValueSetID
WHERE
	WritePermissions.WritePermission = 1';

	$resultReorderSums = DBQuery::getInstance() -> select($query);

	return $resultReorderSums -> fetchAssoc();
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
			$result .= "<li>ItemdID:{$badVariant['ItemID']} has misformed SupplierMinimumPurchase not equal 0</li>";
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
?>