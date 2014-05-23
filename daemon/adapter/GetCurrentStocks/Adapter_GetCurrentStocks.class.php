<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';

/**
 * Sync stock with local datatable.
 * The lastupdate timestamp will be stored in an datatable,
 * so only the new stock will be called.
 * this help you to keep the process running very quick and save traffic.
 *
 * If you run this call at the first time, you will get all stock data.
 * But after the first call - remeber:
 * You can only get data, if new stock informations exists
 *
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class Adapter_GetCurrentStocks extends PlentySoapCall
{
	/**
	 * warehouse id for which stocks would be called
	 *
	 * @var int
	 */
	private $warehouseId = 0;

	/**
	 * the current timestamp needs to be redefined for each execution
	 *
	 * @var int
	 */
	private $currentTimestamp = 0;

	/**
	 * the timestamp of the last query api
	 *
	 * @var int
	 */
	private $lastUpdateTimestamp = 0;

	/**
	 * internal page counter
	 *
	 * @var int
	 */
	private $currentPage = 0;

	public function __construct()
	{
		parent::__construct(__CLASS__);
	}

	/**
	 * reset warehouse id
	 *
	 * @param int $warehouseId
	 */
	public function setWarehouseId($warehouseId)
	{
		$this->warehouseId = $warehouseId;
	}

	public function execute()
	{
		/*
		 * warehouse id is a mandatory input
		 */
		if(!isset($this->warehouseId) || !$this->warehouseId)
		{
			$this->getLogger()->err(__FUNCTION__.' you have to set a warehouseId first');

			return;
		}

		/*
		 * current timestamop with an tiny buffer of 45 seconds
		 *
		 * used for next call
		 */
		$this->currentTimestamp = time()-45;

		/*
		 * when was the last call for this warehouse id?
		 */
		$this->lastUpdateTimestamp = $this->getLastUpdateTimestamp();

		/*
		 * start with page 1
		 */
		$this->currentPage = 1;

		/*
		 * perform first api call
		 */
		$this->callOnePage();
	}

	/**
	 * perform api call
	 *
	 */
	private function callOnePage()
	{
		try
		{
			/*
			 * define request object
			 */
			$oPlentySoapRequest_GetCurrentStocks = new PlentySoapRequest_GetCurrentStocks();
			$oPlentySoapRequest_GetCurrentStocks->Page = $this->currentPage;
			$oPlentySoapRequest_GetCurrentStocks->WarehouseID = $this->warehouseId;

			/*
			 * Use for retrieval of all pages always have the same timestamp.
			 * Use for the next call in a few minutes the current timestamp of now.
			 * Therefore it is important to store the time of the last successful retrieval.
			 */
			$oPlentySoapRequest_GetCurrentStocks->LastUpdate = $this->lastUpdateTimestamp;

			/*
			 * do soap call
			 */
			$response	=	$this->getPlentySoap()->GetCurrentStocks($oPlentySoapRequest_GetCurrentStocks);

			/*
			 * check soap response
			 */
			if( $response->Success == true )
			{
				$this->getLogger()->debug(__FUNCTION__.' request succeed - warehouse: '.$this->warehouseId.' page: '.$this->currentPage);

				/*
				 * store timestamp for the next retrieval
				 */
				if($this->currentPage==1)
				{
					$this->saveCurrentTimestamp2DB4TheNextRetrieval();
				}

				/*
				 * parse and save the data
				 */
				$this->parseResponse($response);
			}
			else
			{
				if(is_array($response->ResponseMessages->item))
				{
					foreach($response->ResponseMessages->item as $ResponseMessage)
					{
						foreach ($ResponseMessage->ErrorMessages->item as $ErrorMessage)
						{
							$this->getLogger()->crit(__FUNCTION__.' request Error - warehouse: '.$this->warehouseId.' code: '.$ErrorMessage->Value . ': '.$ErrorMessage->Key);
						}
					}
				}
			}
		}
		catch(Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}

	/**
	 * Parse the response
	 *
	 * @param PlentySoapResponse_GetCurrentStocks $response
	 */
	private function parseResponse($response)
	{
		if(is_object($response->CurrentStocks->item))
		{
			$this->saveInDatabase($response->CurrentStocks->item);
		}
		elseif (is_array($response->CurrentStocks->item))
		{
			foreach ($response->CurrentStocks->item as $currentStock)
			{
				$this->saveInDatabase($currentStock);
			}
		}

		if($response->Pages>$this->currentPage)
		{
			++$this->currentPage;

			/**
			 * perform call for next page
			 */
			$this->callOnePage();
		}
	}

	/**
	 * save data in plenty_stock
	 *
	 * @param PlentySoapObject_GetCurrentStocks
	 */
	private function saveInDatabase($currentStocks)
	{
		/*
		 * stockkeeping unit (SKU)
		 *
		 * plentymarkets SKU = [ITEM_ID]-[PRICE_ID]-[ATTRIBUTE_VALUE_SET_ID]
		 */
		$sku = explode('-', $currentStocks->SKU);

		$query = 'REPLACE INTO plenty_stock '.DBUtils::buildInsert(	array(	'item_id' => $sku[0],
																			'price_id' =>  $sku[1],
																			'attribute_value_set_id' => $sku[2],
																			'ean' => $currentStocks->EAN,
																			'warehouse_id' => $currentStocks->WarehouseID,
																			'warehouse_type' => $currentStocks->WarehouseType,
																			'storage_location_id' => $currentStocks->StorageLocationID,
																			'storage_location_name' => $currentStocks->StorageLocationName,
																			'storage_location_stock' => $currentStocks->StorageLocationStock,
																			'physical_stock' => $currentStocks->PhysicalStock,
																			'netto_stock' => $currentStocks->NetStock,
																			'average_price' => $currentStocks->AveragePrice,
																		));

		$this->getLogger()->debug(__FUNCTION__.' get stock data for item-sku: '.$currentStocks->SKU.' netto_stock: '.$currentStocks->NetStock);

		DBQuery::getInstance()->replace($query);
	}

	/**
	 * Select last_update_timestamp from plenty_stock_last_update
	 *
	 * @return int
	 */
	private function getLastUpdateTimestamp()
	{
		$query = 'SELECT last_update_timestamp FROM plenty_stock_last_update WHERE warehouse_id='.$this->warehouseId.' LIMIT 1';

		$result = DBQuery::getInstance()->selectAssoc($query);

		$this->getLogger()->debug(__FUNCTION__.' '.$query);

		if(isset($result['last_update_timestamp']) && $result['last_update_timestamp']>0)
		{
			return $result['last_update_timestamp'];
		}

		/*
		 * This seems to be the first stock call...
		 *
		 * We therefore use a very large time interval, once to get all important data.
		 *
		 * We use: current date minus 90 days.
		 *
		 * IMPORTANT
		 * Use such a large time interval really only for the very first api call.
		 * Because from now on, you only interested in the changes since the last api call.
		 */
		return time()-(60*60*24*90);
	}

	/**
	 * Save $this->currentTimestamp in plenty_stock_last_update
	 *
	 */
	private function saveCurrentTimestamp2DB4TheNextRetrieval()
	{
		if($this->warehouseId>0)
		{
			$query = 'REPLACE INTO plenty_stock_last_update '
					.	DBUtils::buildInsert(array(
							'warehouse_id' => $this->warehouseId,
							'last_update_timestamp' => $this->currentTimestamp
					));

					DBQuery::getInstance()->replace($query);

					$this->getLogger()->debug(__FUNCTION__.' '.$query);
		}
	}
}

?>