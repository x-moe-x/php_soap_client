<?php
require_once ROOT . 'includes/RequestContainer.class.php';

/**
 * Class RequestContainer_RemovePropertyFromItem
 */
class RequestContainer_RemovePropertyFromItem extends RequestContainer
{
	/**
	 * creates a new RequestContainer_RemovePropertyFromItem with a specified capacity
	 *
	 * @param int $capacity
	 *
	 * @return RequestContainer_RemovePropertyFromItem
	 */
	public function __construct($capacity)
	{
		parent::__construct($capacity);
	}

	/**
	 * returns the assembled request
	 *
	 * @return PlentySoapRequest_RemovePropertyFromItem
	 */
	public function getRequest()
	{
		$request = new PlentySoapRequest_RemovePropertyFromItem();
		$request->RemovePropertyFromItemList = new ArrayOfPlentysoapobject_removepropertyfromitem();
		$request->RemovePropertyFromItemList->item = array();

		foreach ($this->items as $removePropertyFromItemData)
		{
			$removePropertyFromItem = new PlentySoapObject_RemovePropertyFromItem();
			fillObjectFromArray($removePropertyFromItem, $removePropertyFromItemData);

			$request->RemovePropertyFromItemList->item[] = $removePropertyFromItem;
		}

		return $request;
	}
}
