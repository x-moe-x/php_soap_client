<?php

require_once ROOT . 'includes/FillObjectFromArray.php';
require_once ROOT . 'includes/SKUHelper.php';
require_once ROOT . 'includes/RequestContainer.class.php';

/**
 * Class RequestContainer_SetItemsWarehouseSettings
 */
class RequestContainer_SetItemsWarehouseSettings extends RequestContainer
{
	/**
	 * @var int
	 */
	private $warehouseID;

	/**
	 * @var bool
	 */
	private $useVariants;


	/**
	 * @param int  $warehouseID
	 * @param bool $useVariants
	 *
	 * @return RequestContainer_SetItemsWarehouseSettings
	 */
	public function __construct($warehouseID, $useVariants)
	{
		parent::__construct(SoapCall_SetItemsWarehouseSettings::MAX_WAREHOUSE_SETTINGS_PER_PAGE);

		$this->warehouseID = $warehouseID;
		$this->useVariants = $useVariants;
	}

	/**
	 * returns the assembled request
	 *
	 * @return PlentySoapRequest_SetItemsWarehouseSettings
	 */
	public function getRequest()
	{
		$oPlentySoapRequest_SetItemsWarehouseSettings = new PlentySoapRequest_SetItemsWarehouseSettings();

		$oPlentySoapRequest_SetItemsWarehouseSettings->ItemsList = new ArrayOfPlentysoapobject_setitemswarehousesettings();
		$oPlentySoapRequest_SetItemsWarehouseSettings->VariantSettings = $this->useVariants;

		$oPlentySoapRequest_SetItemsWarehouseSettings->ItemsList->item = array();

		foreach ($this->items as $aItemsWarehouseSetting)
		{
			$oPlentySoapObject_SetItemsWarehouseSettings = new PlentySoapObject_SetItemsWarehouseSettings();

			fillObjectFromArray($oPlentySoapObject_SetItemsWarehouseSettings, $aItemsWarehouseSetting, array(
				'WarehouseID' => $this->warehouseID,
				'SKU'         => Values2SKU($aItemsWarehouseSetting['ItemID'], $aItemsWarehouseSetting['AttributeValueSetID'])
			));

			$oPlentySoapRequest_SetItemsWarehouseSettings->ItemsList->item[] = $oPlentySoapObject_SetItemsWarehouseSettings;

		}

		return $oPlentySoapRequest_SetItemsWarehouseSettings;
	}
}