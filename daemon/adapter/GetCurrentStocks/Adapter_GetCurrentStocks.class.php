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
	 * 
	 * @var int
	 */
	private $currentTimestamp = 0;
	
	public function __construct()
	{
		parent::__construct(__CLASS__);
		
		/*
		 * current timestamop with an tiny buffer of 45 seconds
		 *
		 * used for next call
		 */
		$this->currentTimestamp = time()-45;
	}
	
	/**
	 * 
	 * @param int $warehouseId
	 */
	public function setWarehouseId($warehouseId)
	{
		$this->warehouseId = $warehouseId;
	}
	
	public function execute() 
	{
		if(!isset($this->warehouseId) || !$this->warehouseId)
		{
			$this->getLogger()->err(__FUNCTION__.' you have to set a warehouseId first');
			
			return;
		}
		
		$this->callOnePage(1);
	}
	
	/**
	 * 
	 * @param number $page
	 */
	private function callOnePage($page=1)
	{
		/*
		 * when was the last call for this warehouse id?
		 */
		$lastUpdateTimestamp = $this->getLastUpdateTimestamp($this->warehouseId);
		
		try
		{
			$oPlentySoapRequest_GetCurrentStocks = new PlentySoapRequest_GetCurrentStocks();
			$oPlentySoapRequest_GetCurrentStocks->Page = $page;
			$oPlentySoapRequest_GetCurrentStocks->WarehouseID = $this->warehouseId;
			
			/*
			 * Timestamp now - 15 minutes
			 */
			$oPlentySoapRequest_GetCurrentStocks->LastUpdate = $lastUpdateTimestamp;
		
			/*
			 * do soap call
			 */
			$response	=	$this->getPlentySoap()->GetCurrentStocks($oPlentySoapRequest_GetCurrentStocks);
		
			/*
			 * check soap response
			 */
			if( $response->Success == true )
			{
				/*
				 * save next last timestamp - important!
				 */
				$this->setLastUpdateTimestamp($this->warehouseId, $this->currentTimestamp);
		
		
				$this->getLogger()->debug(__FUNCTION__.' request succeed - warehouse : '.$this->warehouseId);
					
				/*
				 * parse and save the data
				 */
				$this->parseResponse($response);
			}
			else
			{
				if(is_object($response->ErrorMessages) && is_array($response->ErrorMessages->item))
				{
					foreach($response->ErrorMessages->item as $errorItem)
					{
						$this->getLogger()->crit(__FUNCTION__.' request Error - warehouse: '.$this->warehouseId.' code: '.$errorItem->Code.' '.$errorItem->Message);
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
	 * 
	 * @param int $warehouseId
	 * @return int
	 */
	private function getLastUpdateTimestamp($warehouseId)
	{
		$query = 'SELECT last_update_timestamp FROM plenty_stock_last_update WHERE warehouse_id='.$warehouseId.' LIMIT 1';
		
		$result = DBQuery::getInstance()->selectAssoc($query);
		
		$this->getLogger()->debug(__FUNCTION__.' '.$query);
		
		if(isset($result['last_update_timestamp']) && $result['last_update_timestamp']>0)
		{
			return $result['last_update_timestamp'];
		}
		
		/*
		 * for the first run use timestamp: now - 48h 
		 */
		return time()-(60*60*48);
	}
	
	/**
	 * 
	 * @param int $warehouseId
	 * @param int $timestamp
	 */
	private function setLastUpdateTimestamp($warehouseId, $timestamp)
	{
		if($warehouseId>0)
		{
			$query = 'REPLACE INTO plenty_stock_last_update '
												.	DBUtils::buildInsert(array(
																				'warehouse_id' => $warehouseId,
																				'last_update_timestamp' => $timestamp
																			));
			
			DBQuery::getInstance()->replace($query);
			
			$this->getLogger()->debug(__FUNCTION__.' '.$query);
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
	}
	
	/**
	 * Save the data in the database
	 * 
	 * @param PlentySoapObject_GetCurrentStocks 
	 */
	private function saveInDatabase($currentStocks)
	{
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
		
		$this->getLogger()->debug(__FUNCTION__.' get stock data for item/sku: '.$currentStocks->SKU.' netto_stock: '.$currentStocks->NetStock);
		
		DBQuery::getInstance()->replace($query);
	}
}

?>