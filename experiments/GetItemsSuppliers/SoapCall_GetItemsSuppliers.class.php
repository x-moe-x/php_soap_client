<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'RequestContainer_GetItemsSuppliers.class.php';

/**
 * Class SoapCall_GetItemsSuppliers
 */
class SoapCall_GetItemsSuppliers extends PlentySoapCall
{

	/**
	 * @var int
	 */
	const MAX_SUPPLIERS_PER_PAGE = 50;

	/**
	 * Used to prepare bulk insertion to db
	 *
	 * @var array
	 */
	private $storeData;

	/**
	 * Store articles with no data
	 *
	 * @var array
	 */
	private $noDataArticles;

	/**
	 * @return SoapCall_GetItemsSuppliers
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);

		$this->storeData = array();
		$this->noDataArticles = array();

		// clear ItemSuppliers db before start so there's no old leftover
		DBQuery::getInstance()->truncate('TRUNCATE TABLE ItemSuppliers');
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
			$result = DBQuery::getInstance()->select('SELECT ItemID FROM ItemsBase');

			// for every 50 ItemIDs ...
			for ($page = 0; $page < ceil($result->getNumRows() / self::MAX_SUPPLIERS_PER_PAGE); $page++)
			{

				// ... perpare a separate request ...
				$preparedRequest = new RequestContainer_GetItemsSuppliers();
				while (!$preparedRequest->isFull() && $current = $result->fetchAssoc())
				{
					$preparedRequest->add($current['ItemID']);
				}

				// ... then do soap call ...
				$response = $this->getPlentySoap()->GetItemsSuppliers($preparedRequest->getRequest());

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
	 * process whole PlentySoapResponse_GetItemsSuppliers record from plenty
	 *
	 * @param PlentySoapResponse_GetItemsSuppliers $response
	 *
	 * @return void
	 */
	private function responseInterpretation(PlentySoapResponse_GetItemsSuppliers $response)
	{
		if (!is_null($response->ItemsSuppliersList))
		{
			if (is_array($response->ItemsSuppliersList->item))
			{

				/** @noinspection PhpParamsInspection */
				$countRecords = count($response->ItemsSuppliersList->item);
				$this->getLogger()->debug(__FUNCTION__ . " fetched $countRecords supplier records from ItemID: {$response->ItemsSuppliersList->item[0]->ItemID} to {$response->ItemsSuppliersList->item[$countRecords - 1]->ItemID}");

				/** @var PlentySoapObject_ItemsSuppliersList $itemsSuppliersList */
				foreach ($response->ItemsSuppliersList->item AS &$itemsSuppliersList)
				{
					$this->processSupplier($itemsSuppliersList);
				}
			} else
			{
				if (!is_null($response->ItemsSuppliersList->item))
				{

					$this->getLogger()->debug(__FUNCTION__ . " fetched supplier record for ItemID: {$response->ItemsSuppliersList->item->ItemID}");

					$this->processSupplier($response->ItemsSuppliersList->item);
				}
			}
		}

		// process potential response messages
		foreach ($response->ResponseMessages->item as $oPlentySoapResponseMessage)
		{
			$this->processResponseMessage($oPlentySoapResponseMessage);
		}

	}

	/**
	 * process PlentySoapObject_ItemsSuppliersList for a single itemID
	 *
	 * @param PlentySoapObject_ItemsSuppliersList $itemsSuppliersList
	 *
	 * @return void
	 */
	private function processSupplier($itemsSuppliersList)
	{
		if (is_array($itemsSuppliersList->ItemsSuppliers->item))
		{
			/** @var PlentySoapObject_ItemsSuppliers $itemsSupplier */
			foreach ($itemsSuppliersList->ItemsSuppliers->item as $itemsSupplier)
			{
				// sanity check
				if (!$itemsSuppliersList->ItemID === $itemsSupplier->ItemID)
				{
					$this->getLogger()->debug(__FUNCTION__ . " {$itemsSuppliersList->ItemID} != {$itemsSupplier->ItemID}");
					die();
				}

				// prepare for storing
				$this->storeData[] = (array)$itemsSupplier;
			}
		} else
		{
			// sanity check
			if (!$itemsSuppliersList->ItemID === $itemsSuppliersList->ItemsSuppliers->item->ItemID)
			{
				$this->getLogger()->debug(__FUNCTION__ . " {$itemsSuppliersList->ItemID} != {$itemsSuppliersList->ItemsSuppliers->item->ItemID}");
				die();
			}

			// prepare for storing
			$this->storeData[] = (array)$itemsSuppliersList->ItemsSuppliers->item;
		}
	}

	/**
	 * process PlentySoapResponseMessage
	 *
	 * @param PlentySoapResponseMessage $responseMessage
	 *
	 * @return void
	 */
	private function processResponseMessage($responseMessage)
	{
		switch ($responseMessage->Code)
		{
			case 100 :
				// everything ok
				break;
			case 110 :
				// no data warning
				$this->noDataArticles[] = $responseMessage->IdentificationValue;
				break;
			case 800 :
				// error
				if ($responseMessage->IdentificationKey == 'ItemID')
				{
					$this->getLogger()->debug(__FUNCTION__ . ' error ' . $responseMessage->Code . ': ' . $responseMessage->IdentificationKey . ': ' . $responseMessage->IdentificationValue);
				} else
				{
					$this->getLogger()->debug(__FUNCTION__ . ' error ' . $responseMessage->Code . ': An error occurred while retrieving item supplier list');
				}
				break;
			case 810 :
				// limit error
				$this->getLogger()->debug(__FUNCTION__ . ' error ' . $responseMessage->Code . ': Only 50 item supplier lists can be retrieved at the same time');
				break;
			default :
				$this->getLogger()->debug(__FUNCTION__ . ' unknown error: ' . $responseMessage->Code);
		}
	}

	/**
	 * bulk insert/update retrieved data except ItemSupplierPrice and LastUpdate
	 * this is a workaround for a plenty bug which prevents correct values of price and last update being sent
	 *
	 * @return void
	 */
	private function storeToDB()
	{
		$storeDataCount = count($this->storeData);
		$noDataCount = count($this->noDataArticles);

		if ($storeDataCount > 0)
		{
			DBQuery::getInstance()->insert('INSERT INTO `ItemSuppliers`' . DBUtils::buildMultipleInsert($this->storeData) . 'ON DUPLICATE KEY UPDATE ItemSupplierRowID=VALUES(ItemSupplierRowID),IsRebateAllowed=VALUES(IsRebateAllowed),Priority=VALUES(Priority),Rebate=VALUES(Rebate),SupplierDeliveryTime=VALUES(SupplierDeliveryTime),SupplierItemNumber=VALUES(SupplierItemNumber),SupplierMinimumPurchase=VALUES(SupplierMinimumPurchase),VPE=VALUES(VPE)');

			$this->getLogger()->debug(__FUNCTION__ . " storing $storeDataCount records of supplier data");
		}

		if ($noDataCount > 0)
		{
			$this->getLogger()->debug(__FUNCTION__ . ' no data found for items: ' . implode(', ', $this->noDataArticles));
		}
	}

}
