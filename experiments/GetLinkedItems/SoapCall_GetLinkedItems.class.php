<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once ROOT . 'includes/DBUtils2.class.php';
require_once 'Request_GetLinkedItems.class.php';

/**
 * Class SoapCall_GetLinkedItems
 */
class SoapCall_GetLinkedItems extends PlentySoapCall
{

	/**
	 * @var int
	 */
	const MAX_LINKED_ITEMS_PER_PAGES = 100;

	/**
	 * @var array
	 */
	private $aStoreData;

	/**
	 * @return SoapCall_GetLinkedItems
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);

		$this->aStoreData = array();

		// clear LinkedItems db before start so there's no old leftover
		DBQuery::getInstance()->truncate('TRUNCATE TABLE LinkedItems');
	}

	/**
	 * overrides PlentySoapCall's execute() method
	 *
	 * @return void
	 */
	public function execute()
	{
		try
		{
			// get all possible ItemIDs
			$itemIdDbResult = DBQuery::getInstance()->select('SELECT ItemID FROM ItemsBase');

			// for every 100 ItemIDs ...
			for ($page = 0, $maxPage = ceil($itemIdDbResult->getNumRows() / self::MAX_LINKED_ITEMS_PER_PAGES); $page < $maxPage; $page++)
			{

				// ... prepare a separate request ...
				$preparedRequest = new Request_GetLinkedItems();
				while (!$preparedRequest->isFull() && $itemID = $itemIdDbResult->fetchAssoc())
				{
					$preparedRequest->addItem($itemID['ItemID']);
				}

				// ... then do soap call ..
				$response = $this->getPlentySoap()->GetLinkedItems($preparedRequest->getRequest());

				// ... if successfull ...
				if ($response->Success == true)
				{
					// ... then process response
					$this->responseInterpretation($response);
				} else
				{
					// ... otherwise log error and try next request
					$this->getLogger()->debug(__FUNCTION__ . ' Request Error');
				}
			}

			// when done store all retrieved data to db
			$this->storeToDB();

		} catch (Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}

	/**
	 * @param PlentySoapResponse_GetLinkedItems $response
	 */
	private function responseInterpretation(PlentySoapResponse_GetLinkedItems $response)
	{
		if (!is_null($response->Items))
		{
			if (is_array($response->Items->item))
			{
				/** @var PlentySoapResponseObject_GetLinkedItems $linkedItemsRecord */
				foreach ($response->Items->item as &$linkedItemsRecord)
				{
					$this->processLinkedItemsRecord($linkedItemsRecord);
				}
			} else
			{
				if (!is_null($response->Items->item))
				{
					$this->processLinkedItemsRecord($response->Items->item);
				}
			}

		}
	}

	/**
	 * @param PlentySoapResponseObject_GetLinkedItems $linkedItemsRecord
	 */
	private function processLinkedItemsRecord($linkedItemsRecord)
	{
		if (!is_null($linkedItemsRecord->LinkedItems))
		{
			if (is_array($linkedItemsRecord->LinkedItems->item))
			{
				/** @var PlentySoapObject_GetLinkedItems $linkedItem */
				foreach ($linkedItemsRecord->LinkedItems->item as &$linkedItem)
				{
					$this->aStoreData[] = array(
						'ItemID'                   => $linkedItemsRecord->ItemID,
						'ExternalItemID'           => $linkedItemsRecord->ExternalItemID,
						'LinkedItemID'             => $linkedItem->ItemID,
						'LinkedItemExternalItemID' => $linkedItem->ExternalItemID,
						'Relationship'             => $linkedItem->Relationship,
					);
				}
			} else
			{
				$this->aStoreData[] = array(
					'ItemID'                   => $linkedItemsRecord->ItemID,
					'ExternalItemID'           => $linkedItemsRecord->ExternalItemID,
					'LinkedItemID'             => $linkedItemsRecord->LinkedItems->item->ItemID,
					'LinkedItemExternalItemID' => $linkedItemsRecord->LinkedItems->item->ExternalItemID,
					'Relationship'             => $linkedItemsRecord->LinkedItems->item->Relationship,
				);
			}
		}
	}

	private function storeToDB()
	{
		$storeDataCount = count($this->aStoreData);

		if ($storeDataCount > 0)
		{
			DBQuery::getInstance()->insert('INSERT INTO LinkedItems' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->aStoreData));

			$this->debug(__FUNCTION__ . " storing $storeDataCount records of linked item's data");
		}
	}
}
