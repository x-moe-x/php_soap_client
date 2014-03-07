<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_SetItemsWarehouseSettings.class.php';

class SoapCall_SetItemsWarehouseSettings extends PlentySoapCall {

	/**
	 * @var int
	 */
	const MAX_WAREHOUSE_SETTINGS_PER_PAGE = 100;

	/**
	 * @var int
	 */
	private $warehouseID = 1;

	/**
	 * @return SoapCall_SetItemsWarehouseSettings
	 */
	public function __construct() {
		parent::__construct(__CLASS__);
	}

	/**
	 * @return void
	 */
	public function execute() {
		$this -> getLogger() -> debug(__FUNCTION__ . ' writing items warehouse settings ...');
		try {
			// get all values for articles with write permission
			$oDBResult = DBQuery::getInstance() -> select($this -> getWriteBackQuery());

			// for every 100 ItemIDs ...
			for ($page = 0, $maxPage = ceil($oDBResult -> getNumRows() / self::MAX_WAREHOUSE_SETTINGS_PER_PAGE); $page < $maxPage; $page++) {

				// ... prepare a separate request ...
				$oRequest_SetItemsWarehouseSettings = new Request_SetItemsWarehouseSettings();

				while (!$oRequest_SetItemsWarehouseSettings -> isFull() && ($aCurrentItemsWarehoueSetting = $oDBResult -> fetchAssoc())) {
					$oRequest_SetItemsWarehouseSettings -> addItemsWarehouseSetting($aCurrentItemsWarehoueSetting);
				}

				// do soap call to plenty
				$response = $this -> getPlentySoap() -> SetItemsWarehouseSettings($oRequest_SetItemsWarehouseSettings -> getRequest($this -> warehouseID));

				// ... if successful ...
				if ($response -> Success == true) {
				} else {

					// ... otherwise log error and try next request
					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Error');
				}
			}
		} catch(Exception $e) {
			$this -> onExceptionAction($e);
		}
	}

	/**
	 * @return string
	 */
	private function getWriteBackQuery() {
		return 'SELECT
	ItemsWarehouseSettings.ItemID,
	ItemsWarehouseSettings.AttributeValueSetID,
	ItemsWarehouseSettings.ID,
	/* ItemsWarehouseSettings.MaximumStock, skipped, use suggestion instead */
	/* ItemsWarehouseSettings.ReorderLevel, skipped, use suggestion instead */
	ItemsWarehouseSettings.StockBuffer,
	ItemsWarehouseSettings.StockTurnover,
	ItemsWarehouseSettings.StorageLocation,
	ItemsWarehouseSettings.StorageLocationType,
	ItemsWarehouseSettings.WarehouseID,
	ItemsWarehouseSettings.Zone,
	WriteBackSuggestion.MaximumStock,
	WriteBackSuggestion.ReorderLevel
FROM
	`ItemsWarehouseSettings`
LEFT JOIN
	`WritePermissions`
ON
	ItemsWarehouseSettings.ItemID = WritePermissions.ItemID AND ItemsWarehouseSettings.AttributeValueSetID = WritePermissions.AttributeValueSetID
LEFT JOIN
	`WriteBackSuggestion`
ON
	ItemsWarehouseSettings.ItemID = WriteBackSuggestion.ItemID AND ItemsWarehouseSettings.AttributeValueSetID = WriteBackSuggestion.AttributeValueSetID
WHERE
	WritePermissions.WritePermission = 1
AND
	WritePermissions.AttributeValueSetID = 0' . PHP_EOL;
	}

}
?>