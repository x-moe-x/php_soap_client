<?php

require_once ROOT . 'includes/FillObjectFromArray.php';

/**
 * Class Request_GetItemCategoryCatalog.class
 */
class Request_GetItemCategoryCatalog
{
	/**
	 * @param int $lastUpdate
	 * @param int $currentTime
	 * @param int $page
	 *
	 * @return PlentySoapRequest_GetItemCategoryCatalog
	 */
	public static function getRequest($lastUpdate, $currentTime, $page)
	{
		$request = new PlentySoapRequest_GetItemCategoryCatalog();

		fillObjectFromArray($request, array(
			'CallItemsLimit' => null,
			'CategoryID'     => null,
			'Lang'           => null,
			'LastUpdateFrom' => $lastUpdate,
			'LastUpdateTo'   => $currentTime,
			'Page'           => $page,
			'StoreID'        => null,
		));

		return $request;
	}
}
