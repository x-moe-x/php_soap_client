<?php

require_once 'PlentySoapDaemonAction.abstract.php';

/**
 * Save once a day defined warehouses to local datatable.
 *
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class PlentySoapDaemonAction_GetWarehouseList extends PlentySoapDaemonAction 
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
		$this->setDeactiveThisAction(false);
	}
	
	public function execute()
	{
		$soapCallAdapter = $this->getSoapCallAdapterClass('GetWarehouseList');
		if($soapCallAdapter instanceof Adapter_GetWarehouseList)
		{
			$soapCallAdapter->setVerbose(self::VERBOSE);
			
			$soapCallAdapter->execute();
		}
	}
}

?>