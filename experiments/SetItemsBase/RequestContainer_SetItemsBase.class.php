<?php
require_once ROOT . 'includes/FillObjectFromArray.php';
require_once ROOT . 'includes/RequestContainer.class.php';

/**
 * Class RequestContainer_SetItemsBase
 */
class RequestContainer_SetItemsBase extends RequestContainer
{

	/**
	 * @return RequestContainer_SetItemsBase
	 */
	public function __construct()
	{
		parent::__construct(SoapCall_SetItemsBase::MAX_ITEMS_PER_PAGES);
	}

	/**
	 * returns the assembled request
	 *
	 * @return PlentySoapRequest_SetItemsBase
	 */
	public function getRequest()
	{
		$request = new PlentySoapRequest_SetItemsBase();

		$request->BaseItems = new ArrayOfPlentysoapobject_setitemsbaseitembase();
		$request->BaseItems->item = array();

		foreach ($this->items as $item)
		{
			$itemBase = new PlentySoapObject_SetItemsBaseItemBase();
			fillObjectFromArray($itemBase, $item);

			$request->BaseItems->item[] = $itemBase;
		}
		return $request;
	}
}
