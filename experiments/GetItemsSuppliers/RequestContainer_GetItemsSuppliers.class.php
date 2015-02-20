<?php

require_once ROOT . 'includes/RequestContainer.class.php';


/**
 * Class RequestContainer_GetItemsSuppliers
 */
class RequestContainer_GetItemsSuppliers extends RequestContainer
{
	/**
	 * @return RequestContainer_GetItemsSuppliers
	 */
	public function __construct()
	{
		parent::__construct(SoapCall_GetItemsSuppliers::MAX_SUPPLIERS_PER_PAGE);
	}

	/**
	 * returns the assembled request
	 *
	 * @return PlentySoapRequest_GetItemsSuppliers
	 */
	public function getRequest()
	{
		$request = new PlentySoapRequest_GetItemsSuppliers();

		$request->ItemIDList = new ArrayOfPlentysoapobject_getitemssuppliers();
		$request->ItemIDList->item = array();

		foreach ($this->items as $itemID)
		{
			$getItemsSuppliers = new PlentySoapObject_GetItemsSuppliers();
			$getItemsSuppliers->ItemID = $itemID;

			$request->ItemIDList->item[] = $getItemsSuppliers;
		}

		return $request;
	}
}
