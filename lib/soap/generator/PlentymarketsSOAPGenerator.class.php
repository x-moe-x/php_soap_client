<?php

/*
 * load config
 */
require_once ROOT.'config/soap.inc.php';


require_once ROOT.'lib/soap/generator/PlentymarketsSOAPControllerGenerator.class.php';
require_once ROOT.'lib/soap/generator/PlentymarketsSOAPModelGenerator.class.php';



/**
 * Simple generator for plentymarkets soap api objects
 * 
 * run: shell> php cli/PlentymarketsSOAPGenerator.cli.php
 * 
 * check config file: soap.inc.php
 * 
 * 
 * @author phileon
 *
 */
class PlentymarketsSOAPGenerator
{
	/**
	 *
	 * @var PlentymarketsSOAPGenerator
	 */
	private static $instance = null;
	
	private function __construct()
	{
	
	}
	
	/**
	 * singleton pattern
	 *
	 * @return PlentymarketsSOAPGenerator
	 */
	public static function getInstance()
	{
		if( !isset(self::$instance) || !(self::$instance instanceof PlentymarketsSOAPGenerator))
		{
			self::$instance = new PlentymarketsSOAPGenerator();
		}
	
		return self::$instance;
	}
	
	/**
	 * generate model an controller files
	 */
	public function run()
	{
		$plentymarketsSOAPModelGenerator = new PlentymarketsSOAPModelGenerator();
		$plentymarketsSOAPModelGenerator->run();
		
		$plentymarketsSOAPControllerGenerator = new PlentymarketsSOAPControllerGenerator();
		$plentymarketsSOAPControllerGenerator->run();
	}
}

?>