<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';

/**
 * NOT READY
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class PlentyItemDataPushImage extends PlentySoapCall
{
	/**
	 *
	 * @var int
	 */
	private $itemId = 0;
	
	/**
	 * 
	 * @var string
	 */
	private $imageUrl = '';

	/**
	 *
	 * @var PlentyItemDataPushImage
	 */
	private static $instance = null;
	
	public function __construct()
	{
		parent::__construct(__CLASS__);
	}
	
	/**
	 * singleton pattern
	 *
	 * @return PlentyItemDataPushImage
	 */
	public static function getInstance()
	{
		if( !isset(self::$instance) || !(self::$instance instanceof PlentyItemDataPushImage))
		{
			self::$instance = new PlentyItemDataPushImage();
		}
	
		return self::$instance;
	}
	
	public function execute()
	{
		try
		{
			$oPlentySoapObject_ItemImage = new PlentySoapObject_ItemImage();
			$oPlentySoapObject_ItemImage->ImageURL = $this->imageUrl;
			$oPlentySoapObject_ItemImage->ImageID = $this->itemId;
			
			$oPlentySoapRequest_AddItemsImage = new PlentySoapRequest_AddItemsImage();
			$oPlentySoapRequest_AddItemsImage->Image = $oPlentySoapObject_ItemImage;
			$oPlentySoapRequest_AddItemsImage->ItemID = $this->itemId;
			
			/*
			 * do soap call
			 */
			$response	=	$this->getPlentySoap()->AddItemsImage($oPlentySoapRequest_AddItemsImage);
				
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
	 * @return PlentyItemDataPushImage
	 */
	public function setItemId($itemId) 
	{
		$this->itemId = $itemId;
		
		return $this;
	}

	/**
	 * @param string $imageUrl
	 * 
	 * @return PlentyItemDataPushImage
	 */
	public function setImageUrl($imageUrl) 
	{
		$this->imageUrl = $imageUrl;
		
		return $this;
	}

	

}
?>