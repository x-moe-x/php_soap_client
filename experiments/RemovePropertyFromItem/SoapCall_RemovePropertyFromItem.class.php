<?php
require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'RequestContainer_RemovePropertyFromItem.class.php';

/**
 * Class SoapCall_RemovePropertyFromItem
 */
class SoapCall_RemovePropertyFromItem extends PlentySoapCall
{
	/**
	 * @var int
	 */
	const MAX_PROPERTIES_FROM_ITEMS_PER_PAGE = 100;

	/**
	 * @return SoapCall_RemovePropertyFromItem
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);
	}

	/**
	 *
	 */
	public function execute()
	{
		$this->debug(__FUNCTION__ . ' remove properties to items...');
		try
		{
			// 1. get all property-item-pair records
			$unwrittenPropertyFromItemData = DBQuery::getInstance()->select($this->getQuery());

			$this->getLogger()->debug(__FUNCTION__ . ' found ' . $unwrittenPropertyFromItemData->getNumRows() . ' records...');

			// 2. for every 100 updates ...
			for ($page = 0, $maxPage = ceil($unwrittenPropertyFromItemData->getNumRows() / self::MAX_PROPERTIES_FROM_ITEMS_PER_PAGE); $page < $maxPage; $page++)
			{
				// ... prepare a separate request ...
				$request = new RequestContainer_RemovePropertyFromItem(self::MAX_PROPERTIES_FROM_ITEMS_PER_PAGE);

				// ... fill in data
				$writtenUpdates = array();
				while (!$request->isFull() && ($unwrittenRemoveData = $unwrittenPropertyFromItemData->fetchAssoc()))
				{
					$request->add(array(
						'ItemId'     => $unwrittenRemoveData['ItemId'],
						'PropertyId' => $unwrittenRemoveData['PropertyId'],
					));

					$writtenUpdates[] = $unwrittenRemoveData['id'];
				}

				// 3. write them back via soap
				$response = $this->getPlentySoap()->RemovePropertyFromItem($request->getRequest());

				// 4. if successful ...
				if ($response->Success == true)
				{
					// ... then delete specified elements from setCurrentStocks
					DBQuery::getInstance()->delete('DELETE FROM RemovePropertyFromItem WHERE id IN (' . implode(',', $writtenUpdates) . ')');
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

	private function getQuery()
	{
		return 'SELECT
	id,
	ItemId,
	PropertyId
FROM
	RemovePropertyFromItem';
	}
}
