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
		return $request;
	}
}
