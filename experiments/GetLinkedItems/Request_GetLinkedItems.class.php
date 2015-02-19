<?php

/**
 * Class Request_GetLinkedItems
 */
class Request_GetLinkedItems
{

	/**
	 * @var int[]
	 */
	private $aItemIDs;

	/**
	 * @return Request_GetLinkedItems
	 */
	public function __construct()
	{
		$this->aItemIDs = array();
	}

	/**
	 * @param int $itemID
	 */
	public function addItem($itemID)
	{
		if (count($this->aItemIDs) < SoapCall_GetLinkedItems::MAX_LINKED_ITEMS_PER_PAGES)
		{
			$this->aItemIDs[] = $itemID;
		}
	}

	/**
	 * @return bool
	 */
	public function isFull()
	{
		return count($this->aItemIDs) === SoapCall_GetLinkedItems::MAX_LINKED_ITEMS_PER_PAGES;
	}

	/**
	 * @return PlentySoapRequest_GetLinkedItems
	 */
	public function getRequest()
	{
		$request = new PlentySoapRequest_GetLinkedItems();
		$request->ItemsList = new ArrayOfPlentysoapobject_getlinkeditems();
		$request->ItemsList->item = array();

		foreach ($this->aItemIDs as $itemID)
		{
			$linkedItem = new PlentySoapObject_GetLinkedItems();
			$linkedItem->ItemID = $itemID;

			$request->ItemsList->item[] = $linkedItem;
		}

		return $request;
	}
}