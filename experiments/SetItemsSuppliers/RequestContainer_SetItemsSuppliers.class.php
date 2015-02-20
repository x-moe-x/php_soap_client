<?php

require_once ROOT . 'includes/FillObjectFromArray.php';
require_once ROOT . 'includes/RequestContainer.class.php';

/**
 * Class RequestContainer_SetItemsSuppliers
 */
class RequestContainer_SetItemsSuppliers extends RequestContainer
{
	/**
	 * @return RequestContainer_SetItemsSuppliers
	 */
	public function __construct()
	{
		parent::__construct(SoapCall_SetItemsSuppliers::MAX_SUPPLIERS_PER_PAGES);
	}

	/**
	 * returns the assembled request
	 *
	 * @return PlentySoapRequest_SetItemsSuppliers
	 */
	public function getRequest()
	{
		$oPlentySoapRequest_SetItemsSuppliers = new PlentySoapRequest_SetItemsSuppliers();

		$oPlentySoapRequest_SetItemsSuppliers->ItemsSuppliers = new ArrayOfPlentysoapobject_itemssuppliers();
		$oPlentySoapRequest_SetItemsSuppliers->ItemsSuppliers->item = array();

		foreach ($this->items as &$aItemsSupplier)
		{
			/* @var $aItemsSupplier array */
			$oPlentySoapObject_ItemsSuppliers = new PlentySoapObject_ItemsSuppliers();

			fillObjectFromArray($oPlentySoapObject_ItemsSuppliers, $aItemsSupplier);

			$oPlentySoapRequest_SetItemsSuppliers->ItemsSuppliers->item[] = $oPlentySoapObject_ItemsSuppliers;
		}

		return $oPlentySoapRequest_SetItemsSuppliers;
	}
}
