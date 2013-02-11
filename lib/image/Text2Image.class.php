<?php

/**
 * A very simple possibility to position text on an image passed via a URL
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class Text2Image
{
	/**
	 *
	 * @var string
	 */
	const TMP_DIR = '/tmp/';
	
	/**
	 * 
	 * @var string
	 */
	private $imageUrl = '';
	
	/**
	 *
	 * @var int
	 */
	private $imageType = 0;
	
	/**
	 *
	 * @var string
	 */
	private $fileName = '';
	
	/**
	 * 
	 * @var string
	 */
	private $fontFile = '';
	
	/**
	 *
	 * @var Text2Image
	 */
	private static $instance = null;
	
	private function __construct()
	{
		$this->fontFile = ROOT.'lib/image/fonts/didact-gothic/DidactGothic.ttf';
	}
	
	/**
	 * singleton pattern
	 *
	 * @return Text2Image
	 */
	public static function getInstance()
	{
		if( !isset(self::$instance) || !(self::$instance instanceof Text2Image))
		{
			self::$instance = new Text2Image();
		}
	
		return self::$instance;
	}
	
	/**
	 * 
	 * @param string $text
	 * @param number $x
	 * @param number $y
	 * @param number $fontSize
	 * @param number $angle	the angle in degrees
	 * @param number $red value of red component
	 * @param number $green value of green component
	 * @param number $blue value of blue component
	 * 
	 * @return Text2Image
	 */
	public function pushText2Image($text, $x, $y, $fontSize, $angle=0, $red=255, $green=255, $blue=255)
	{
		$img = $this->getImageHandle();
		if(!empty($img))
		{
			$color = imagecolorallocate($img, $red, $green, $blue);
			
			imagefttext($img, $fontSize, $angle, $x, $y, $color, $this->fontFile, $text);
			
			if($this->imageType==IMAGETYPE_JPEG)
			{
				@imagejpeg($img, $this->fileName, 100);
			}
			else if($this->imageType==IMAGETYPE_PNG)
			{
				@imagepng($img, $this->fileName, 100);
			}
			
			imagedestroy($img);
		}
		
		return $this;
	}
	
	/**
	 * accept jpeg && png image urls
	 * 
	 * @return resource|string
	 */
	private function getImageHandle()
	{
		if(strlen($this->imageUrl))
		{
			$this->fileName = self::TMP_DIR . md5($this->imageUrl.time());
			
			file_put_contents($this->fileName, file_get_contents($this->imageUrl));
			
			if(is_file($this->fileName))
			{
				$this->imageType = exif_imagetype($this->fileName);
				if($this->imageType==IMAGETYPE_JPEG)
				{
					return @imagecreatefromjpeg($this->fileName);
				}
				else if($this->imageType==IMAGETYPE_PNG)
				{
					return @imagecreatefrompng($this->fileName);
				}
			}
		}
		
		return '';
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getFileEnding()
	{
		if($this->imageType==IMAGETYPE_JPEG)
		{
			return '.jpg';
		}
		else if($this->imageType==IMAGETYPE_PNG)
		{
			return '.png';
		}
		
		return '';
	}
	
	/**
	 * @param string $imageUrl
	 * 
	 * @return Text2Image
	 */
	public function setImageUrl($imageUrl) 
	{
		$this->imageUrl = $imageUrl;
		
		return $this;
	}
	
	/**
	 * @return the $fileName
	 */
	public function getFileName() 
	{
		return $this->fileName;
	}

	/**
	 * Get image file as base64. File will be deleted.
	 * 
	 * @return string
	 */
	public function getFileBase64()
	{
		if(is_file($this->fileName))
		{
			$c = base64_encode( file_get_contents($this->fileName) );
			
			@unlink($this->fileName);
			
			return $c;
		}
		
		return '';
	}
	
	
}

?>