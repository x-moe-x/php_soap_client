<?php

require_once ROOT.'lib/log/Logger.class.php';

/**
 * Run an example. 
 * Usage: look at cli/PlentymarketsSoapExampleLoader.cli.php
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class PlentymarketsSoapExampleLoader
{
	/**
	 *
	 * @var PlentymarketsSoapExampleLoader
	 */
	private static $instance = null;
	
	private function __construct()
	{
	
	}
	
	/**
	 * singleton pattern
	 *
	 * @return PlentymarketsSoapExampleLoader
	 */
	public static function getInstance()
	{
		if( !isset(self::$instance) || !(self::$instance instanceof PlentymarketsSoapExampleLoader))
		{
			self::$instance = new PlentymarketsSoapExampleLoader();
		}
	
		return self::$instance;
	}
	
	/**
	 * run an example
	 * 
	 * @param array $params
	 */
	public function run($params)
	{
		if(isset($params[1]) && strlen($params[1]))
		{
			$clazz = 'SoapCall_'.$params[1];
			$file = ROOT.'examples/'.$params[1].'/'.$clazz.'.class.php';
			
			if(is_file($file))
			{
				require_once $file;
				$o = new $clazz();
				$o->execute();
			}
			else
			{
				$this->getLogger()->crit('First param is not a valid example name');
				
				$this->displayValidExamples();
			}
		}
		else
		{
			$this->getLogger()->crit('You have to insert a valid example name e.g. shell> php cli/PlentymarketsSoapExampleLoader.cli.php GetServerTime');
			
			$this->displayValidExamples();
		}
	}
	
	/**
	 *
	 * @return Logger
	 */
	protected function getLogger()
	{
		return Logger::instance(__CLASS__);
	}
	
	/**
	 * display all examples
	 * 
	 */
	private function displayValidExamples()
	{
		echo 'List of valid example names:'.chr(10);
		
		foreach (glob(ROOT.'examples/*', GLOB_ONLYDIR) as $dir) 
		{
			if(is_file($dir.'/SoapCall_'.basename($dir).'.class.php'))
			{
				echo chr(9).basename($dir).chr(10);
			}
		}
	}
}
?>