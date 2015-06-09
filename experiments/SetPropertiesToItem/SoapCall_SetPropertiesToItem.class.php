<?php
require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'RequestContainer_SetPropertiesToItem.class.php';

/**
 * Class SoapCall_AddPropertyToItem
 */
class SoapCall_SetPropertiesToItem extends PlentySoapCall
{
	/**
	 * @var int
	 */
	const MAX_ITEMS_TO_PROPERTIES_PER_PAGE = 100;

	/**
	 * @return SoapCall_SetPropertiesToItem
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);
	}

	public function execute()
	{
		$this->debug(__FUNCTION__ . ' writing properties to items');
		try
		{
			// 1. get all tuples (itemID, propertyID, propertyItemValue)
			$unwrittenItemToPropertyRecords = DBQuery::getInstance()->select('SELECT ItemId, Lang, PropertyId, PropertyItemValue FROM SetPropertiesToItem');

			$this->getLogger()->debug(__FUNCTION__ . ' found ' . $unwrittenItemToPropertyRecords->getNumRows() . ' records...');

			// 2. for every 100 updates ...
			for ($page = 0, $maxPage = ceil($unwrittenItemToPropertyRecords->getNumRows() / self::MAX_ITEMS_TO_PROPERTIES_PER_PAGE); $page < $maxPage; $page++)
			{
				// ... prepare a separate request ...
				$request = new RequestContainer_SetPropertiesToItem(self::MAX_ITEMS_TO_PROPERTIES_PER_PAGE);

				// ... fill in data
				while (!$request->isFull() && ($unwrittenItemToPropertyRecord = $unwrittenItemToPropertyRecords->fetchAssoc()))

				{

					$request->add(array(
						'ItemId'            => $unwrittenItemToPropertyRecord['ItemId'],
						'Lang'              => $unwrittenItemToPropertyRecord['Lang'],
						'PropertyId'        => $unwrittenItemToPropertyRecord['PropertyId'],
						'PropertyItemValue' => $unwrittenItemToPropertyRecord['PropertyItemValue'],
					));
				}

				// 3. write them back via soap
				$response = $this->getPlentySoap()->SetPropertiesToItem($request->getRequest());

				// 4. if successful ...
				if ($response->Success == true)
				{
					// ... do nothing
				} else
				{
					die(print_r($response));
					// ... otherwise log error and try next request
					$this->getLogger()->debug(__FUNCTION__ . ' Request Error');
				}
			}

		} catch (Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}
}
