<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'RequestContainer_SetItemAttributeVariants.class.php';

/**
 * Class SoapCall_SetItemAttributeVariants
 */
class SoapCall_SetItemAttributeVariants extends PlentySoapCall
{
	/**
	 *
	 */
	const MAX_ITEMS_PER_PAGES = 100;

	public function execute()
	{
		$this->debug(__FUNCTION__ . ' writing ItemAttributeVariants...');
		try
		{
			// for every given article ...
			for ($itemId = 2137; $itemId <= 2670; $itemId++)
			{
				// ... prepare a separate request ...
				$requestContainer = new RequestContainer_SetItemAttributeVariants($itemId);

				// ... fill in data
				for ($attributeValueId = 22; $attributeValueId <= 28; $attributeValueId++)
				{
					if (!$requestContainer->isFull())
					{
						$requestContainer->add($attributeValueId);
					} else
					{
						throw new RuntimeException("Item $itemId: could not add attributeValueId, container is full");
					}
				}

				$this->debug(__FUNCTION__ . ' writing ItemAttributeVariants for item ' . $itemId);

				// do soap call to plenty
				$response = $this->getPlentySoap()
					->SetItemAttributeVariants($requestContainer->getRequest());

				// ... if successful ...
				if ($response->Success == true)
				{
					// ... be quiet ...
				} else
				{
					// ... otherwise log error and try next request
					$this->getLogger()
						->debug(__FUNCTION__ . ' Request Error for Item '. $itemId);
				}
			}
		} catch (Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}
}