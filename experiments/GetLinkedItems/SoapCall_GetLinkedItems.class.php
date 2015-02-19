<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetLinkedItems.class.php';

/**
 * Class SoapCall_GetLinkedItems
 */
class SoapCall_GetLinkedItems extends PlentySoapCall
{

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

			// prepare request
			$preparedRequest = new Request_GetLinkedItems();
			while ($itemID = $itemIdDbResult->fetchAssoc())
			{
				$preparedRequest->addItem($itemID['ItemID']);
			}

			// do soap call
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
						'ItemID'       => $linkedItemsRecord->ItemID,
						'LinkedItemID' => $linkedItem->ItemID,
						'Relationship' => $linkedItem->Relationship,
					);
				}
			} else
			{
				$this->aStoreData[] = array(
					'ItemID'       => $linkedItemsRecord->ItemID,
					'LinkedItemID' => $linkedItemsRecord->LinkedItems->item->ItemID,
					'Relationship' => $linkedItemsRecord->LinkedItems->item->Relationship,
				);
			}
		}
	}

	private function storeToDB()
	{
		echo "Storing " . count($this->aStoreData) . " records of linked items data\n";
	}
}
