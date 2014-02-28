<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetItemsWarehouseSettings.class.php';
require_once ROOT . 'includes/DBLastUpdate.php';
require_once ROOT . 'includes/SKUHelper.php';
require_once ROOT . 'includes/DBUtils2.class.php';

class SoapCall_GetItemsWarehouseSettings extends PlentySoapCall {

	/**
	 * @var int
	 */
	public static $MAX_SKU_PER_PAGE = 100;

	/**
	 * @var array
	 */
	private $aStorData;

	/**
	 * @var int
	 */
	private $warehouseID = 1;

	/**
	 * @return SoapCall_GetItemsWarehouseSettings
	 */
	public function __construct() {
		parent::__construct(__CLASS__);
		$this -> aStorData = array();
	}

	/**
	 * @return void
	 */
	public function execute() {
		try {
			// get all possible SKUs
			$oDBResult = DBQuery::getInstance() -> select($this -> getSKUQuery());

			// for every 100 SKUs ...
			for ($page = 0, $maxPages = ceil($oDBResult -> getNumRows() / self::$MAX_SKU_PER_PAGE); $page < $maxPages; $page++) {/** @var int $page  */

				// ... prepare a seperate request
				$oRequest_GetItemsWarehouseSettings = new Request_GetItemsWarehouseSettings($this->warehouseID);
				while (!$oRequest_GetItemsWarehouseSettings -> isFull() && $current = $oDBResult -> fetchAssoc()) {
					$oRequest_GetItemsWarehouseSettings -> addSKU($current['SKU']);
				}

				// ... then do soap call ...
				$oPlentySoapResponse_GetItemsWarehouseSettings = $this -> getPlentySoap() -> GetItemsWarehouseSettings($oRequest_GetItemsWarehouseSettings -> getRequest($this -> warehouseID));

				// ... if successful ...
				if (($oPlentySoapResponse_GetItemsWarehouseSettings -> Success == true)) {
					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Success');

					// ... then process response
					$this -> responseInterpretation($oPlentySoapResponse_GetItemsWarehouseSettings);
				} else {

					// ... otherwise log error and try next request
					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Error');
				}
			}

			// when done store all retrieved data to db
			$this -> storeToDB();

		} catch(Exception $e) {
			$this -> onExceptionAction($e);
		}
	}

	/**
	 * @return string SQL-Query to get all pairs of ItemID -> AttributeValueSetID
	 */
	private function getSKUQuery() {
		return 'SELECT
CONCAT(
	ItemsBase.ItemID,
	\'-0-\',
    CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
        "0"
    ELSE
        AttributeValueSets.AttributeValueSetID
    END
) AS SKU
FROM
	ItemsBase
LEFT JOIN
	AttributeValueSets
ON
	ItemsBase.ItemID = AttributeValueSets.ItemID';
	}

	private function storeToDB() {
		DBQuery::getInstance() -> insert('INSERT INTO `ItemsWarehouseSettings`' . DBUtils::buildMultipleInsert($this -> aStorData).'ON DUPLICATE KEY UPDATE' . DBUtils2::buildOnDuplicateKeyUpdateAll($this -> aStorData[0]));
	}

	private function processWarehouseSetting($oWarehouseSetting) {
		list($ItemID, , $AttributeValueSetID) = SKU2Values($oWarehouseSetting -> SKU);

		// @formatter:off
		$this->aStorData[] = array(
				'ID'					=>	$oWarehouseSetting->ID,
				'MaximumStock'			=>	$oWarehouseSetting->MaximumStock,
				'ReorderLevel'			=>	$oWarehouseSetting->ReorderLevel,
			/*  'SKU'					=>	$oWarehouseSetting->SKU,	// replaced with ItemID in combination with AVSI */
				'ItemID'				=>	$ItemID,
				'AttributeValueSetID'	=>	$AttributeValueSetID,
			/*
			 * 	End of SKU replacement
			 */
				'StockBuffer'			=>	$oWarehouseSetting->StockBuffer,
				'StockTurnover'			=>	$oWarehouseSetting->StockTurnover,
				'StorageLocation'		=>	$oWarehouseSetting->StorageLocation,
				'StorageLocationType'	=>	$oWarehouseSetting->StorageLocationType,
				'WarehouseID'			=>	$oWarehouseSetting->WarehouseID,
				'Zone'					=>	$oWarehouseSetting->Zone
		);
		// @formatter:on
	}

	private function responseInterpretation($oPlentySoapResponse_GetItemsWarehouseSettings) {
		if (isset($oPlentySoapResponse_GetItemsWarehouseSettings -> ItemList) && is_array($oPlentySoapResponse_GetItemsWarehouseSettings -> ItemList -> item)) {

			$countRecords = count($oPlentySoapResponse_GetItemsWarehouseSettings -> ItemList -> item);
			$this -> getLogger() -> debug(__FUNCTION__ . " fetched $countRecords warehouse setting records from SKU: {$oPlentySoapResponse_GetItemsWarehouseSettings -> ItemList -> item[0] -> SKU} to {$oPlentySoapResponse_GetItemsWarehouseSettings -> ItemList -> item[$countRecords - 1] -> SKU}");

			foreach ($oPlentySoapResponse_GetItemsWarehouseSettings -> ItemList -> item as &$warehouseSetting) {
				$this -> processWarehouseSetting($warehouseSetting);
			}
		} else if (isset($oPlentySoapResponse_GetItemsWarehouseSettings -> ItemList)) {
			$this -> getLogger() -> debug(__FUNCTION__ . " fetched warehouse setting records for SKU: {$oPlentySoapResponse_GetItemsWarehouseSettings -> ItemList -> item -> SKU}");

			$this -> processWarehouseSetting($oPlentySoapResponse_GetItemsWarehouseSettings -> ItemList -> item);
		} else {
			$this -> getLogger() -> debug(__FUNCTION__ . ' fetched no warehouse setting records for current request');
		}
		$this -> getLogger() -> debug(__FUNCTION__ . ' done.');
	}

}
?>