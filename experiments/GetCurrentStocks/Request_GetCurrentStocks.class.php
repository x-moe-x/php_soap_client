<?php

require_once ROOT . 'includes/FillObjectFromArray.php';

class Request_GetCurrentStocks
{

	/**
	 * @param int $lastUpdate
	 * @param int $page
	 * @param int $warehouseId
	 *
	 * @return PlentySoapRequest_GetCurrentStocks
	 */
	public static function getRequest($lastUpdate, $page, $warehouseId)
	{
		$request = new PlentySoapRequest_GetCurrentStocks();

		fillObjectFromArray($request, array(
			'CallItemsLimit'        => null,
			'GetCurrentStocksByEAN' => null,
			'Items'                 => null,
			'LastUpdate'            => $lastUpdate,
			'Page'                  => $page,
			'WarehouseID'           => $warehouseId
		));

		return $request;
	}
}
