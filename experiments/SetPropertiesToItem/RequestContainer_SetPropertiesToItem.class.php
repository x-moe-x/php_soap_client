<?php
require_once ROOT .'includes/RequestContainer.class.php';

/**
 * Class RequestContainer_AddPropertyToItem
 */
class RequestContainer_SetPropertiesToItem extends RequestContainer
{

	/**
	 * creates a new RequestContainer_AddPropertyToItem with a specified capacity
	 *
	 * @param int $capacity
	 *
	 * @return RequestContainer_SetPropertiesToItem
	 */
	public function __construct($capacity)
	{
		parent::__construct($capacity);
	}

	/**
	 * returns the assembled request
	 *
	 * @return RequestContainer_SetPropertiesToItem
	 */
	public function getRequest()
	{
		$request = new PlentySoapRequest_SetPropertiesToItem();

		$request->PropertyToItemList = new ArrayOfPlentysoapobject_setpropertytoitem();
		$request->PropertyToItemList->item = array();

		foreach ($this->items as $propertyToItemData)
		{
			$propertyToItem = new PlentySoapObject_SetPropertyToItem();
			fillObjectFromArray($propertyToItem, $propertyToItemData);

			$request->PropertyToItemList->item[] = $propertyToItem;
		}
		return $request;
	}
}
