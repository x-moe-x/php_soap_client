<?php

require_once 'PlentySoapDaemonAction.abstract.php';

/**
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
		 * once a day
		 */
		$this->setTimeInterval(1440);
	}
	
	public function execute()
	{
		echo 'hello world!' . chr(10);
	}
}

?>