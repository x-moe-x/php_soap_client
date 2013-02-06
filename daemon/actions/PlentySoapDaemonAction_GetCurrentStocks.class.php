<?php

require_once 'PlentySoapDaemonAction.abstract.php';

/**
 * Sync stock with local datatable. 
 * More informations you will find in adapter/Adapter_GetCurrentStocks.class.php
 *
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class PlentySoapDaemonAction_GetCurrentStocks extends PlentySoapDaemonAction 
{
	public function __construct()
	{
		parent::__construct(__CLASS__);
		
		/*
		 * run every 15 minutes
		 */
		$this->setTimeInterval(15);
		
		/*
		 * deactive this action for PlentySoapDaemon?
		 */
		$this->setDeactiveThisAction(false);
	}
	
	public function execute()
	{
		$soapCallAdapter = $this->getSoapCallAdapterClass('GetCurrentStocks');
		if($soapCallAdapter instanceof Adapter_GetCurrentStocks)
		{
			$soapCallAdapter->setVerbose(self::VERBOSE);
			
			/*
			 * insert your warehouse id
			 */
			$soapCallAdapter->setWarehouseId(1);
			$soapCallAdapter->execute();
			
			/*
			 * get stock for another warehouse?
			 * remove slashes and insert next warehouse id
			 */
			//$soapCallAdapter->setWarehouseId(5);
			//$soapCallAdapter->execute();
		}
	}
}

?>