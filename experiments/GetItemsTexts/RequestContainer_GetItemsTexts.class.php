<?php
require_once ROOT . 'includes/RequestContainer.class.php';

/**
 * Class RequestContainer_GetItemsTexts
 */
class RequestContainer_GetItemsTexts extends RequestContainer
{
	/**
	 * returns the assembled request
	 *
	 * @return PlentySoapRequest_GetItemsTexts
	 */
	public function getRequest()
	{
		$request = new PlentySoapRequest_GetItemsTexts();
		$request->ItemsList = new ArrayOfPlentysoapobject_requestitems();
		$request->ItemsList->item = array();

		foreach ($this->items as $item)
		{
			$itemRequest = new PlentySoapObject_RequestItems();

			fillObjectFromArray($itemRequest, array(
				'ExternalItemNumer' => null,
				'ItemId'            => $item,
				'ItemNumber'        => null,
				'Lang'              => 'de',
			));

			$request->ItemsList->item[] = $itemRequest;
		}

		return $request;
	}
}
