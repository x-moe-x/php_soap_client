<?php

require_once ROOT . 'includes/FillObjectFromArray.php';

/**
 * Class Request_GetItemsImages
 */
class Request_GetItemsImages
{
	/**
	 * @param int $lastUpdate
	 * @param int $currentTime
	 * @param int $page
	 *
	 * @return PlentySoapRequest_GetItemsImages
	 */
	public static function getRequest($lastUpdate, $currentTime, $page)
	{
		$request = new PlentySoapRequest_GetItemsImages();

		fillObjectFromArray($request, array(
			'Page'           => $page,
			'LastUpdateFrom' => $lastUpdate,
			'LastUpdateTo'   => $currentTime,
			'CallItemsLimit' => null,
			'ImageType'      => null,
			'Lang'           => null,
			'ReferenceType'  => null,
			'ReferenceValue' => null,
			'SKU'            => null,
			'UploadedFrom'   => null,
			'UploadedTo'     => null
		));

		return $request;
	}
}
