<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetWarehouseList.class.php';
require_once ROOT . 'includes/DBLastUpdate.php';

class SoapCall_GetWarehouseList extends PlentySoapCall {

	private $oPlentySoapRequest_GetWarehouseList = null;

	public function __construct() {
		parent::__construct(__CLASS__);
	}

	public function execute() {
		$this -> getLogger() -> debug(__FUNCTION__);

		try {

			$oRequest_GetWarehouseList = new Request_GetWarehouseList();

			$this -> oPlentySoapRequest_GetWarehouseList = $oRequest_GetWarehouseList -> getRequest();

			/*
			 * do soap call
			 */
			$response = $this -> getPlentySoap() -> GetWarehouseList($this -> oPlentySoapRequest_GetWarehouseList);

			if ($response -> Success == true) {
				$this -> getLogger() -> debug(__FUNCTION__ . ' Request Success');

				// process response
				$this -> responseInterpretation($response);
			} else {
				$this -> getLogger() -> debug(__FUNCTION__ . ' Request Error');
			}
		} catch(Exception $e) {
			$this -> onExceptionAction($e);
		}
	}

	private function processWarehouse($oWarehouse) {
		// store to db
		$query = 'REPLACE INTO `WarehouseList` ' . DBUtils::buildInsert(array('WarehouseID' => $oWarehouse -> WarehouseID, 'Name' => $oWarehouse -> Name, 'Type' => $oWarehouse -> Type));

		DBQuery::getInstance() -> replace($query);
	}

	private function clearDB() {
		$query = 'DELETE FROM `WarehouseList`';
		$deletedItems = DBQuery::getInstance() -> delete($query);
		$this -> getLogger() -> debug(__FUNCTION__ . ' : done, deleted ' . $deletedItems . ' items');
	}

	private function responseInterpretation($oPlentySoapResponse_GetWarehouseList) {
		$this -> clearDB();
		if (is_array($oPlentySoapResponse_GetWarehouseList -> WarehouseList -> item)) {
			foreach ($oPlentySoapResponse_GetWarehouseList-> WarehouseList->item AS $warehouse) {
				$this -> processWarehouse($warehouse);
			}
			$this -> getLogger() -> debug(__FUNCTION__ . ' : done, added ' . count($oPlentySoapResponse_GetWarehouseList -> WarehouseList -> item) . ' items');
		} else {
			$this -> processWarehouse($oPlentySoapResponse_GetWarehouseList -> WarehouseList -> item);
			$this -> getLogger() -> debug(__FUNCTION__ . ' : done, added 1 item');
		}

	}

}
?>