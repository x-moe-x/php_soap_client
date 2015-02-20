<?php

/**
 * Class Request_GetContentPage
 */
class Request_GetContentPage
{
	/**
	 * Prepares a request for a specific content page in a specific language
	 *
	 * @param int    $contentPageId the content page's numeric id
	 * @param int    $storeId       the store's numeric id
	 * @param string $language      the language's shortcut
	 *
	 * @return PlentySoapRequest_GetContentPage
	 */
	public static function getRequest($contentPageId, $storeId = 0, $language = 'de')
	{
		$request = new PlentySoapRequest_GetContentPage();

		$request->ContentPageID = $contentPageId;
		$request->Lang = $language;
		$request->StoreID = $storeId;

		return $request;
	}
}