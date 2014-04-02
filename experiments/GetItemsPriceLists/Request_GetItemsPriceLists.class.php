<?php


require_once ROOT . 'includes/SKUHelper.php';

class Request_GetItemsPriceLists {

	/**
	 * @var array
	 */
	private $aSKUs;

	/**
	 * @return Request_GetItemsPriceLists
	 */
	public function __construct() {
		$this -> aSKUs = array();
	}

	/**
	 * @param int $itemID
	 * @param int $attributeValueSetID
	 * @return void
	 */
	public function addArticleVariant($itemID, $attributeValueSetID) {
		if (count($this -> aSKUs) < SoapCall_GetItemsPriceLists::MAX_PRICE_SETS_PER_PAGE) {
			$this -> aSKUs[] = Values2SKU($itemID, $attributeValueSetID);
		}
	}

	/**
	 * @return bool
	 */
	public function isFull() {
		return count($this -> aSKUs) === SoapCall_GetItemsPriceLists::MAX_PRICE_SETS_PER_PAGE;
	}

	/**
	 * @return PlentySoapRequest_GetItemsPriceLists
	 */
	public function getRequest() {
		$oPlentySoapRequest_GetItemsPriceLists = new PlentySoapRequest_GetItemsPriceLists();

		$oPlentySoapRequest_GetItemsPriceLists -> Items = new ArrayOfPlentysoaprequestobject_getitemspricelists();
		$oPlentySoapRequest_GetItemsPriceLists -> Items -> item = array();

		foreach ($this -> aSKUs as $sSKU) {
			$oPlentySoapRequestObject_GetItemsPriceLists = new PlentySoapRequestObject_GetItemsPriceLists();
			$oPlentySoapRequestObject_GetItemsPriceLists->SKU = $sSKU;

			$oPlentySoapRequest_GetItemsPriceLists -> Items -> item[] = $oPlentySoapRequestObject_GetItemsPriceLists;
		}

		return $oPlentySoapRequest_GetItemsPriceLists;
	}

}
?>