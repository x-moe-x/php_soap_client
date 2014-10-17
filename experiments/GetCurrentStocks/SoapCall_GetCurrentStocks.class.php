<?php
require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetCurrentStocks.class.php';
require_once ROOT . 'includes/SKUHelper.php';
require_once ROOT . 'includes/DBUtils2.class.php';
require_once ROOT . 'includes/DBLastUpdate.php';
require_once ROOT . 'api/ApiHelper.class.php';

class SoapCall_GetCurrentStocks extends PlentySoapCall {

	/**
	 * @var int
	 */
	private $page = 0;

	/**
	 * @var int
	 */
	private $pages = -1;

	/**
	 * @var int
	 */
	private $startAtPage = 0;

	/**
	 * @var array
	 */
	private $aStockRecords;

	/**
	 * @var PlentySoapRequest_GetCurrentStocks
	 */
	private $oPlentySoapRequest_GetCurrentStocks = null;

	public function __construct() {
		parent::__construct(__CLASS__);

		$this -> aStockRecords = array();
	}

	public function execute() {
		$warehouses = ApiHelper::getWarehouseList();

		foreach ($warehouses as $warehouse) {
			$this -> page = 0;
			$this -> pages = -1;
			$this -> startAtPage = 0;
			$this -> executeForWarehouse($warehouse['id']);
		}
	}

	private function executeForWarehouse($warehouseID) {

		list($lastUpdate, $currentTime, $this -> startAtPage) = lastUpdateStart(__CLASS__ . "_for_wh_$warehouseID");

		if ($this -> pages == -1) {
			try {

				$oRequest_GetCurrentStocks = new Request_GetCurrentStocks();

				$this -> oPlentySoapRequest_GetCurrentStocks = $oRequest_GetCurrentStocks -> getRequest($lastUpdate, $this -> startAtPage, $warehouseID);

				if ($this -> startAtPage > 0) {
					$this -> getLogger() -> debug(__FUNCTION__ . " Starting at page " . $this -> startAtPage);
				}

				/*
				 * do soap call
				 */
				$response = $this -> getPlentySoap() -> GetCurrentStocks($this -> oPlentySoapRequest_GetCurrentStocks);

				if (($response -> Success == true) && isset($response -> CurrentStocks)) {
					// request successful, processing data..

					$stocksFound = count($response -> CurrentStocks -> item);
					$pagesFound = $response -> Pages;

					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Success - stock records found : ' . $stocksFound . ' / pages : ' . $pagesFound . ', page : ' . ($this -> page + 1));

					// process response
					$this -> responseInterpretation($response);

					if ($pagesFound > $this -> page) {
						$this -> page = $this -> startAtPage + 1;
						$this -> pages = $pagesFound;

						lastUpdatePageUpdate(__CLASS__ . "_for_wh_$warehouseID", $this -> page);
						$this -> executePagesForWarehouse($warehouseID);

					}
				} else if (($response -> Success == true) && !isset($response -> CurrentStocks)) {
					// request successful, but no data to process
					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Success -  but no matching stock records found');
				} else {
					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Error');
				}
			} catch(Exception $e) {
				$this -> onExceptionAction($e);
			}
		} else {
			$this -> executePagesForWarehouse($warehouseID);
		}

		$this -> storeToDB();
		lastUpdateFinish($currentTime, __CLASS__ . "_for_wh_$warehouseID");
	}

	private function storeToDB() {
		// insert stock records
		$countStockRecords = count($this -> aStockRecords);

		if ($countStockRecords > 0) {
			$this -> getLogger() -> info(__FUNCTION__ . " : storing $countStockRecords stock records ...");
			DBQuery::getInstance() -> insert('INSERT INTO `CurrentStocks`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this -> aStockRecords));
		}

		$this->aStockRecords = array();
	}

	private function processStockRecord($oPlentySoapObject_GetCurrentStocks) {
		list($itemID, , $attributeValueSetID) = SKU2Values($oPlentySoapObject_GetCurrentStocks -> SKU);
		//@formatter:off
		$this->aStockRecords[] = array(
			'ItemID' => $itemID,
			'AttributeValueSetID' => $attributeValueSetID,
		    'WarehouseID' => $oPlentySoapObject_GetCurrentStocks->WarehouseID,
		    'EAN' => $oPlentySoapObject_GetCurrentStocks->EAN,
		    'EAN2' => $oPlentySoapObject_GetCurrentStocks->EAN2,
		    'EAN3' => $oPlentySoapObject_GetCurrentStocks->EAN3,
		    'EAN4' => $oPlentySoapObject_GetCurrentStocks->EAN4,
		    'VariantEAN' => $oPlentySoapObject_GetCurrentStocks->VariantEAN,
		    'VariantEAN2' => $oPlentySoapObject_GetCurrentStocks->VariantEAN2,
		    'VariantEAN3' => $oPlentySoapObject_GetCurrentStocks->VariantEAN3,
		    'VariantEAN4' => $oPlentySoapObject_GetCurrentStocks->VariantEAN4,
		    'WarehouseType' => $oPlentySoapObject_GetCurrentStocks->WarehouseType,
		    'StorageLocationID' => $oPlentySoapObject_GetCurrentStocks->StorageLocationID,
		    'StorageLocationName' => $oPlentySoapObject_GetCurrentStocks->StorageLocationName,
		    'StorageLocationStock' => $oPlentySoapObject_GetCurrentStocks->StorageLocationStock,
		    'PhysicalStock' => $oPlentySoapObject_GetCurrentStocks->PhysicalStock,
		    'NetStock' => $oPlentySoapObject_GetCurrentStocks->NetStock,
		    'AveragePrice' => $oPlentySoapObject_GetCurrentStocks->AveragePrice
		);
		//@formatter:on
	}

	private function responseInterpretation(PlentySoapResponse_GetCurrentStocks $oPlentySoapResponse_GetCurrentStocks) {
		if (is_array($oPlentySoapResponse_GetCurrentStocks -> CurrentStocks -> item)) {

			foreach ($oPlentySoapResponse_GetCurrentStocks -> CurrentStocks -> item AS $stockRecord) {
				$this -> processStockRecord($stockRecord);
			}
		} else {
			$this -> processStockRecord($oPlentySoapResponse_GetCurrentStocks -> CurrentStocks -> item);
		}
	}

	/**
	 * @return void
	 */
	private function executePagesForWarehouse($warehouseID) {
		while ($this -> pages > $this -> page) {
			$this -> oPlentySoapRequest_GetCurrentStocks -> Page = $this -> page;
			try {
				$response = $this -> getPlentySoap() -> GetCurrentStocks($this -> oPlentySoapRequest_GetCurrentStocks);

				if ($response -> Success == true) {
					$stocksFound = count($response -> CurrentStocks -> item);
					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Success - stock records found : ' . $stocksFound . ' / page : ' . ($this -> page + 1));

					// auswerten
					$this -> responseInterpretation($response);
				}

				$this -> page++;
				lastUpdatePageUpdate(__CLASS__ . "_for_wh_$warehouseID", $this -> page);

			} catch(Exception $e) {
				$this -> onExceptionAction($e);
			}
		}
	}

}
?>