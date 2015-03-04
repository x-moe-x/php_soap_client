<?php

require_once ROOT . 'includes/RequestContainer.class.php';
require_once ROOT . 'includes/SKUHelper.php';

/**
 * Class RequestContainer_GetItemsPriceLists
 */
class RequestContainer_GetItemsPriceLists extends RequestContainer
{

	/**
	 * @return RequestContainer_GetItemsPriceLists
	 */
	public function __construct()
	{
		parent::__construct(SoapCall_GetItemsPriceLists::MAX_PRICE_SETS_PER_PAGE);
	}

	/**
	 * @param mixed $itemID
	 * @param       $attributeValueSetID
	 */
	public function add($itemID, $attributeValueSetID)
	{
		parent::add(array(
			$itemID,
			$attributeValueSetID
		));
	}

	/**
	 * returns the assembled request
	 *
	 * @return PlentySoapRequest_GetItemsPriceLists
	 */
	public function getRequest()
	{
		$request = new PlentySoapRequest_GetItemsPriceLists();

		$request->Items = new ArrayOfPlentysoaprequestobject_getitemspricelists();
		$request->Items->item = array();

		foreach ($this->items as $sSKU)
		{
			$getItemsPriceLists = new PlentySoapRequestObject_GetItemsPriceLists();
			$getItemsPriceLists->SKU = is_array($sSKU) ? Values2SKU($sSKU[0], $sSKU[1]) : Values2SKU($sSKU);

			$request->Items->item[] = $getItemsPriceLists;
		}

		return $request;
	}
}
