<?php

/*
 * load config
 */
require_once ROOT.'config/soap.inc.php';


require_once ROOT.'lib/soap/generator/PlentymarketsSoapControllerGenerator.class.php';
require_once ROOT.'lib/soap/generator/PlentymarketsSoapModelGenerator.class.php';



/**
 * Simple generator for plentymarkets soap api objects
 * 
 * run: shell> php cli/PlentymarketsSoapGenerator.cli.php
 * 
 * check config file: soap.inc.php
 * 
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class PlentymarketsSoapGenerator
{
	/**
	 *
	 * @var PlentymarketsSoapGenerator
	 */
	private static $instance = null;
	
	private function __construct()
	{
	
	}
	
	/**
	 * singleton pattern
	 *
	 * @return PlentymarketsSoapGenerator
	 */
	public static function getInstance()
	{
		if( !isset(self::$instance) || !(self::$instance instanceof PlentymarketsSoapGenerator))
		{
			self::$instance = new PlentymarketsSoapGenerator();
		}
	
		return self::$instance;
	}
	
	/**
	 * generate model an controller files
	 */
	public function run()
	{
		$plentymarketsSOAPModelGenerator = new PlentymarketsSoapModelGenerator();
		$plentymarketsSOAPModelGenerator->run();
		
		$plentymarketsSOAPControllerGenerator = new PlentymarketsSoapControllerGenerator();
		$plentymarketsSOAPControllerGenerator->run();
	}
}

?>