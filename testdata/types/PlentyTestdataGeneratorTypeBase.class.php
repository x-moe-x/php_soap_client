<?php

/**
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
abstract class PlentyTestdataGeneratorTypeBase
{
	/**
	 *
	 * @var string
	 */
	private $lang = 'de';
	
	/**
	 * How many data should I generate?
	 *
	 * @var int
	 */
	private $quantity = 300;
	
	public abstract function execute();
	
	/**
	 * @return the $lang
	 */
	public function getLang() 
	{
		return $this->lang;
	}

	/**
	 * @return the $quantity
	 */
	public function getQuantity() 
	{
		return $this->quantity;
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
}

?>