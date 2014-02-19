<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetItemsSuppliers.class.php';

class SoapCall_GetItemsSuppliers extends PlentySoapCall {

	private static $MAX_PAGES = 50;

	/**
	 * Used to prepare bulk insertion to db
	 *
	 * @var array
	 */
	private $aStoreData;

	public function __construct() {
		parent::__construct(__CLASS__);
		$this -> storeData = array();
	}

	public function execute() {
		try {
			// get all possible ItemIDs
			$result = DBQuery::getInstance() -> select('Select ItemID FROM ItemsBase');

			// for every 50 ItemIDs ...
			for ($page = 0; $page < ceil($result -> getNumRows() / self::$MAX_PAGES); $page++) {

				// ... perpare a separate request
				$oRequest_GetItemsSuppliers = new Request_GetItemsSuppliers();
				while (!$oRequest_GetItemsSuppliers -> isFull() && $current = $result -> fetchAssoc()) {
					$oRequest_GetItemsSuppliers -> addItemID($current['ItemID']);
				}

				/*
				 * do soap call
				 */
				$response = $this -> getPlentySoap() -> GetItemsSuppliers($oRequest_GetItemsSuppliers -> getRequest());

				if ($response -> Success == true) {
					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Success');

					// process response
					$this -> responseInterpretation($response);
				} else {
					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Error');
				}
			}
			
			echo implode(',', $this->aStoreData);
		} catch(Exception $e) {
			$this -> onExceptionAction($e);
		}
	}

	/**
	 * @param PlentySoapResponse_GetItemsSuppliers $response
	 */
	private function responseInterpretation(PlentySoapResponse_GetItemsSuppliers $response) {
		if (is_array($response -> ItemsSuppliersList -> item)) {

			$countRecords = count($response -> ItemsSuppliersList -> item);
			$this -> getLogger() -> debug(__FUNCTION__ . " fetched $countRecords supplier records from ItemID: {$response -> ItemsSuppliersList -> item[0]->ItemID} to {$response -> ItemsSuppliersList -> item[$countRecords - 1]->ItemID}");

			foreach ($response -> ItemsSuppliersList -> item AS $supplier) {
				$this -> processSupplier($supplier);
			}
		} else {

			$this -> getLogger() -> debug(__FUNCTION__ . " fetched supplier record from ItemID: {$response -> ItemsSuppliersList -> item -> ItemID}");

			$this -> processSupplier($oPlentySoapResponse_SearchOrders -> Orders -> item);
		}
	}

	/*
	 *  [20] => stdClass Object
	 (
	 [ItemsSuppliers] => stdClass Object
	 (
	 [item] => Array
	 (
	 [0] => stdClass Object
	 (
	 [ItemSupplierRowID] => 2215
	 [ItemID] => 885
	 [SupplierID] => 4
	 [Priority] => 0
	 [ItemSupplierPrice] =>
	 [SupplierMinimumPurchase] => 1
	 [SupplierItemNumber] => SCT15XA4PHC9003
	 [SupplierDeliveryTime] => 7
	 [LastUpdate] => 2012
	 [Rebate] => 0
	 [IsRebateAllowed] => 0
	 [VPE] => 1
	 )

	 )

	 )

	 [ItemID] => 885
	 )
	 * */

	/**
	 * @param $oPlentySoapObject_ItemsSuppliersList PlentySoapObject_ItemsSuppliersList
	 */
	private function processSupplier($oPlentySoapObject_ItemsSuppliersList) {
		foreach ($oPlentySoapObject_ItemsSuppliersList->ItemsSuppliers->item as $supplier) {/* @var $supplier PlentySoapObject_ItemsSuppliers */
			// sanity check
			if (!$oPlentySoapObject_ItemsSuppliersList -> ItemID === $supplier -> ItemID) {
				$this -> getLogger() -> debug(__FUNCTION__ . " {$oPlentySoapObject_ItemsSuppliersList->ItemID} != {$supplier->ItemID}");
				die();
			}

			// store
			$this -> aStoreData[] = "('{$supplier->ItemSupplierRowID}','{$supplier->ItemID}','{$supplier->SupplierID}','{$supplier->Priority}','{$supplier->ItemSupplierPrice}','{$supplier->SupplierMinimumPurchase}','{$supplier->SupplierItemNumber}','{$supplier->SupplierDeliveryTime}','{$supplier->LastUpdate}','{$supplier->Rebate}','{$supplier->IsRebateAllowed}','{$supplier->VPE}')";
		}
	}

}
