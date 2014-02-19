<?php

class Request_GetItemsSuppliers {

	/**
	 * @var int[]
	 */
	private $aItemIDs;

	public function __construct() {
		$this -> aItemIDs = array();
	}

	/**
	 * @param int $itemID
	 */
	public function addItemID($itemID) {
		$this -> aItemIDs[] = $itemID;
	}

	/**
	 * @return PlentySoapRequest_GetItemsSuppliers
	 */
	public function getRequest() {
		$oPlentySoapRequest_GetItemsSuppliers = new PlentySoapRequest_GetItemsSuppliers();

		$oPlentySoapRequest_GetItemsSuppliers -> ItemIDList = new ArrayOfPlentysoapobject_getitemssuppliers();
		$oPlentySoapRequest_GetItemsSuppliers -> ItemIDList -> item = array();

		foreach ($this->aItemIDs as $itemID) {
			$oPlentySoapObject_GetItemsSuppliers = new PlentySoapObject_GetItemsSuppliers();
			$oPlentySoapObject_GetItemsSuppliers -> ItemID = $itemID;

			$oPlentySoapRequest_GetItemsSuppliers -> ItemIDList -> item[] = $oPlentySoapObject_GetItemsSuppliers;
		}

		return $oPlentySoapRequest_GetItemsSuppliers;
	}

}
?>