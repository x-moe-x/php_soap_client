<?php

require_once 'PlentySoapDaemonAction.abstract.php';

/**
 * Save once a day all active method of payments types to local datatable.
 *
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class PlentySoapDaemonAction_GetMethodOfPayments extends PlentySoapDaemonAction 
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
		$soapCallAdapter = $this->getSoapCallAdapterClass('GetMethodOfPayments');
		if($soapCallAdapter instanceof Adapter_GetMethodOfPayments)
		{
			$soapCallAdapter->setVerbose(self::VERBOSE);
			
			$soapCallAdapter->execute();
		}
	}
}

?>