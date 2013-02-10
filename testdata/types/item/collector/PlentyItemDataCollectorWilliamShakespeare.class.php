<?php

require_once ROOT.'lib/log/Logger.class.php';

/**
 * 
 * @author phileon
 *
 */
class PlentyItemDataCollectorWilliamShakespeare
{
	/**
	 * How many data should I collect?
	 *
	 * @var int
	 */
	private $quantity = 300;
	
	/**
	 *
	 * @var string
	 */
	private $lang = 'de';
	
	/**
	 * 
	 * @var string
	 */
	private $procuder = 'William Shakespeare';

	/**
	 * public domain text
	 * 
	 * @var array
	 */
	private $urlList = array(
					'Comedy' => array(
									'http://shakespeare.mit.edu/allswell/index.html',
									'http://shakespeare.mit.edu/asyoulikeit/index.html',
									'http://shakespeare.mit.edu/comedy_errors/index.html',
									'http://shakespeare.mit.edu/cymbeline/index.html',
									'http://shakespeare.mit.edu/lll/index.html',
									'http://shakespeare.mit.edu/measure/index.html',
									'http://shakespeare.mit.edu/merry_wives/index.html',
									'http://shakespeare.mit.edu/merchant/index.html',
									'http://shakespeare.mit.edu/midsummer/index.html',
									'http://shakespeare.mit.edu/much_ado/index.html',
									'http://shakespeare.mit.edu/pericles/index.html',
									'http://shakespeare.mit.edu/taming_shrew/index.html',
									'http://shakespeare.mit.edu/tempest/index.html',
									'http://shakespeare.mit.edu/troilus_cressida/index.html',
									'http://shakespeare.mit.edu/twelfth_night/index.html',
									'http://shakespeare.mit.edu/two_gentlemen/index.html',
									'http://shakespeare.mit.edu/winters_tale/index.html',
					),
					'History' => array(
									'http://shakespeare.mit.edu/1henryiv/index.html',
									'http://shakespeare.mit.edu/2henryiv/index.html',
									'http://shakespeare.mit.edu/henryv/index.html',
									'http://shakespeare.mit.edu/1henryvi/index.html',
									'http://shakespeare.mit.edu/2henryvi/index.html',
									'http://shakespeare.mit.edu/3henryvi/index.html',
									'http://shakespeare.mit.edu/henryviii/index.html',
									'http://shakespeare.mit.edu/john/index.html',
									'http://shakespeare.mit.edu/richardii/index.html',
									'http://shakespeare.mit.edu/richardiii/index.html',
					),
					'Tragedy' => array(
									'http://shakespeare.mit.edu/cleopatra/index.html',
									'http://shakespeare.mit.edu/coriolanus/index.html',
									'http://shakespeare.mit.edu/hamlet/index.html',
									'http://shakespeare.mit.edu/julius_caesar/index.html',
									'http://shakespeare.mit.edu/lear/index.html',
									'http://shakespeare.mit.edu/macbeth/index.html',
									'http://shakespeare.mit.edu/othello/index.html',
									'http://shakespeare.mit.edu/romeo_juliet/index.html',
									'http://shakespeare.mit.edu/timon/index.html',
									'http://shakespeare.mit.edu/titus/index.html',
					),
			);
	
	/**
	 * 
	 * @var array
	 */
	private $itemList = array();
	
	public function execute()
	{
		foreach(array_keys($this->urlList) as $category)
		{
			$this->getLogger()->debug(__FUNCTION__.' current category: '.$category);
			
			/*
			 * check category
			 */
			$categoryId = PlentyItemDataPushCategory::getInstance()->checkCategoryLevel1($category);
			if(!$categoryId)
			{
				/*
				 * save category
				 */
				PlentyItemDataPushCategory::getInstance()->saveNewCategoryLevel1($category);
				
				$categoryId = PlentyItemDataPushCategory::getInstance()->checkCategoryLevel1($category);
			}
			
			if($categoryId>0)
			{
				foreach($this->urlList[$category] as $url)
				{
					$itemUrls = $this->getItemUrlList($url);
					if(isset($itemUrls) && is_array($itemUrls))
					{
						foreach($itemUrls as $itemUrl)
						{
							if( count($this->itemList)>0 && $this->quantity<=count($this->itemList) )
							{
								$this->getLogger()->debug(__FUNCTION__.' quantity '.$this->quantity.' reached');
								break 3;
							}
							else
							{
								$this->generateItem($categoryId, $itemUrl);
								
								sleep(1);
							}
						}
					}
				}				
			}
			else
			{
				$this->getLogger()->crit(__FUNCTION__.' I can not add a new category. I will shut down.');
				exit;
			}
		}
	}
	
