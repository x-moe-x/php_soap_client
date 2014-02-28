<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetItemsSuppliers.class.php';

class SoapCall_GetItemsSuppliers extends PlentySoapCall {

	/**
	 * @var int
	 */
	public static $MAX_SUPPLIERS_PER_PAGES = 50;

	/**
	 * Used to prepare bulk insertion to db
	 *
	 * @var array
	 */
	private $aStoreData;

	public function __construct() {
		parent::__construct(__CLASS__);
		$this -> aStoreData = array();
	}

	/**
	 * overrides PlentySoapCall's execute() method
	 *
	 * @return void
	 */
	public function execute() {
		try {
			// get all possible ItemIDs
			$result = DBQuery::getInstance() -> select('Select ItemID FROM ItemsBase');

			// for every 50 ItemIDs ...
			for ($page = 0; $page < ceil($result -> getNumRows() / self::$MAX_SUPPLIERS_PER_PAGES); $page++) {

				// ... perpare a separate request ...
				$oRequest_GetItemsSuppliers = new Request_GetItemsSuppliers();
				while (!$oRequest_GetItemsSuppliers -> isFull() && $current = $result -> fetchAssoc()) {
					$oRequest_GetItemsSuppliers -> addItemID($current['ItemID']);
				}

				// ... then do soap call ...
				$response = $this -> getPlentySoap() -> GetItemsSuppliers($oRequest_GetItemsSuppliers -> getRequest());

				// ... if successfull ...
				if ($response -> Success == true) {

					// ... then process response
					$this -> responseInterpretation($response);
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
	 * bulk insert/update retrieved data except ItemSupplierPrice and LastUpdate
	 * this is a workaround for a plenty bug which prevents correct values of price and last update being sent
	 *
	 * @return void
	 */
	private function storeToDB() {
		DBQuery::getInstance() -> insert('INSERT INTO `ItemSuppliers`' . DBUtils::buildMultipleInsert($this -> aStoreData) . 'ON DUPLICATE KEY UPDATE ItemSupplierRowID=VALUES(ItemSupplierRowID),IsRebateAllowed=VALUES(IsRebateAllowed),Priority=VALUES(Priority),Rebate=VALUES(Rebate),SupplierDeliveryTime=VALUES(SupplierDeliveryTime),SupplierItemNumber=VALUES(SupplierItemNumber),SupplierMinimumPurchase=VALUES(SupplierMinimumPurchase),VPE=VALUES(VPE)');
	}

	/**
	 * process whole PlentySoapResponse_GetItemsSuppliers record from plenty
	 *
	 * @param PlentySoapResponse_GetItemsSuppliers $oPlentySoapResponse_GetItemsSuppliers
	 * @return void
	 */
	private function responseInterpretation(PlentySoapResponse_GetItemsSuppliers $oPlentySoapResponse_GetItemsSuppliers) {
		if (is_array($oPlentySoapResponse_GetItemsSuppliers -> ItemsSuppliersList -> item)) {

			$countRecords = count($oPlentySoapResponse_GetItemsSuppliers -> ItemsSuppliersList -> item);
			$this -> getLogger() -> debug(__FUNCTION__ . " fetched $countRecords supplier records from ItemID: {$oPlentySoapResponse_GetItemsSuppliers -> ItemsSuppliersList -> item[0]->ItemID} to {$oPlentySoapResponse_GetItemsSuppliers -> ItemsSuppliersList -> item[$countRecords - 1]->ItemID}");

			foreach ($oPlentySoapResponse_GetItemsSuppliers -> ItemsSuppliersList -> item AS &$oPlentySoapObject_ItemsSuppliersList) {
				$this -> processSupplier($oPlentySoapObject_ItemsSuppliersList);
			}
		} else {

			$this -> getLogger() -> debug(__FUNCTION__ . " fetched supplier record for ItemID: {$oPlentySoapResponse_GetItemsSuppliers -> ItemsSuppliersList -> item -> ItemID}");

			$this -> processSupplier($oPlentySoapResponse_GetItemsSuppliers -> ItemsSuppliersList -> item);
		}
	}

	/**
	 * process PlentySoapObject_ItemsSuppliersList for a single itemID
	 *
	 * @param PlentySoapObject_ItemsSuppliersList $oPlentySoapObject_ItemsSuppliersList
	 * @return void
	 */
	private function processSupplier($oPlentySoapObject_ItemsSuppliersList) {
		if (is_array($oPlentySoapObject_ItemsSuppliersList -> ItemsSuppliers -> item)) {
			foreach ($oPlentySoapObject_ItemsSuppliersList -> ItemsSuppliers -> item as $oPlentySoapObject_ItemsSuppliers) {
				/* @var PlentySoapObject_ItemsSuppliers $oPlentySoapObject_ItemsSuppliers*/

				// sanity check
				if (!$oPlentySoapObject_ItemsSuppliersList -> ItemID === $oPlentySoapObject_ItemsSuppliers -> ItemID) {
					$this -> getLogger() -> debug(__FUNCTION__ . " {$oPlentySoapObject_ItemsSuppliersList->ItemID} != {$supplier->ItemID}");
					die();
				}

				// prepare for storing
				$this -> aStoreData[] = (array)$oPlentySoapObject_ItemsSuppliers;
			}
		} else {
			// sanity check
			if (!$oPlentySoapObject_ItemsSuppliersList -> ItemID === $oPlentySoapObject_ItemsSuppliersList -> ItemsSuppliers -> item -> ItemID) {
				$this -> getLogger() -> debug(__FUNCTION__ . " {$oPlentySoapObject_ItemsSuppliersList->ItemID} != {$oPlentySoapObject_ItemsSuppliersList -> ItemsSuppliers -> item -> ItemID}");
				die();
			}

			// prepare for storing
			$this -> aStoreData[] = (array)$oPlentySoapObject_ItemsSuppliersList -> ItemsSuppliers -> item;
		}
	}

}
