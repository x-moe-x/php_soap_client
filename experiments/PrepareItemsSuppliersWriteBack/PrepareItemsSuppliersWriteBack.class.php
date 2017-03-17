<?php
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/NX_Executable.abstract.php';

/**
 * Class PrepareItemsSuppliersWriteBack
 */
class PrepareItemsSuppliersWriteBack extends NX_Executable {

	public function execute() {

		DBQuery::getInstance()
			->truncate("TRUNCATE SetItemsSuppliers");

		DBQuery::getInstance()
			->insert($this->getQuery());
	}

	private function getQuery() {
		return "INSERT INTO SetItemsSuppliers (ItemID, SupplierID, ItemSupplierRowID, IsRebateAllowed, ItemSupplierPrice, LastUpdate, Priority, Rebate, SupplierDeliveryTime, SupplierItemNumber, SupplierMinimumPurchase, VPE)
	SELECT
		ItemSuppliers.ItemID,
		ItemSuppliers.SupplierID,
		ItemSuppliers.ItemSupplierRowID,
		ItemSuppliers.IsRebateAllowed,
		ItemSuppliers.ItemSupplierPrice,
		ItemSuppliers.LastUpdate,
		ItemSuppliers.Priority,
		ItemSuppliers.Rebate,
		ItemSuppliers.SupplierDeliveryTime,
		ItemSuppliers.SupplierItemNumber,
		/* ItemSuppliers.SupplierMinimumPurchase, skipped, use suggestion instead */
		WriteBackSuggestion.SupplierMinimumPurchase,
		ItemSuppliers.VPE
	FROM
		`ItemSuppliers`
		LEFT JOIN
		`WritePermissions`
			ON
				ItemSuppliers.ItemID = WritePermissions.ItemID AND WritePermissions.AttributeValueSetID = 0
		LEFT JOIN
		`WriteBackSuggestion`
			ON
				ItemSuppliers.ItemID = WriteBackSuggestion.ItemID AND WriteBackSuggestion.AttributeValueSetID = 0
	WHERE
		WritePermissions.WritePermission = 1
		AND
		WritePermissions.AttributeValueSetID = 0
		AND ItemSuppliers.ItemSupplierRowID IS NOT NULL";
	}
}