	/**
	 * collect scene urls which we will use as item data
	 * 
	 * @param string $url
	 * @return array
	 */
	private function getItemUrlList($url)
	{
		$list = array();

		$e = explode('/',$url);
		$a = array_pop($e);
		$baseUrl = implode('/', $e);
		
		$content = file_get_contents($url);
		
		preg_match_all('/(Act.*?):.*?href="(.*?)"/', $content, $result);
		
		if(isset($result[2]) && is_array($result[2]))
		{
			foreach($result[2] as $itemUrl)
			{
				$list[] = $baseUrl.'/'.$itemUrl;
			}
		}
		
		return $list;
	}
	
	/**
	 * 
	 * @param string $categoryId
	 * @param array $itemUrl
	 */
	private function generateItem($categoryId, $itemUrl)
	{
		$content = file_get_contents($itemUrl);
		
		$itemName = $itemName2 = $itemName3 = '';
		$shortDescription = $longDescription = '';
		
		/*
		 * item name
		 */
		preg_match('/class="play" align="center">(.*?)</ims', $content, $result);
		if(isset($result[1]))
		{
			$itemName = trim($result[1]);
		}
		
		preg_match('/<title>(.*?)<\//ims', $content, $result);
		if(isset($result[1]))
		{
			$itemName2 = trim($result[1]);
		}
		
		preg_match('/ \| (Act .*?)</ims', $content, $result);
		if(isset($result[1]))
		{
			$itemName3 = trim($result[1]);
		}
		
		/*
		 * short description
		 */
		preg_match('/<p><blockquote>\n<i>(.*?)<\//ims', $content, $result);
		if(isset($result[1]))
		{
			$shortDescription = trim(strip_tags($result[1]));
		}
		
		/*
		 * description
		 */
		preg_match('/\/h3>(.*?)<table/ims', $content, $result);
		if(isset($result[1]))
		{
			$longDescription = trim($result[1]);
			$longDescription = strip_tags($longDescription,'<i>,<p>,<br>,<b>,<blockquote>');
			$longDescription .= chr(10).chr(10).'<p>reference: <a href="'.$itemUrl.'">'.$itemUrl.'</a></p>';
		}
		
		if(strlen($itemName) 
				&& strlen($itemName2)
				&& strlen($longDescription)
				)
		{
			/*
			 * push result to plenty soap object
			 */
			$plentySoapObject_ItemTexts = new PlentySoapObject_ItemTexts();
			$plentySoapObject_ItemTexts->Lang = $this->lang;
			$plentySoapObject_ItemTexts->Name = $itemName . (strlen($itemName3) ? ' / '.$itemName3 : '');
			$plentySoapObject_ItemTexts->Name2 = $itemName2;
			$plentySoapObject_ItemTexts->Name3 = $itemName3;
			$plentySoapObject_ItemTexts->MetaDescription = $this->procuder . ' ' . $plentySoapObject_ItemTexts->Name;
			$plentySoapObject_ItemTexts->ShortDescription = $shortDescription;
			$plentySoapObject_ItemTexts->LongDescription = $longDescription;
			
			/*
			 * push category
			 */
			$plentySoapObject_ItemCategory = new PlentySoapObject_ItemCategory();
			$plentySoapObject_ItemCategory->ItemCategoryPath = $categoryId.';;;;;';
			
			$plentySoapObject_AddItemsBaseItemBase = new PlentySoapObject_AddItemsBaseItemBase();
			$plentySoapObject_AddItemsBaseItemBase->Texts = $plentySoapObject_ItemTexts;
			/*
			 * category is a list so add as an array
			 */
			$plentySoapObject_AddItemsBaseItemBase->Categories[] = $plentySoapObject_ItemCategory;
			
			
			/*
			 * producer
			 */
			$producerId = PlentyItemDataPushProducer::getInstance()->checkProducer($this->procuder);
			if(!$producerId)
			{
				PlentyItemDataPushProducer::getInstance()->saveNewProducer($this->procuder);
				
				$producerId = PlentyItemDataPushProducer::getInstance()->checkProducer($this->procuder);
			}
			
			if($producerId>0)
			{
				$plentySoapObject_AddItemsBaseItemBase->ProducerID = $producerId;
			}
			
			/*
			 * push to stack
			 */
			$this->itemList[] = $plentySoapObject_AddItemsBaseItemBase;
			
			$this->getLogger()->debug(__FUNCTION__.' add new item text / item name: '.$plentySoapObject_ItemTexts->Name );
		}
	}
	
	/**
	 * 
	 * @return array
	 */
	public function getItemList()
	{
		return $this->itemList;
	}
	
	/**
	 * @param string $lang
	 */
	public function setLang($lang)
	{
		$this->lang = $lang;
	}
	
	/**
	 * @param number $quantity
	 */
	public function setQuantity($quantity)
	{
		$this->quantity = (int)$quantity;
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