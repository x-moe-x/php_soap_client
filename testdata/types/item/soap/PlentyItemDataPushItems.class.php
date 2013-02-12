<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';
require_once ROOT.'testdata/types/item/collector/PlentyItemDataCollectorImages.class.php';
require_once ROOT.'testdata/types/item/soap/PlentyItemDataPushImage.class.php';
require_once ROOT.'testdata/types/item/soap/PlentyItemDataPushStock.class.php';

/**
 * This class needs an array of PlentySoapObject_AddItemsBaseItemBase objects.
 * Commit the list to pushItems($itemList). This method calls ->execute() on its own. 
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
	 * @var PlentyItemDataCollectorImages
	 */
	private $plentyItemDataCollectorImages = null;
	
	/**
	 * used for push stock
	 * 
	 * @var int
	 */
	private $warehouseId = 0;
	
	/**
	 *
	 * @var PlentyItemDataPushItems
	 */
	private static $instance = null;
	
	public function __construct()
	{
		parent::__construct(__CLASS__);
		
		$this->plentyItemDataCollectorImages = new PlentyItemDataCollectorImages();
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
	 * @param unknown $itemList array of PlentySoapObject_AddItemsBaseItemBase objects 
	 */
	public function pushItems($itemList)
	{
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
					if($c >= 50)
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
				if(is_array($response->SuccessMessages->item))
				{
					foreach($response->SuccessMessages->item as $item)
					{
						if($item->Code=='SIB0001' && strpos($item->Message, ';')!==false)
						{
							/*
							 * 0 = itemId
							 * 1 = priceId
							 */
							$idList = explode(';', $item->Message);
							
							$this->getLogger()->debug(__FUNCTION__.' new item id: '.$idList[0]);
								
							/*
							 * add an image for this new item
							 */
							$this->pushImage((int)$idList[0]);
							
							/*
							 * push items to stock data objects
							 */
							$this->push2StockStack((int)$idList[0], (int)$idList[1]);
						}
					}
					
					/*
					 * push stock to api
					 */
					PlentyItemDataPushStock::getInstance()->pushData2API();
					
				}

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
	 * add an image for this new item
	 *
	 * @param int $itemId
	 */
	private function pushImage($itemId)
	{
		$imageUrl = $this->plentyItemDataCollectorImages->getOneImageUrl();
		 
		if(strlen($imageUrl) && $itemId>0)
		{
			PlentyItemDataPushImage::getInstance()->setImageUrl($imageUrl)->setItemId($itemId)->pushTestImage2API(2);
		}
		else
		{
			$this->getLogger()->debug(__FUNCTION__.' I miss some data - itemId: '.$itemId.' imageUrl: '.$imageUrl);
		}
	}
	
	/**
	 * push items to stock data objects
	 * 
	 * @param int $itemId
	 * @param int $priceId
	 */
	private function push2StockStack($itemId, $priceId)
	{
		if($itemId>0 && $priceId>0 && $this->warehouseId>0)
		{
			PlentyItemDataPushStock::getInstance()->setItemId($itemId)
													->setPriceId($priceId)
													->setAttributeValueSetId(0)
													->setWarehouseId($this->warehouseId)
													->pushItemData2Stack(2);
		}
		else
		{
			$this->getLogger()->crit(__FUNCTION__.' I miss some data - itemId: '.$itemId.' priceId: '.$priceId.' warehouseId: '.$this->warehouseId);
		}
	}
	
	/**
	 * @param number $warehouseId
	 * 
	 * @return PlentyItemDataPushItems
	 */
	public function setWarehouseId($warehouseId) 
	{
		$this->warehouseId = $warehouseId;
		
		return $this;
	}

	
}
?>