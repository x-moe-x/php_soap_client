<?php

require_once ROOT . 'includes/FillObjectFromArray.php';

/**
 * Class Request_GetProperties
 */
class Request_GetProperties
{
	/**
	 * @var int
	 */
	private $callItemsLimit;

	public function __construct($callItemsLimit)
	{
		$this->callItemsLimit = $callItemsLimit;
	}

	/**
	 * @param int $lastUpdate
	 * @param int $currentTime
	 * @param int $page
	 *
	 * @return PlentySoapRequest_GetProperties
	 */
	public function getRequest($lastUpdate, $currentTime, $page)
	{
		$request = new PlentySoapRequest_GetProperties();

		fillObjectFromArray($request, array(
			'CallItemsLimit'  => $this->callItemsLimit,
			'Lang'            => 'de',
			'LastUpdateFrom'  => $lastUpdate,
			'LastUpdateTill'  => $currentTime,
			'Page'            => $page,
			'PropertyGroupID' => null,
		));

		return $request;
	}
}