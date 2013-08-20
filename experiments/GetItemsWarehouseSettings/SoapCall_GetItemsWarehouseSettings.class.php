<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetItemsWarehouseSettings.class.php';
require_once ROOT . 'includes/DBLastUpdate.php';

class SoapCall_GetItemsWarehouseSettings extends PlentySoapCall {

	private $oPlentySoapRequest_GetItemsWarehouseSettings = null;

	public function __construct() {
		parent::__construct(__CLASS__);
	}

	private function getSKUQuery() {
		return 'SELECT
				ItemsBase.ItemID,
				CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
					"0"
				ELSE
					AttributeValueSets.AttributeValueSetID
				END AttributeValueSetID
				FROM ItemsBase
				LEFT JOIN AttributeValueSets
					ON ItemsBase.ItemID = AttributeValueSets.ItemID';
	}

	public function execute() {
		$this -> getLogger() -> debug(__FUNCTION__);

		try {

			// get all ItemID-AttributeValueSetID pairs
			$pairs = DBQuery::getInstance() -> select($this -> getSKUQuery());
			$maxPairs = $pairs -> getNumRows();
			$this -> getLogger() -> debug(__FUNCTION__ . ' itemid-avsi-pairs: ' . $maxPairs);

			// for each 100 pairs get items warehouse settings
			$requestSKUs = array();
			for ($i = 0; $i < 100; ++$i) {
				//TODO prevent exception to be thrown
				if ($i > 7)
					break;
				$current = $pairs -> fetchAssoc();
				$requestSKUs[] = $current['ItemID'] . '-0-' . $current['AttributeValueSetID'];
			}

			$oRequest_GetItemsWarehouseSettings = new Request_GetItemsWarehouseSettings();

			$this -> oPlentySoapRequest_GetItemsWarehouseSettings = $oRequest_GetItemsWarehouseSettings -> getRequest($requestSKUs, 1);

			/*
			 * do soap call
			 */
			$response = $this -> getPlentySoap() -> GetItemsWarehouseSettings($this -> oPlentySoapRequest_GetItemsWarehouseSettings);

			if (($response -> Success == true) && isset($response -> ItemList)) {

				$this -> getLogger() -> debug(__FUNCTION__ . ' Request Success, ' . count($response -> ItemList -> item) . ' warehouse settings found');

				// process response
				$this -> responseInterpretation($response);
			} else if (($response -> Success == true) && !isset($response -> ItemList)) {
				$this -> getLogger() -> debug(__FUNCTION__ . ' Request Success but no items available');
			} else {
				if (isset($response -> ResponseMessages -> item) && is_array($response -> ResponseMessages -> item)) {
					$errorString = '';
					foreach ($response -> ResponseMessages -> item as $message) {
						if (isset($message -> ErrorMessages -> item) && is_array($message -> ErrorMessages -> item)) {
							foreach ($message -> ErrorMessages -> item as $errorMessage) {
								$errorString .= $errorMessage -> Key . ': ' . $errorMessage -> Value;
								$errorString .= ', ';
							}

						}
					}
					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Error: ' . ($errorString != '' ? $errorString : 'unable to retreive error messages'));
				} else {
					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Error');
				}
			}
		} catch(Exception $e) {
			$this -> onExceptionAction($e);
		}
	}

	private function processWarehouseSetting($oWarehouseSetting) {
		if ((preg_match('/(\d+)-\d+-(\d+)/', $oWarehouseSetting -> SKU, $matches) == 1) && count($matches) == 3) {
			$ItemID = $matches[1];
			$AVSI = $matches[2];
		} else {
			echo "error";
		}

		$this -> getLogger() -> info(__FUNCTION__ . ' SKU: ' . $oWarehouseSetting -> SKU . ' ItemID: ' . $ItemID . ' AVSI: ' . $AVSI);
		// store to db
		$query = 'REPLACE INTO `ItemsWarehouseSettings` ' . DBUtils::buildInsert(
			array(
				'ID'					=>	$oWarehouseSetting->ID,
				'MaximumStock'			=>	$oWarehouseSetting->MaximumStock,
				'ReorderLevel'			=>	$oWarehouseSetting->ReorderLevel,
			/*  'SKU'					=>	$oWarehouseSetting->SKU,	// replace with ItemID in combination with AVSI */
				'ItemID'				=>	$ItemID,
				'AttributeValueSetID'	=>	$AVSI,
			/*
			 * 	End of SKU replacement
			 */
				'StockBuffer'			=>	$oWarehouseSetting->StockBuffer,
				'StockTurnover'			=>	$oWarehouseSetting->StockTurnover,
				'StorageLocation'		=>	$oWarehouseSetting->StorageLocation,
				'StorageLocationType'	=>	$oWarehouseSetting->StorageLocationType,
				'WarehouseID'			=>	$oWarehouseSetting->WarehouseID,
				'Zone'					=>	$oWarehouseSetting->Zone
			)
		);

		DBQuery::getInstance() -> replace($query);
	}

	private function responseInterpretation($oPlentySoapResponse_GetItemsWarehouseSettings) {
		if (is_array($oPlentySoapResponse_GetItemsWarehouseSettings -> ItemList -> item)) {
			foreach ($oPlentySoapResponse_GetItemsWarehouseSettings -> ItemList -> item as $warehouseSetting) {
				$this -> processWarehouseSetting($warehouseSetting);
			}
		} else {
			$this -> processWarehouseSetting($oPlentySoapResponse_GetItemsWarehouseSettings -> ItemList -> item);
		}
		$this -> getLogger() -> debug(__FUNCTION__ . ' done.');
	}

}
?>