<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';
require_once ROOT.'lib/image/Text2Image.class.php';


/**
 * This class adds a picture an added item. 
 * For more test data, the image will get texts and then transmitted twice.
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
	 * @var PlentySoapRequest_AddItemsImage
	 */
	private $oPlentySoapRequest_AddItemsImage = null;

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
	
	/**
	 * Create multiple versions of the image and then passes these versions directly to ->execute()
	 * 
	 * @param number $numberOfImages
	 */
	public function pushTestImage2API($numberOfImages=2)
	{
		if(strlen($this->imageUrl) && $this->itemId>0)
		{
			for($i=1; $i<=$numberOfImages; $i++)
			{
				$base64 = Text2Image::getInstance()->setImageUrl($this->imageUrl)
													->pushText2Image('#'.$i, 20, 110, 96)
													->getFileBase64();
				
				$fileEnding = Text2Image::getInstance()->getFileEnding();
				
				if(strlen($base64))
				{
					$oPlentySoapObject_FileBase64Encoded = new PlentySoapObject_FileBase64Encoded();
					$oPlentySoapObject_FileBase64Encoded->FileData = $base64;
					$oPlentySoapObject_FileBase64Encoded->FileEnding = $fileEnding;
					$oPlentySoapObject_FileBase64Encoded->FileName = 'plentymarkets_testimage_'.$this->itemId.'_'.$i;
					
					$oPlentySoapObject_ItemImage = new PlentySoapObject_ItemImage();
					$oPlentySoapObject_ItemImage->ImageData = $oPlentySoapObject_FileBase64Encoded;
					$oPlentySoapObject_ItemImage->Availability = 1;
					$oPlentySoapObject_ItemImage->Position = ($i-1);
						
					$this->oPlentySoapRequest_AddItemsImage = new PlentySoapRequest_AddItemsImage();
					$this->oPlentySoapRequest_AddItemsImage->Image = $oPlentySoapObject_ItemImage;
					$this->oPlentySoapRequest_AddItemsImage->ItemID = $this->itemId;
					
					$this->execute();
				}
				else
				{
					$this->getLogger()->crit(__FUNCTION__.' I did not get base64 data.');
				}
			}
			
		}
		else
		{
			$this->getLogger()->crit(__FUNCTION__.' I miss some data - itemId:'.$this->itemId.' imageUrl:'.$this->imageUrl);
		}
	}
	
	public function execute()
	{
		if(!($this->oPlentySoapRequest_AddItemsImage instanceof PlentySoapRequest_AddItemsImage))
		{
			$this->getLogger()->crit(__FUNCTION__.' $this->oPlentySoapRequest_AddItemsImage is not an instance of PlentySoapRequest_AddItemsImage');
			return ;	
		}
		
		try
		{
			/*
			 * do soap call
			 */
			$response	=	$this->getPlentySoap()->AddItemsImage($this->oPlentySoapRequest_AddItemsImage);
				
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