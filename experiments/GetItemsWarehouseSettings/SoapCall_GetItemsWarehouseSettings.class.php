<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'RequestContainer_GetItemsWarehouseSettings.class.php';
require_once ROOT . 'includes/DBLastUpdate.php';
require_once ROOT . 'includes/SKUHelper.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * Class SoapCall_GetItemsWarehouseSettings
 */
class SoapCall_GetItemsWarehouseSettings extends PlentySoapCall
{

	/**
	 * @var int
	 */
	const MAX_SKU_PER_PAGE = 100;

	/**
	 * @var array
	 */
	private $storeData;

	/**
	 * @var int
	 */
	private $warehouseID = 1;

	/**
	 * @return SoapCall_GetItemsWarehouseSettings
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);
		$this->storeData = array();
	}

	/**
	 * @return void
	 */
	public function execute()
	{
		try
		{
			// get all possible SKUs
			$oDBResult = DBQuery::getInstance()->select($this->getSKUQuery());

			// for every 100 SKUs ...
			for ($page = 0, $maxPages = ceil($oDBResult->getNumRows() / self::MAX_SKU_PER_PAGE); $page < $maxPages; $page++)
			{
				/** @var int $page */

				// ... prepare a seperate request
				$preparedRequest = new RequestContainer_GetItemsWarehouseSettings($this->warehouseID);
				while (!$preparedRequest->isFull() && $current = $oDBResult->fetchAssoc())
				{
					$preparedRequest->add($current['SKU']);
				}

				// ... then do soap call ...
				$response = $this->getPlentySoap()->GetItemsWarehouseSettings($preparedRequest->getRequest());

				// ... if successfull ...
				if (($response->Success == true))
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
	 * @return string SQL-Query to get all pairs of ItemID -> AttributeValueSetID
	 */
	private function getSKUQuery()
	{
		return 'SELECT
CONCAT(
	ItemsBase.ItemID,
	\'-0-\',
    CASE WHEN (AttributeValueSets.AttributeValueSetID IS NULL) THEN
        "0"
    ELSE
        AttributeValueSets.AttributeValueSetID
    END
) AS SKU
FROM
	ItemsBase
LEFT JOIN
	AttributeValueSets
ON
	ItemsBase.ItemID = AttributeValueSets.ItemID';
	}

	/**
	 * @param PlentySoapResponse_GetItemsWarehouseSettings $response
	 */
	private function responseInterpretation(PlentySoapResponse_GetItemsWarehouseSettings $response)
	{
		if (isset($response->ItemList) && is_array($response->ItemList->item))
		{

			/** @noinspection PhpParamsInspection */
			$countRecords = count($response->ItemList->item);
			$this->debug(__FUNCTION__ . " fetched $countRecords warehouse setting records from SKU: {$response->ItemList->item[0]->SKU} to {$response->ItemList->item[$countRecords - 1]->SKU}");

			foreach ($response->ItemList->item as &$warehouseSetting)
			{
				$this->processWarehouseSetting($warehouseSetting);
			}
		} else
		{
			if (isset($response->ItemList))
			{
				$this->debug(__FUNCTION__ . " fetched warehouse setting records for SKU: {$response->ItemList->item->SKU}");

				$this->processWarehouseSetting($response->ItemList->item);
			} else
			{
				$this->debug(__FUNCTION__ . ' fetched no warehouse setting records for current request');
			}
		}
	}

	/**
	 * @param PlentySoapObject_ResponseGetItemsWarehouseSettings $warehouseSetting
	 */
	private function processWarehouseSetting($warehouseSetting)
	{
		list($ItemID, , $AttributeValueSetID) = SKU2Values($warehouseSetting->SKU);

		$this->storeData[] = array(
			'ID'                  => $warehouseSetting->ID,
			'MaximumStock'        => $warehouseSetting->MaximumStock,
			'ReorderLevel'        => $warehouseSetting->ReorderLevel,
			/*  'SKU'					=>	$oWarehouseSetting->SKU,	// replaced with ItemID in combination with AVSI */
			'ItemID'              => $ItemID,
			'AttributeValueSetID' => $AttributeValueSetID,
			/*
			 * 	End of SKU replacement
			 */
			'StockBuffer'         => $warehouseSetting->StockBuffer,
			'StockTurnover'       => $warehouseSetting->StockTurnover,
			'StorageLocation'     => $warehouseSetting->StorageLocation,
			'StorageLocationType' => $warehouseSetting->StorageLocationType,
			'WarehouseID'         => $warehouseSetting->WarehouseID,
			'Zone'                => $warehouseSetting->Zone
		);
	}

	private function storeToDB()
	{
		DBQuery::getInstance()->insert('INSERT INTO `ItemsWarehouseSettings`' . DBUtils::buildMultipleInsert($this->storeData) . 'ON DUPLICATE KEY UPDATE' . DBUtils2::buildOnDuplicateKeyUpdateAll($this->storeData[0]));
	}

}
