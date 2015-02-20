<?php

require_once ROOT . 'includes/RequestContainer.class.php';

/**
 * Class RequestContainer_GetLinkedItems
 */
class RequestContainer_GetLinkedItems extends RequestContainer
{
	/**
	 * @return RequestContainer_GetLinkedItems
	 */
	public function __construct()
	{
		parent::__construct(SoapCall_GetLinkedItems::MAX_LINKED_ITEMS_PER_PAGES);
	}

	/**
	 * returns the assembled request
	 *
	 * @return PlentySoapRequest_GetLinkedItems
	 */
	public function getRequest()
	{
		$request = new PlentySoapRequest_GetLinkedItems();
		$request->ItemsList = new ArrayOfPlentysoapobject_getlinkeditems();
		$request->ItemsList->item = array();

		foreach ($this->items as $itemID)
		{
			$linkedItem = new PlentySoapObject_GetLinkedItems();
			$linkedItem->ItemID = $itemID;

			$request->ItemsList->item[] = $linkedItem;
		}

		return $request;
	}
}
