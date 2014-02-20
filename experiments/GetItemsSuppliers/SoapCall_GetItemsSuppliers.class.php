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

				// ... perpare a separate request ...
				$oRequest_GetItemsSuppliers = new Request_GetItemsSuppliers();
				while (!$oRequest_GetItemsSuppliers -> isFull() && $current = $result -> fetchAssoc()) {
					$oRequest_GetItemsSuppliers -> addItemID($current['ItemID']);
				}

				// ... then do soap call ...
				$response = $this -> getPlentySoap() -> GetItemsSuppliers($oRequest_GetItemsSuppliers -> getRequest());

				// if successful ...
				if ($response -> Success == true) {
					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Success');

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

	private function storeToDB() {
		// bulk replace
		DBQuery::getInstance() -> replace('REPLACE INTO `ItemSuppliers`' . DBUtils::buildMultipleInsert($this -> aStoreData));
	}

	/**
	 *
	 *
	 * @param PlentySoapResponse_GetItemsSuppliers $oPlentySoapResponse_GetItemsSuppliers
	 */
	private function responseInterpretation(PlentySoapResponse_GetItemsSuppliers $oPlentySoapResponse_GetItemsSuppliers) {
		if (is_array($oPlentySoapResponse_GetItemsSuppliers -> ItemsSuppliersList -> item)) {

			$countRecords = count($oPlentySoapResponse_GetItemsSuppliers -> ItemsSuppliersList -> item);
			$this -> getLogger() -> debug(__FUNCTION__ . " fetched $countRecords supplier records from ItemID: {$oPlentySoapResponse_GetItemsSuppliers -> ItemsSuppliersList -> item[0]->ItemID} to {$oPlentySoapResponse_GetItemsSuppliers -> ItemsSuppliersList -> item[$countRecords - 1]->ItemID}");

			foreach ($oPlentySoapResponse_GetItemsSuppliers -> ItemsSuppliersList -> item AS $oPlentySoapObject_ItemsSuppliersList) {
				$this -> processSupplier($oPlentySoapObject_ItemsSuppliersList);
			}
		} else {

			$this -> getLogger() -> debug(__FUNCTION__ . " fetched supplier record from ItemID: {$oPlentySoapResponse_GetItemsSuppliers -> ItemsSuppliersList -> item -> ItemID}");

			$this -> processSupplier($oPlentySoapResponse_GetItemsSuppliers -> ItemsSuppliersList -> item);
		}
	}

	/**
	 * @param $oPlentySoapObject_ItemsSuppliersList PlentySoapObject_ItemsSuppliersList
	 */
	private function processSupplier($oPlentySoapObject_ItemsSuppliersList) {
		if (is_array($oPlentySoapObject_ItemsSuppliersList -> ItemsSuppliers -> item)) {
			foreach ($oPlentySoapObject_ItemsSuppliersList -> ItemsSuppliers -> item as $oPlentySoapObject_ItemsSuppliers) {
				/* @var $oPlentySoapObject_ItemsSuppliers PlentySoapObject_ItemsSuppliers */

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