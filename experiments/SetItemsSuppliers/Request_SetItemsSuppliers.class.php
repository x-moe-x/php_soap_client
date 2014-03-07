<?php

require_once ROOT . 'includes/FillObjectFromArray.php';

class Request_SetItemsSuppliers {

	/**
	 * @var array
	 */
	private $aItemsSuppliers;

	/**
	 * @return Request_SetItemsSuppliers
	 */
	public function __construct() {
		$this -> aItemsSuppliers = array();
	}

	/**
	 * @param array $aItemsSupplier
	 * @return void
	 */
	public function addItemsSupplier(array $aItemsSuppliers) {
		if (count($this -> aItemsSuppliers) < SoapCall_SetItemsSuppliers::MAX_SUPPLIERS_PER_PAGES) {
			$this -> aItemsSuppliers[] = $aItemsSuppliers;
		}
	}

	/**
	 * @return boolean
	 */
	public function isFull() {
		return count($this -> aItemsSuppliers) === SoapCall_SetItemsSuppliers::MAX_SUPPLIERS_PER_PAGES;
	}

	/**
	 * @return PlentySoapRequest_SetItemsSuppliers
	 */
	public function getRequest() {
		$oPlentySoapRequest_SetItemsSuppliers = new PlentySoapRequest_SetItemsSuppliers();

		$oPlentySoapRequest_SetItemsSuppliers -> ItemsSuppliers = new ArrayOfPlentysoapobject_itemssuppliers();
		$oPlentySoapRequest_SetItemsSuppliers -> ItemsSuppliers -> item = array();

		foreach ($this -> aItemsSuppliers as &$aItemsSupplier) {/* @var $aItemsSupplier array */
			$oPlentySoapObject_ItemsSuppliers = new PlentySoapObject_ItemsSuppliers();

			fillObjectFromArray($oPlentySoapObject_ItemsSuppliers, $aItemsSupplier);

			$oPlentySoapRequest_SetItemsSuppliers -> ItemsSuppliers -> item[] = $oPlentySoapObject_ItemsSuppliers;
		}

		return $oPlentySoapRequest_SetItemsSuppliers;
	}

}
?>