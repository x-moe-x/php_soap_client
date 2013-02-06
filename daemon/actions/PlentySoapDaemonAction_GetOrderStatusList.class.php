<?php

require_once 'PlentySoapDaemonAction.abstract.php';

/**
 * Save once a day all order status params to local datatable.
 *
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class PlentySoapDaemonAction_GetOrderStatusList extends PlentySoapDaemonAction 
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
		$soapCallAdapter = $this->getSoapCallAdapterClass('GetOrderStatusList');
		if($soapCallAdapter instanceof Adapter_GetOrderStatusList)
		{
			$soapCallAdapter->setVerbose(self::VERBOSE);
			$soapCallAdapter->setLang('de');
			$soapCallAdapter->execute();
		}
	}
}

?>