<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';

class SoapCall_GetCurrentStocks extends PlentySoapCall 
{
	public function __construct()
	{
		parent::__construct(__CLASS__);
	}
	
	public function execute() 
	{
		try
		{
			$this->getLogger()->debug(__FUNCTION__.' start');
			
			/*
			 * Get all warehouses
			 */
			$warehouseResult = DBQuery::getInstance()->select('SELECT warehouse_id FROM plenty_warehouse');
			
			/*
			 * the soap call for each warehouse
			 */
			while ( ($warehouseRow = $warehouseResult->fetchAssoc()) && $warehouseRow )
			{
				$oPlentySoapRequest_GetCurrentStocks = new PlentySoapRequest_GetCurrentStocks();
				$oPlentySoapRequest_GetCurrentStocks->WarehouseID = $warehouseRow['warehouse_id'];
				/*
				 * Timestamp now - 15 minutes
				 */
				$oPlentySoapRequest_GetCurrentStocks->LastUpdate = time() - 900;
				
				/*
				 * do soap call
				 */
				$response	=	$this->getPlentySoap()->GetCurrentStocks($oPlentySoapRequest_GetCurrentStocks);
					
				/*
				 * check soap response
				*/
				if( $response->Success == true )
				{
					$this->getLogger()->debug(__FUNCTION__.' Request Success - : GetCurrentStocks - Warehouse : '.$warehouseRow['warehouse_id']);
				
					/*
					 * parse and save the data
					*/
					$this->parseResponse($response);
				}
				else
				{
					$this->getLogger()->debug(__FUNCTION__.' Request Error - Warehouse : '.$warehouseRow['warehouse_id']);
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
		$this->getLogger()->debug(__FUNCTION__.' '.$query);
		
		DBQuery::getInstance()->replace($query);
	}
}

?>