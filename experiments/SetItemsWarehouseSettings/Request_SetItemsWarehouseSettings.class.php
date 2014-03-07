<?php

require_once ROOT . 'includes/FillObjectFromArray.php';
require_once ROOT . 'includes/SKUHelper.php';

class Request_SetItemsWarehouseSettings {

	/**
	 * @var array
	 */
	private $aItemsWarehouseSettings;

	/**
	 * @param array $aWarehouseSetting
	 * @return void
	 */
	public function addItemsWarehouseSetting(array $aWarehouseSetting) {
		if (count($this -> aItemsWarehouseSettings) < SoapCall_SetItemsWarehouseSettings::MAX_WAREHOUSE_SETTINGS_PER_PAGE) {
			$this -> aItemsWarehouseSettings[] = $aWarehouseSetting;
		}
	}

	/**
	 * @return boolean
	 */
	public function isFull() {
		return count($this -> aItemsWarehouseSettings) === SoapCall_SetItemsWarehouseSettings::MAX_WAREHOUSE_SETTINGS_PER_PAGE;
	}

	/**
	 * @return Request_SetItemsWarehouseSettings
	 */
	public function __construct() {
		$this -> aItemsWarehouseSettings = array();
	}

	/**
	 * @param int $warehouse
	 * @return PlentySoapRequest_SetItemsWarehouseSettings
	 */
	public function getRequest($warehouse) {
		$oPlentySoapRequest_SetItemsWarehouseSettings = new PlentySoapRequest_SetItemsWarehouseSettings();

		$oPlentySoapRequest_SetItemsWarehouseSettings -> ItemsList = new ArrayOfPlentysoapobject_setitemswarehousesettings();
		$oPlentySoapRequest_SetItemsWarehouseSettings -> VariantSettings = FALSE;

		$oPlentySoapRequest_SetItemsWarehouseSettings -> ItemsList -> item = array();

		foreach ($this->aItemsWarehouseSettings as $aItemsWarehouseSetting) {
			$oPlentySoapObject_SetItemsWarehouseSettings = new PlentySoapObject_SetItemsWarehouseSettings();

			fillObjectFromArray($oPlentySoapObject_SetItemsWarehouseSettings, $aItemsWarehouseSetting, array('WarehouseID' => $warehouse, 'SKU' => Values2SKU($aItemsWarehouseSetting['ItemID'], $aItemsWarehouseSetting['AttributeValueSetID'])));
			
			$oPlentySoapRequest_SetItemsWarehouseSettings -> ItemsList -> item[] = $oPlentySoapObject_SetItemsWarehouseSettings;

		}

		return $oPlentySoapRequest_SetItemsWarehouseSettings;
	}

}
?>