<?php
require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetCurrentStocks.class.php';
require_once ROOT . 'includes/SKUHelper.php';
require_once ROOT . 'includes/DBUtils2.class.php';
require_once ROOT . 'includes/DBLastUpdate.php';
require_once ROOT . 'api/ApiHelper.class.php';

/**
 * Class SoapCall_GetCurrentStocks
 */
class SoapCall_GetCurrentStocks extends PlentySoapCall
{

	/**
	 * @var int
	 */
	private $page = 0;

	/**
	 * @var int
	 */
	private $pages = -1;

	/**
	 * @var int
	 */
	private $startAtPage = 0;

	/**
	 * @var array
	 */
	private $stockRecords;

	/**
	 * @var PlentySoapRequest_GetCurrentStocks
	 */
	private $plentySoapRequest = null;

	/**
	 * @return SoapCall_GetCurrentStocks
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);

		$this->stockRecords = array();
	}

	public function execute()
	{
		$warehouses = ApiHelper::getWarehouseList();

		foreach ($warehouses as $warehouse)
		{
			$this->page = 0;
			$this->pages = -1;
			$this->startAtPage = 0;
			$this->executeForWarehouse($warehouse['id']);
		}
	}

	/**
	 * @param int $warehouseId
	 */
	private function executeForWarehouse($warehouseId)
	{

		list($lastUpdate, $currentTime, $this->startAtPage) = lastUpdateStart(__CLASS__ . "_for_wh_$warehouseId");

		if ($this->pages == -1)
		{
			try
			{
				$this->plentySoapRequest = Request_GetCurrentStocks::getRequest($lastUpdate, $this->startAtPage, $warehouseId);

				if ($this->startAtPage > 0)
				{
					$this->debug(__FUNCTION__ . " Starting at page " . $this->startAtPage);
				}

				/*
				 * do soap call
				 */
				$response = $this->getPlentySoap()->GetCurrentStocks($this->plentySoapRequest);

				if (($response->Success == true) && isset($response->CurrentStocks))
				{
					// request successful, processing data..

					/** @noinspection PhpParamsInspection */
					$stocksFound = count($response->CurrentStocks->item);
					$pagesFound = $response->Pages;

					$this->debug(__FUNCTION__ . ' Request Success - stock records found : ' . $stocksFound . ' / pages : ' . $pagesFound . ', page : ' . ($this->page + 1));

					// process response
					$this->responseInterpretation($response);

					if ($pagesFound > $this->page)
					{
						$this->page = $this->startAtPage + 1;
						$this->pages = $pagesFound;

						lastUpdatePageUpdate(__CLASS__ . "_for_wh_$warehouseId", $this->page);
						$this->executePagesForWarehouse($warehouseId);
					}
				} else
				{
					if (($response->Success == true) && !isset($response->CurrentStocks))
					{
						// request successful, but no data to process
						$this->debug(__FUNCTION__ . ' Request Success -  but no matching stock records found');
					} else
					{
						$this->debug(__FUNCTION__ . ' Request Error');
					}
				}
			} catch (Exception $e)
			{
				$this->onExceptionAction($e);
			}
		} else
		{
			$this->executePagesForWarehouse($warehouseId);
		}

		$this->storeToDB();
		lastUpdateFinish($currentTime, __CLASS__ . "_for_wh_$warehouseId");
	}

	/**
	 * @param PlentySoapResponse_GetCurrentStocks $response
	 */
	private function responseInterpretation(PlentySoapResponse_GetCurrentStocks $response)
	{
		if (is_array($response->CurrentStocks->item))
		{

			/** @var PlentySoapObject_GetCurrentStocks $stockRecord */
			foreach ($response->CurrentStocks->item AS $stockRecord)
			{
				$this->processStockRecord($stockRecord);
			}
		} else
		{
			$this->processStockRecord($response->CurrentStocks->item);
		}
	}

	/**
	 * @param PlentySoapObject_GetCurrentStocks $stockRecord
	 */
	private function processStockRecord($stockRecord)
	{
		list($itemID, , $attributeValueSetId) = SKU2Values($stockRecord->SKU);

		$this->stockRecords[] = array(
			'ItemID'               => $itemID,
			'AttributeValueSetID'  => $attributeValueSetId,
			'WarehouseID'          => $stockRecord->WarehouseID,
			'EAN'                  => $stockRecord->EAN,
			'EAN2'                 => $stockRecord->EAN2,
			'EAN3'                 => $stockRecord->EAN3,
			'EAN4'                 => $stockRecord->EAN4,
			'VariantEAN'           => $stockRecord->VariantEAN,
			'VariantEAN2'          => $stockRecord->VariantEAN2,
			'VariantEAN3'          => $stockRecord->VariantEAN3,
			'VariantEAN4'          => $stockRecord->VariantEAN4,
			'WarehouseType'        => $stockRecord->WarehouseType,
			'StorageLocationID'    => $stockRecord->StorageLocationID,
			'StorageLocationName'  => $stockRecord->StorageLocationName,
			'StorageLocationStock' => $stockRecord->StorageLocationStock,
			'PhysicalStock'        => $stockRecord->PhysicalStock,
			'NetStock'             => $stockRecord->NetStock,
			'AveragePrice'         => $stockRecord->AveragePrice
		);
	}

	/**
	 * @param $warehouseId
	 *
	 * @return void
	 */
	private function executePagesForWarehouse($warehouseId)
	{
		while ($this->pages > $this->page)
		{
			$this->plentySoapRequest->Page = $this->page;
			try
			{
				$response = $this->getPlentySoap()->GetCurrentStocks($this->plentySoapRequest);

				if ($response->Success == true)
				{
					/** @noinspection PhpParamsInspection */
					$stocksFound = count($response->CurrentStocks->item);
					$this->debug(__FUNCTION__ . ' Request Success - stock records found : ' . $stocksFound . ' / page : ' . ($this->page + 1));

					// auswerten
					$this->responseInterpretation($response);
				}

				$this->page++;
				lastUpdatePageUpdate(__CLASS__ . "_for_wh_$warehouseId", $this->page);

			} catch (Exception $e)
			{
				$this->onExceptionAction($e);
			}
		}
	}

	private function storeToDB()
	{
		// insert stock records
		$countStockRecords = count($this->stockRecords);

		if ($countStockRecords > 0)
		{
			$this->getLogger()->info(__FUNCTION__ . " : storing $countStockRecords stock records ...");
			DBQuery::getInstance()->insert('INSERT INTO `CurrentStocks`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->stockRecords));
		}

		$this->stockRecords = array();
	}
}
