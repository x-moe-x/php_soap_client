<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';

require_once 'Request_SetItemsTexts.class.php';


class SoapCall_SetItemsTexts extends PlentySoapCall 
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
						
			$oRequest_SetItemsTexts = new Request_SetItemsTexts();
			
			/*
			 * do soap call
			 */
			$response	= $this->getPlentySoap()->SetItemsTexts( $oRequest_SetItemsTexts->getRequest() );
			
			/*
			 * check soap response
			 */
			if( $response->Success == true )
			{
				$this->getLogger()->debug(__FUNCTION__.' Request Success');
			}
			else
			{
				$this->getLogger()->debug(__FUNCTION__.' Request Error');
			}
		}
		catch(Exception $e)
		{
			$this->onExceptionAction($e);
		}
	
	}
}

?>