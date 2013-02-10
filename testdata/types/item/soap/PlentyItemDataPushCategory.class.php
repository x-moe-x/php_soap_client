<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';

/**
 *
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class PlentyItemDataPushCategory extends PlentySoapCall
{
	/**
	 *
	 * @var string
	 */
	private $lang = 'de';
	
	private $categoryLevel1List = array();

	/**
	 *
	 * @var PlentyItemDataPushCategory
	 */
	private static $instance = null;
	
	public function __construct()
	{
		parent::__construct(__CLASS__);
	}
	
	/**
	 * singleton pattern
	 *
	 * @return PlentyItemDataPushCategory
	 */
	public static function getInstance()
	{
		if( !isset(self::$instance) || !(self::$instance instanceof PlentyItemDataPushCategory))
		{
			self::$instance = new PlentyItemDataPushCategory();
		}
	
		return self::$instance;
	}
	
	public function execute()
	{
		try
		{
			$oPlentySoapRequest_GetItemCategoryCatalogBase = new PlentySoapRequest_GetItemCategoryCatalogBase();
			$oPlentySoapRequest_GetItemCategoryCatalogBase->Level = 1;
			$oPlentySoapRequest_GetItemCategoryCatalogBase->Lang = $this->lang;
			
			/*
			 * do soap call
			 */
			$response	=	$this->getPlentySoap()->GetItemCategoryCatalogBase($oPlentySoapRequest_GetItemCategoryCatalogBase);
				
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
	 * @param PlentySoapResponse_GetItemCategoryCatalogBase $response
	 */
	private function parseResponse($response)
	{
		if(is_array($response->Categories->item))
		{
			foreach($response->Categories->item as $item)
			{
				$this->categoryLevel1List[$item->Name] = $item->CategoryID;
			}
		}
		/*
		 * only one country of delivery
		 */
		elseif (is_object($response->Categories->item))
		{
			$this->categoryLevel1List[$response->Categories->item->Name] = $response->Categories->item->CategoryID;
		}
	}
	
	/**
	 * 
	 * @param string $name
	 * @return number
	 */
	public function checkCategoryLevel1($name)
	{
		if(isset($this->categoryLevel1List[$name]))
		{
			return $this->categoryLevel1List[$name];
		}
		
		return 0;
	}
	
	/**
	 * 
	 * @param string $name
	 */
	public function saveNewCategoryLevel1($name)
	{
		try
		{
			$oPlentySoapRequest_AddItemCategory = new PlentySoapRequest_AddItemCategory();
			$oPlentySoapRequest_AddItemCategory->Lang = $this->lang;
			$oPlentySoapRequest_AddItemCategory->Level = 1;
			$oPlentySoapRequest_AddItemCategory->Name = $name;
				
			/*
			 * do soap call
			*/
			$response	=	$this->getPlentySoap()->AddItemCategory($oPlentySoapRequest_AddItemCategory);
		
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
						if($item->Code=='SAI0001' && is_numeric($item->Message))
						{
							$this->getLogger()->debug(__FUNCTION__.' new category: '.$name.' id: '.$item->Message);
							
							$this->categoryLevel1List[$name] = (int)$item->Message;
						}
					}
				}
			}
			else
			{
				$this->getLogger()->debug(__FUNCTION__.' request error');
			}
		}
		catch(Exception $e)
		{
			$this->getLogger()->crit(__FUNCTION__.' request error '. $e->getMessage());
		}
	}
	
	/**
	 * @param string $lang
	 */
	public function setLang($lang)
	{
		$this->lang = $lang;
	}
	
}
?>