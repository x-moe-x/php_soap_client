<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';

/**
 * 
 * @author phileon
 *
 */
class SoapCall_GetServerTime extends PlentySoapCall
{
	
	public function __construct()
	{
		parent::__construct(__CLASS__);
	}
	
	public function execute() 
	{
		try
		{
			$this->getLogger()->debug(__FUNCTION__.' start');
			
			/*
			 * do soap call
			 */
			$response	= $this->getPlentySoap()->GetServerTime();
	
			/*
			 * check soap response
			 */
			if( $response->Success == true )
			{
				$this->getLogger()->debug(__FUNCTION__.' request success - servertime : '.$response->Timestamp);
			}
			else
			{
				$this->getLogger()->debug(__FUNCTION__.' request Error');
			}
		}
		catch(Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}


}

?>