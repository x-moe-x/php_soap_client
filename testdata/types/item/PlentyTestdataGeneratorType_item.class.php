<?php
require_once ROOT.'testdata/types/PlentyTestdataGeneratorTypeBase.class.php';
require_once 'collector/PlentyItemDataCollectorWilliamShakespeare.class.php';
require_once 'soap/PlentyItemDataPushCategory.class.php';
require_once 'soap/PlentyItemDataPushWarehouse.class.php';
require_once 'soap/PlentyItemDataPushProducer.class.php';
require_once 'soap/PlentyItemDataPushItems.class.php';

/**
 * This tool adds new items in plentymarkets. 
 * Including a meaningful description, prices, producer and category.
 * 
 * It is also a complex example to create items.
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class PlentyTestdataGeneratorType_item extends PlentyTestdataGeneratorTypeBase
{	
	/**
	 * 
	 * @var array
	 */
	private $itemList = array();
	
	public function __construct()
	{
		/*
		 * select some data, we will use them for new items
		 */
		PlentyItemDataPushCategory::getInstance()->execute();
		PlentyItemDataPushWarehouse::getInstance()->execute();
		PlentyItemDataPushProducer::getInstance()->execute();
	}
	
	public function execute()
	{
		/*
		 * collect some texts for new items
		 */
		$plentyItemDataCollectorWilliamShakespeare = new PlentyItemDataCollectorWilliamShakespeare();
		$plentyItemDataCollectorWilliamShakespeare->setQuantity($this->getQuantity());
		$plentyItemDataCollectorWilliamShakespeare->setLang($this->getLang());
		$plentyItemDataCollectorWilliamShakespeare->execute();

		$this->itemList = $plentyItemDataCollectorWilliamShakespeare->getItemList();
		
		/*
		 * add prices, availability options ...
		 */
		$this->addMoreData();

		/*
		 * push items to api
		 */
		PlentyItemDataPushItems::getInstance()->pushItems($this->itemList);
		

	}

	/**
	 * 
	 */
	private function addMoreData()
	{
		$this->getLogger()->debug(__FUNCTION__);
		
		if(is_array($this->itemList))
		{
			/*
			 * add some more data
			 */
			for($i=0; $i<count($this->itemList); $i++)
			{
				$item = $this->itemList[$i];
				
				if($item instanceof PlentySoapObject_AddItemsBaseItemBase)
				{
					$id = $this->generateExternalItemID($item->Texts->Name.' '.$item->Texts->LongDescription, 8);
					$item->ExternalItemID = $id;
					$item->ItemNo = $id;
					$item->Published = $this->generateTimestampInThePast();
					$item->Marking1ID = 26; // sun icon
					$item->Marking2ID = rand(1,11); // colored peopel icon
						
					/*
					 * availability options
					*/
					$plentySoapObject_ItemAvailability = new PlentySoapObject_ItemAvailability();
					$plentySoapObject_ItemAvailability->Webshop = 1;
					$plentySoapObject_ItemAvailability->WebAPI = 0;
					$plentySoapObject_ItemAvailability->AvailabilityID = 5; // will be set to 1 by ChangeAvailablePositiveStock option
						
					$item->Availability = $plentySoapObject_ItemAvailability;
						
						
					/*
					 * stock options
					*/
					$plentySoapObject_ItemStock = new PlentySoapObject_ItemStock();
					$plentySoapObject_ItemStock->ChangeAvailablePositiveStock = true;
					$plentySoapObject_ItemStock->ChangeNotAvailableNoStock = true;
					$plentySoapObject_ItemStock->MainWarehouseID = PlentyItemDataPushWarehouse::getInstance()->getFirstWarehouseId();
						
					$item->Stock = $plentySoapObject_ItemStock;
						
						
					/*
					 * price options
					*/
					$price = rand(25, 99) + (rand(1,99)/100);
						
					$plentySoapObject_ItemPriceSet = new PlentySoapObject_ItemPriceSet();
					$plentySoapObject_ItemPriceSet->Price = $price;
						
					for($ii=1; $ii<=12; $ii++)
					{
					$plentySoapObject_ItemPriceSet->{'Price'.$ii} = number_format($price-($price/100*($ii/2)) , 2, '.', '');
					}
								
					$plentySoapObject_ItemPriceSet->RRP = ceil($price*1.15);
					$plentySoapObject_ItemPriceSet->PurchasePriceNet = number_format($price-($price/100*35) , 2, '.', '');
					$plentySoapObject_ItemPriceSet->WeightInGramm = rand(100,1000);
					
					$plentySoapObject_ItemPriceSet->HeightInMM = rand(5,50);
					$plentySoapObject_ItemPriceSet->WidthInMM = rand(50,100);
					$plentySoapObject_ItemPriceSet->LengthInMM = rand(100,600);
					
					$plentySoapObject_ItemPriceSet->Lot = 1;
					$plentySoapObject_ItemPriceSet->Package = 1;
						
					$item->PriceSet = $plentySoapObject_ItemPriceSet;
					
					$this->itemList[$i] = $item;
				}
			}
		}
		else
		{
			$this->getLogger()->crit(__FUNCTION__.' itemList is empty or not an array.');
		}
			
	}
	
	/**
	 * simple generator for unique ids
	 * 
	 * @param string $string
	 * @param int $length
	 * @return string
	 */
	private function generateExternalItemID($string, $length)
	{
		$s = md5($string . time());
		
		return substr($s, 0, $length);
	}
	
	/**
	 * 
	 * @return number
	 */
	private function generateTimestampInThePast()
	{
		return mktime(0, 0, 0, date('n'), date('j')-rand(0,500), date('Y'));
	}
	
	/**
	 *
	 * @return Logger
	 */
	private function getLogger()
	{
		return Logger::instance(__CLASS__);
	}
}

?>