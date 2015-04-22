<?php
require_once ROOT . 'includes/FillObjectFromArray.php';
require_once ROOT . 'includes/RequestContainer.class.php';

/**
 * Class RequestContainer_SetItemsTexts
 */
class RequestContainer_SetItemsTexts extends RequestContainer
{
	/**
	 * @return RequestContainer_SetItemsTexts
	 */
	public function __construct()
	{
		parent::__construct(SoapCall_SetItemsTexts::MAX_ITEMS_PER_PAGES);
	}

	/**
	 * returns the assembled request
	 *
	 * @return PlentySoapRequest_SetItemsTexts
	 */
	public function getRequest()
	{
		$request = new PlentySoapRequest_SetItemsTexts();

		$request->ItemsList = new ArrayOfPlentysoapobject_setitemstexts();
		$request->ItemsList->item = array();

		foreach ($this->items as $item)
		{
			$texts = new PlentySoapObject_SetItemsTexts();
			fillObjectFromArray($texts, $item);

			$request->ItemsList->item[] = $texts;
		}

		return $request;
	}
}
