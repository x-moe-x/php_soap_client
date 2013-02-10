<?php

require_once ROOT.'lib/log/Logger.class.php';
require_once ROOT.'lib/soap/autoloader/SoapModelLoader.fnc.php';

/**
 * Simple test data generator 
 * 
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class PlentyTestdataGenerator
{
	/**
	 * TestdataGeneratorType name
	 * 
	 * @var string 
	 */
	private $type = '';
	
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
	
	/**
	 *
	 * @var PlentyTestdataGenerator
	 */
	private static $instance = null;
	
	private function __construct()
	{
	
	}
	
	/**
	 * singleton pattern
	 *
	 * @return PlentyTestdataGenerator
	 */
	public static function getInstance()
	{
		if( !isset(self::$instance) || !(self::$instance instanceof PlentyTestdataGenerator))
		{
			self::$instance = new PlentyTestdataGenerator();
		}
	
		return self::$instance;
	}
	
	/**
	 * 
	 * @param array $params
	 */
	public function execute($params)
	{
		$this->handleParams($params);
		
		$plentyTestdataGeneratorType = $this->getGeneratorType();
		$plentyTestdataGeneratorType->setLang($this->lang);
		$plentyTestdataGeneratorType->setQuantity($this->quantity);
		
		$plentyTestdataGeneratorType->execute();
	}
	
	/**
	 * @return PlentyTestdataGeneratorTypeBase
	 */
	private function getGeneratorType()
	{
		if(!strlen($this->type))
		{
			$this->getLogger()->crit(__FUNCTION__.' you have to insert a type name e.g. type:item');
			exit;
		}
		
		$clazz = 'PlentyTestdataGeneratorType_'.$this->type;
		$file = ROOT.'testdata/types/'.$this->type.'/'.$clazz.'.class.php';
		
		if(!is_file($file))
		{
			$this->getLogger()->crit(__FUNCTION__.' I did not find type classfile: '.$file);
			exit;
		}
		
		require_once $file;
		$o = new $clazz();
		
		if($o instanceof PlentyTestdataGeneratorTypeBase)
		{
			return $o;
		}
		else
		{
			$this->getLogger()->crit(__FUNCTION__.' type class did not extends PlentyTestdataGeneratorTypeBase');
			exit;
		}
	}
	
	/**
	 *
	 * @return Logger
	 */
	private function getLogger()
	{
		return Logger::instance(__CLASS__);
	}
	
	/**
	 * sort out $argv cli params
	 *
	 * @param string[] $params $argv cli params
	 */
	private function handleParams($params)
	{
		if($params && is_array($params) && isset($params[1]))
		{
			foreach($params as $p)
			{
				switch($p)
				{
					case strrpos($p, 'type:')!==false:
							
						$this->type = str_replace('type:', '', $p);
						break;
							
					case strrpos($p, 'lang:')!==false:
							
						$this->lang = str_replace('lang:', '', $p);
						break;
	
					case strrpos($p, 'quantity:')!==false:
							
						$this->quantity = str_replace('quantity:', '', $p);
						break;
				}
			}
		}
	}
}
?>