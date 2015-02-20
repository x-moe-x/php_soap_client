<?php

require_once ROOT . 'includes/RequestContainer.class.php';


/**
 * Class RequestContainer_GetItemsWarehouseSettings
 */
class RequestContainer_GetItemsWarehouseSettings extends RequestContainer
{
	/**
	 * @var int
	 */
	private $warehouseId;

	/**
	 * @param int $warehouseId
	 *
	 * @return RequestContainer_GetItemsWarehouseSettings
	 */
	public function __construct($warehouseId)
	{
		parent::__construct(SoapCall_GetItemsWarehouseSettings::MAX_SKU_PER_PAGE);

		$this->warehouseId = $warehouseId;
	}

	/**
	 * returns the assembled request
	 *
	 * @return PlentySoapRequest_GetItemsWarehouseSettings
	 */
	public function getRequest()
	{
		$request = new PlentySoapRequest_GetItemsWarehouseSettings();

		foreach ($this->items as &$SKU)
		{
			$requestGetItemsWarehouseSettings = new PlentySoapObject_RequestGetItemsWarehouseSettings();
			$requestGetItemsWarehouseSettings->WarehouseID = $this->warehouseId;
			$requestGetItemsWarehouseSettings->SKU = $SKU;
			$request->ItemsList[] = $requestGetItemsWarehouseSettings;
		}

		return $request;
	}
}
