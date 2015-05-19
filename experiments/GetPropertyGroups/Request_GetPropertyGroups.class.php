<?php

class Request_GetPropertyGroups
{
	/**
	 * @var int
	 */
	private $callItemsLimit;

	/**
	 * @param int $callItemsLimit
	 */
	public function __construct($callItemsLimit)
	{
		$this->callItemsLimit = $callItemsLimit;
	}

	/**
	 * @param int $lastUpdate
	 * @param int $currentTime
	 * @param int $page
	 *
	 * @return PlentySoapRequest_GetPropertyGroups
	 */
	public function getRequest($lastUpdate, $currentTime, $page)
	{
		$request = new PlentySoapRequest_GetPropertyGroups();

		return $request;
	}
}