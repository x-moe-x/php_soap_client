<?php
require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'RequestContainer_SetPriceSets.class.php';

/**
 * Class SoapCall_SetPriceSets
 */
class SoapCall_SetPriceSets extends PlentySoapCall
{
	/**
	 * @var int
	 */
	const MAX_PRICE_SETS_PER_CALL = 100;

	/**
	 *
	 */
	public function execute()
	{
		try
		{
			// get all new price sets to be written
			$setPriceSetsDBResult = DBQuery::getInstance()->select('SELECT * FROM `SetPriceSets`');

			// for every 100 price sets ...
			for ($page = 0, $maxPage = ceil($setPriceSetsDBResult->getNumRows() / self::MAX_PRICE_SETS_PER_CALL); $page < $maxPage; $page++)
			{
				$this->debug(__FUNCTION__ . ': writing ' . $setPriceSetsDBResult->getNumRows() . ' new price sets');

				// ... prepare a separate request ...
				$requestContainer = new RequestContainer_SetPriceSets(self::MAX_PRICE_SETS_PER_CALL);

				// ... fill in data
				while (!$requestContainer->isFull() && ($priceSet = $setPriceSetsDBResult->fetchAssoc()))
				{
					$requestContainer->add($priceSet);
				}

				$this->debug(__FUNCTION__ . ' writing page ' . ($page + 1) . ' of ' . $maxPage);

				// do soap call to plenty
				$response = $this->getPlentySoap()->SetPriceSets($requestContainer->getRequest());

				// ... if successful ...
				if ($response->Success == true)
				{
					// ... be quiet ...
				} else
				{
					// ... otherwise log error and try next request
					$this->getLogger()->debug(__FUNCTION__ . ' Request Error');
				}
			}

			// cleanup
			DBQuery::getInstance()->truncate("TRUNCATE SetPriceSets");
		} catch (Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}
}
