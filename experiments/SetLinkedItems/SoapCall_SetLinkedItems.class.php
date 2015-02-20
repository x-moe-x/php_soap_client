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
			// select every item from db
			$setLinkedItemsDbResult = DBQuery::getInstance()->select($this->getQuery());

			// for every main item and relationship ...
			while ($row = $setLinkedItemsDbResult->fetchAssoc())
			{
				// ... prepare a separate request ...
				$preparedRequest = new RequestContainer_SetLinkedItems($row['ItemID'], $row['Relationship']);

				// ... add linked items ...
				$linkedItems = explode(',', $row['LinkedItemIDList']);
				foreach ($linkedItems as &$linkedItem)
				{
					$preparedRequest->add($linkedItem);
				}

				// ... then do soap call
				/** @var PlentySoapResponse_SetLinkedItems $response */
				$response = $this->getPlentySoap()->SetLinkedItems($preparedRequest->getRequest());

				if ($response->Success == true)
				{
					$this->debug(__FUNCTION__ . ' successfully updated Item ' . $row['ItemID'] . '\'s linked items');
				} else
				{
					// ... otherwise log error and try next request
					$this->debug(__FUNCTION__ . ' Request Error for Item ' . $row['ItemID']);
				}
			}
		} catch (Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}

	/**
	 * @return string
	 */
	private function getQuery()
	{
		return 'SELECT
  ItemID,
  CAST(
      GROUP_CONCAT(
          CAST(LinkedItemID AS SIGNED) ORDER BY LinkedItemID ASC
          SEPARATOR ",") AS CHAR) AS LinkedItemIDList,
  RelationShip
FROM `SetLinkedItems`
GROUP BY
  ItemID,
  RelationSHip';
	}
}
