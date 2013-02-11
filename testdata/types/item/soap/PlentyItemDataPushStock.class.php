<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';

/**
 * This class booked for a passed item randomly generated inventory.
 * 
 * Usage:
 * Call per item this methods:
 * 				PlentyItemDataPushStock::getInstance()->setItemId($itemId)
 * 															->setPriceId($priceId)
 * 															->setAttributeValueSetId(0) // set 0 if this item did not have attributes
 * 															->setWarehouseId($warehouseId)
 * 															->pushItemData2Stack(2);  // set number of bookings per item - update this method for productive usage!
 * At the end:
 * 				PlentyItemDataPushStock::getInstance()->pushData2API();
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class PlentyItemDataPushStock extends PlentySoapCall
{
	/**
	 *
	 * @var int
	 */
	private $itemId = 0;
	
	/**
	 *
	 * @var int
	 */
	private $priceId = 0;

	/**
	 *
	 * @var int
	 */
	private $attributeValueSetId = 0;

	/**
	 *
	 * @var int
	 */
	private $warehouseId = 0;
	

	/**
	 * currently not in use - we generate testdata by rand
	 * 
	 * @var int
	 */
	private $physicalStock = 0;

	/**
	 * 
	 * @var PlentySoapRequest_SetCurrentStocks
	 */
	private $plentySoapRequest_SetCurrentStocks = null;
	
	/**
	 * 
	 * @var array
	 */
	private $stockList = array();
	
	/**
	 *
	 * @var PlentyItemDataPushStock
	 */
	private static $instance = null;
	
	public function __construct()
	{
		parent::__construct(__CLASS__);
	}
	
	/**
	 * singleton pattern
	 *
	 * @return PlentyItemDataPushStock
	 */
	public static function getInstance()
	{
		if( !isset(self::$instance) || !(self::$instance instanceof PlentyItemDataPushStock))
		{
			self::$instance = new PlentyItemDataPushStock();
		}
	
		return self::$instance;
	}
	
	/**
	 * call this after filling data via setItemId, setPriceId... for each item once
	 * 
	 * @param number $bookingsEachItem
	 */
	public function pushItemData2Stack($bookingsEachItem=1)
	{
		if($bookingsEachItem<=0 || $bookingsEachItem>20)
		{
			$bookingsEachItem = 1;
		}

		if(isset($this->itemId) && isset($this->priceId) && isset($this->warehouseId))
		{
			for($i=0; $i<$bookingsEachItem; $i++)
			{
				$plentySoapObject_SetCurrentStocks = new PlentySoapObject_SetCurrentStocks();
				$plentySoapObject_SetCurrentStocks->PhysicalStock = rand(4,200);
				// SKU = [ITEM_ID]-[PRICE_ID]-[ATTRIBUTE_VALUE_SET_ID]
				$plentySoapObject_SetCurrentStocks->SKU = $this->itemId.'-'.$this->priceId.'-'.(int)$this->attributeValueSetId;
				$plentySoapObject_SetCurrentStocks->WarehouseID = $this->warehouseId;
				$plentySoapObject_SetCurrentStocks->StorageLocation = -1;
					
				$this->stockList[] = $plentySoapObject_SetCurrentStocks;
			}
		}
		else
		{
			$this->getLogger()->crit(__FUNCTION__.' I miss some data itemId: '.$this->itemId.' priceId: '.$this->priceId.' warehouseId: '.$this->warehouseId);
		}
	}
	
	/**
	 * This function transfers the collected data in the correct item number to ->execute()
	 * 
	 */
	public function pushData2API()
	{
		if(is_array($this->stockList))
		{
			$i = 0;
			$this->plentySoapRequest_SetCurrentStocks = new PlentySoapRequest_SetCurrentStocks();
				
			foreach($this->stockList as $plentySoapObject_SetCurrentStocks)
			{
				++$i;
		
				if($i>=100)
				{
					$this->execute();
						
					$this->plentySoapRequest_SetCurrentStocks->CurrentStocks = array();
				}
		
				$this->plentySoapRequest_SetCurrentStocks->CurrentStocks[] = $plentySoapObject_SetCurrentStocks;
			}
		}
		
		if(is_array($this->plentySoapRequest_SetCurrentStocks->CurrentStocks) && count($this->plentySoapRequest_SetCurrentStocks->CurrentStocks)>0)
		{
			$this->execute();
		}
	}
	
	public function execute()
	{
		try
		{
			/*
			 * do soap call
			 */
			$response	=	$this->getPlentySoap()->SetCurrentStocks($this->plentySoapRequest_SetCurrentStocks);
				
			/*
			 * check soap response
			*/
			if( $response->Success == true )
			{
				$this->getLogger()->debug(__FUNCTION__.' request succeed');
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
	 * @param number $itemId
	 * 
	 * @return PlentyItemDataPushStock
	 */
	public function setItemId($itemId) 
	{
		$this->itemId = $itemId;
		
		return $this;
	}

	/**
	 * @param number $priceId
	 * 
	 * @return PlentyItemDataPushStock
	 */
	public function setPriceId($priceId) 
	{
		$this->priceId = $priceId;
		
		return $this;
	}

	/**
	 * @param number $attributeValueSetId
	 * 
	 * @return PlentyItemDataPushStock
	 */
	public function setAttributeValueSetId($attributeValueSetId) 
	{
		$this->attributeValueSetId = $attributeValueSetId;
		
		return $this;
	}

	/**
	 * @param number $warehouseId
	 * 
	 * @return PlentyItemDataPushStock
	 */
	public function setWarehouseId($warehouseId) 
	{
		$this->warehouseId = $warehouseId;
		
		return $this;
	}

	/**
	 * currently not in use - we generate testdata by rand
	 * 
	 * @param number $physicalStock
	 *
	 * @return PlentyItemDataPushStock
	 */
	public function setPhysicalStock($physicalStock)
	{
		$this->physicalStock = $physicalStock;
	
		return $this;
	}
	
}
?>