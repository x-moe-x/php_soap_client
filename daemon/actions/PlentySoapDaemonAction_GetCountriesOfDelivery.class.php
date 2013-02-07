<?php

require_once 'PlentySoapDaemonAction.abstract.php';

/**
 * Save once a day all country of delivery names to local datatable.
 *
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class PlentySoapDaemonAction_GetCountriesOfDelivery extends PlentySoapDaemonAction 
{
	public function __construct()
	{
		parent::__construct(__CLASS__);
		
		/*
		 * run once a day
		 */
		$this->setTimeInterval(1440);
		
		/*
		 * deactive this action for PlentySoapDaemon?
		 */
		$this->setDeactiveThisAction(true);
	}
	
	public function execute()
	{
		$soapCallAdapter = $this->getSoapCallAdapterClass($this->getClassPostfix(__CLASS__));
		if($soapCallAdapter instanceof Adapter_GetCountriesOfDelivery)
		{
			$soapCallAdapter->setVerbose(self::VERBOSE);
			
			$soapCallAdapter->execute();
		}
	}
}

?>