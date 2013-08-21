<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetItemsWarehouseSettings.class.php';
require_once ROOT . 'includes/DBLastUpdate.php';
require_once ROOT . 'includes/SKUHelper.php';

class SoapCall_GetItemsWarehouseSettings extends PlentySoapCall {

	private $page = 0;
	private $pages = -1;
	private $startAtPage = 0;
	private $oPlentySoapRequest_GetItemsWarehouseSettings = null;

	private $oItemIDAttributeValueSetIDPairs = null;
	private $oMaxPairs = -1;

	private $oWarehouseID = 1;

	private $MAX_PAIRS_PER_PAGE = 100;

	public function __construct() {
		parent::__construct(__CLASS__);
	}

	public function execute() {
		$this -> getLogger() -> debug(__FUNCTION__);

		if ($this -> pages == -1)
			try {
				$this -> initPairs();

				$oRequest_GetItemsWarehouseSettings = new Request_GetItemsWarehouseSettings();

				$this -> oPlentySoapRequest_GetItemsWarehouseSettings = $oRequest_GetItemsWarehouseSettings -> getRequest($this -> getSKUList(0), $this -> oWarehouseID);

				/*
				 * do soap call
				 */
				$response = $this -> getPlentySoap() -> GetItemsWarehouseSettings($this -> oPlentySoapRequest_GetItemsWarehouseSettings);

				if (($response -> Success == true) && isset($response -> ItemList)) {

					$pagesFound = ceil($this -> oMaxPairs / $this -> MAX_PAIRS_PER_PAGE);

					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Success, ' . count($response -> ItemList -> item) . ' warehouse settings found, ' . $pagesFound . ' pages');

					// process response
					$this -> responseInterpretation($response);

					if ($pagesFound > $this -> page) {
						$this -> page = $this -> startAtPage + 1;
						$this -> pages = $pagesFound;

						$this -> executePages();
					}
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
		else {
			$this -> executePages();
		}
	}

	private function executePages() {
		while ($this -> pages > $this -> page) {
			$oRequest_GetItemsWarehouseSettings = new Request_GetItemsWarehouseSettings();

			$this -> oPlentySoapRequest_GetItemsWarehouseSettings = $oRequest_GetItemsWarehouseSettings -> getRequest($this -> getSKUList($this -> page), $this -> oWarehouseID);

			try {
				$response = $this -> getPlentySoap() -> GetItemsWarehouseSettings($this -> oPlentySoapRequest_GetItemsWarehouseSettings);

				if (($response -> Success == true) && isset($response -> ItemList)) {

					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Success, ' . count($response -> ItemList -> item) . ' warehouse settings found, page: ' . $this -> page);

					// process response
					$this -> responseInterpretation($response);
				}

				$this -> page++;

			} catch(Exception $e) {
				$this -> onExceptionAction($e);
			}
		}
	}

	private function getPairQuery() {
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

	private function initPairs() {
		// get all ItemID-AttributeValueSetID pairs
		$this -> oItemIDAttributeValueSetIDPairs = DBQuery::getInstance() -> select($this -> getPairQuery());
		$this -> oMaxPairs = $this -> oItemIDAttributeValueSetIDPairs -> getNumRows();

		$this -> getLogger() -> debug(__FUNCTION__ . ' itemid-avsi-pairs: ' . $this -> oMaxPairs);
	}

	private function getSKUList($startPage) {
		$requestSKUs = array();
		for ($i = $startPage * $this -> MAX_PAIRS_PER_PAGE; $i < min(($startPage + 1) * $this -> MAX_PAIRS_PER_PAGE, $this -> oMaxPairs); ++$i) {

			$current = $this -> oItemIDAttributeValueSetIDPairs -> fetchAssoc();

			// TODO workaround for plenty bug on ticket 135428
			// check if no article variant is beeing processed ...
			if ($current['AttributeValueSetID'] == 0)
				// ... then store
				$requestSKUs[] = Values2SKU($current['ItemID'], $current['AttributeValueSetID']);
			else
				// ... otherwise skip
				;
		}
		return $requestSKUs;
	}

	private function processWarehouseSetting($oWarehouseSetting) {
		list($ItemID, $PriceID, $AttributeValueSetID) = SKU2Values($oWarehouseSetting -> SKU);

		$this -> getLogger() -> info(__FUNCTION__ . ' SKU: ' . $oWarehouseSetting -> SKU . ' ItemID: ' . $ItemID . ' AVSI: ' . $AttributeValueSetID);
		// store to db
		// @formatter:off
		$query = 'REPLACE INTO `ItemsWarehouseSettings` ' . DBUtils::buildInsert(
			array(
				'ID'					=>	$oWarehouseSetting->ID,
				'MaximumStock'			=>	$oWarehouseSetting->MaximumStock,
				'ReorderLevel'			=>	$oWarehouseSetting->ReorderLevel,
			/*  'SKU'					=>	$oWarehouseSetting->SKU,	// replace with ItemID in combination with AVSI */
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
			)
		);
		// @formatter:on

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