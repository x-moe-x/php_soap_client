<?php
require_once ROOT . 'includes/FillObjectFromArray.php';
require_once ROOT . 'includes/RequestContainer.class.php';

/**
 * Class RequestContainer_SetContentPages
 */
class RequestContainer_SetContentPages extends RequestContainer
{

	/**
	 * returns the assembled request
	 *
	 * @return PlentySoapRequest_SetContentPages
	 */
	public function getRequest()
	{
		$result = new PlentySoapRequest_SetContentPages();
		$result->ContentPages = new ArrayOfPlentysoapobject_contentpage();
		$result->ContentPages->item = array();

		foreach ($this->items as &$item)
		{
			$contentPage = new PlentySoapObject_ContentPage();
			fillObjectFromArray($contentPage,$item);
			$result->ContentPages->item[] = $contentPage;
		}

		return $result;
	}
}
