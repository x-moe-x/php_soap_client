<?php

require_once ROOT . 'includes/FillObjectFromArray.php';

class Request_GetCurrentStocks {

	/**
	 * @param int $lastUpdate
	 * @param int $currentTime
	 * @param int $page
	 * @return PlentySoapRequest_GetCurrentStocks
	 */
	public function getRequest($lastUpdate, $page, $warehouseID) {
		$oPlentySoapRequest_GetCurrentStocks = new PlentySoapRequest_GetCurrentStocks();

		// @formatter:off
		fillObjectFromArray($oPlentySoapRequest_GetCurrentStocks, array(
			'CallItemsLimit' =>			null,
			'GetCurrentStocksByEAN' =>	null,
			'Items' =>					null,
			'LastUpdate' =>				$lastUpdate,
			'Page' =>					$page,
			'WarehouseID' =>			$warehouseID
		));
		// @formatter:on

		return $oPlentySoapRequest_GetCurrentStocks;
	}

}
?>