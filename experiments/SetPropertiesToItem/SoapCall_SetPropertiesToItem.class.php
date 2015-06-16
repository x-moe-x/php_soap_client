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
			$unwrittenItemToPropertyRecords = DBQuery::getInstance()->select('SELECT id, ItemId, Lang, PropertyId, PropertyItemValue FROM SetPropertiesToItem');

			$this->getLogger()->debug(__FUNCTION__ . ' found ' . $unwrittenItemToPropertyRecords->getNumRows() . ' records...');

			// 2. for every 100 updates ...
			for ($page = 0, $maxPage = ceil($unwrittenItemToPropertyRecords->getNumRows() / self::MAX_ITEMS_TO_PROPERTIES_PER_PAGE); $page < $maxPage; $page++)
			{
				$this->getLogger()->debug(__FUNCTION__ . ' page ' . ($page + 1) . ' of ' . $maxPage);
				// ... prepare a separate request ...
				$request = new RequestContainer_SetPropertiesToItem(self::MAX_ITEMS_TO_PROPERTIES_PER_PAGE);

				// ... fill in data
				$writtenUpdates = array();
				while (!$request->isFull() && ($unwrittenItemToPropertyRecord = $unwrittenItemToPropertyRecords->fetchAssoc()))
				{
					$request->add(array(
						'ItemId'            => $unwrittenItemToPropertyRecord['ItemId'],
						'Lang'              => $unwrittenItemToPropertyRecord['Lang'],
						'PropertyId'        => $unwrittenItemToPropertyRecord['PropertyId'],
						'PropertyItemValue' => $unwrittenItemToPropertyRecord['PropertyItemValue'],
					));

					$writtenUpdates[] = $unwrittenItemToPropertyRecord['id'];
				}

				// 3. write them back via soap
				$response = $this->getPlentySoap()->SetPropertiesToItem($request->getRequest());

				// 4. if successful ...
				if ($response->Success == true)
				{
					// ... then delete specified elements from setCurrentStocks
					DBQuery::getInstance()->delete('DELETE FROM SetPropertiesToItem WHERE id IN (' . implode(',', $writtenUpdates) . ')');
				} else
				{
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
