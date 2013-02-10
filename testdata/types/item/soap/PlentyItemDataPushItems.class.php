<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';

/**
 *
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class PlentyItemDataPushItems extends PlentySoapCall
{
	/**
	 * 
	 * @var PlentySoapRequest_AddItemsBase
	 */
	private $plentySoapRequest_AddItemsBase = null;

	/**
	 *
	 * @var PlentyItemDataPushItems
	 */
	private static $instance = null;
	
	public function __construct()
	{
		parent::__construct(__CLASS__);
	}
	
	/**
	 * singleton pattern
	 *
	 * @return PlentyItemDataPushItems
	 */
	public static function getInstance()
	{
		if( !isset(self::$instance) || !(self::$instance instanceof PlentyItemDataPushItems))
		{
			self::$instance = new PlentyItemDataPushItems();
		}
	
		return self::$instance;
	}
	
	/**
	 * push items to api
	 * 
	 * @param unknown $itemList
	 */
	public function pushItems($itemList)
	{
		$this->getLogger()->debug(__FUNCTION__);
		
		$this->plentySoapRequest_AddItemsBase = new PlentySoapRequest_AddItemsBase();
		
		if(is_array($itemList))
		{
			/*
			 * add some more data
			 */
			foreach($itemList as $item)
			{
				if($item instanceof PlentySoapObject_AddItemsBaseItemBase)
				{
					$c = count($this->plentySoapRequest_AddItemsBase->BaseItems);

					/*
					 * check if max. number of items of this call is reached
					 */
					if($c >= 2)
					{
						/*
						 * push to api
						 */
						$this->execute();
						
						/*
						 * empty list
						 */
						$this->plentySoapRequest_AddItemsBase->BaseItems = array();
					}

					$this->plentySoapRequest_AddItemsBase->BaseItems[] = $item;
				}
				else
				{
					$this->getLogger()->crit(__FUNCTION__.' Wrong data type found.');
				}
			}

			/*
			 * push to api
			 */
			$c = count($this->plentySoapRequest_AddItemsBase->BaseItems);

			
			if($c > 0)
			{
				$this->execute();
			}
		}
		else
		{
			$this->getLogger()->crit(__FUNCTION__.' itemList is empty or not an array.');
		}
	}
	
	public function execute()
	{
		$this->getLogger()->debug(__FUNCTION__);
		
		try
		{
			/*
			 * do soap call
			 */
			$response	=	$this->getPlentySoap()->AddItemsBase($this->plentySoapRequest_AddItemsBase);
	
			/*
			 * check soap response
			 */
			if( $response->Success == true )
			{
				$this->getLogger()->debug(__FUNCTION__.' request succeed');

				/*
				 * parse and save the data
				 */
				$this->parseResponse($response);
			}
			else
			{
				$this->getLogger()->debug(__FUNCTION__.' request error');
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
	 * @param PlentySoapResponse_GetWarehouseList $response
	 */
	private function parseResponse($response)
	{
		//print_r($response);
		
		return;
		
		if(is_array($response->WarehouseList->item))
		{
			/*
			 * If more than one warehouse
			 */
			foreach ($response->WarehouseList->item as $warehouse)
			{
				if($warehouse->item->Type==0)
				{
					$this->warehouseList[$warehouse->Name] = $warehouse->WarehouseID;
				}
			}
		}
		
		/*
		 * only one warehouse
		 */
		elseif (is_object($response->WarehouseList->item))
		{
			if($response->WarehouseList->item->Type==0)
			{
				$this->warehouseList[$response->WarehouseList->item->Name] = $response->WarehouseList->item->WarehouseID;
			}
		}
	}
	
}
?>