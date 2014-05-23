<?php
class Request_GetItemsWarehouseSettings {

	/**
	 * @var array Array of SKUs
	 */
	private $aSKUList;

	/**
	 * @return Request_GetItemsWarehouseSettings
	 */
	public function __construct() {
		$this -> aSKUList = array();
	}

	/**
	 * @param string $sSKU
	 * @return void
	 */
	public function addSKU($sSKU) {
		if (count($this -> aSKUList) < SoapCall_GetItemsWarehouseSettings::MAX_SKU_PER_PAGE) {
			$this -> aSKUList[] = $sSKU;
		}
	}

	/**
	 * @return boolean
	 */
	public function isFull(){
		return count($this -> aSKUList) === SoapCall_GetItemsWarehouseSettings::MAX_SKU_PER_PAGE;
	}

	/**
	 * @param int $warehouse
	 * @return PlentySoapRequest_GetItemsWarehouseSettings
	 */
	public function getRequest($warehouse) {
		$oPlentySoapRequest_GetItemsWarehouseSettings = new PlentySoapRequest_GetItemsWarehouseSettings();

		foreach ($this -> aSKUList as &$SKU) {
			$oPlentySoapObject_RequestGetItemsWarehouseSettings = new PlentySoapObject_RequestGetItemsWarehouseSettings();
			$oPlentySoapObject_RequestGetItemsWarehouseSettings -> WarehouseID = $warehouse;
			$oPlentySoapObject_RequestGetItemsWarehouseSettings -> SKU = $SKU;
			$oPlentySoapRequest_GetItemsWarehouseSettings -> ItemsList[] = $oPlentySoapObject_RequestGetItemsWarehouseSettings;
		}

		return $oPlentySoapRequest_GetItemsWarehouseSettings;
	}

}
?>