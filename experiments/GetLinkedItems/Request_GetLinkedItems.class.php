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
		$this->aItemIDs[] = $itemID;
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