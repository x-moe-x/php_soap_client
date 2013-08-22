<?php
class Request_GetItemsWarehouseSettings {

	public function getRequest($SKUList, $Warehouse) {
		$oPlentySoapRequest_GetItemsWarehouseSettings = new PlentySoapRequest_GetItemsWarehouseSettings();

		foreach ($SKUList as $SKU) {
			$oPlentySoapObject_RequestGetItemsWarehouseSettings = new PlentySoapObject_RequestGetItemsWarehouseSettings();
			$oPlentySoapObject_RequestGetItemsWarehouseSettings -> WarehouseID = $Warehouse;
			$oPlentySoapObject_RequestGetItemsWarehouseSettings -> SKU = $SKU;
			$oPlentySoapRequest_GetItemsWarehouseSettings -> ItemsList[] = $oPlentySoapObject_RequestGetItemsWarehouseSettings;
		}

		return $oPlentySoapRequest_GetItemsWarehouseSettings;
	}

}
?>