<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'RequestContainer_SetLinkedItems.class.php';

/**
 * Class SoapCall_SetLinkedItems
 */
class SoapCall_SetLinkedItems extends PlentySoapCall
{

	/**
	 * @var int
	 */
	const MAX_LINKED_ITEMS_PER_PAGE = 50;

	/**
	 * overrides PlentySoapCall's execute() method
	 *
	 * @return void
	 */
	public function execute()
	{
		try
		{
			// prepare request
			$preparedRequest = new RequestContainer_SetLinkedItems(12, 'similar');

			// add items
			$preparedRequest->add(15,1);

			/** @var PlentySoapResponse_SetLinkedItems $response */
			$response = $this->getPlentySoap()->SetLinkedItems($preparedRequest->getRequest());


			if ($response->Success == true)
			{
			} else
			{
				// ... otherwise log error and try next request
				$this->debug(__FUNCTION__ . ' Request Error');
			}
		} catch (Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}
}
