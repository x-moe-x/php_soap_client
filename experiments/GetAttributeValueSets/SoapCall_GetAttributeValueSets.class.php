<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetAttributeValueSets.class.php';


class SoapCall_GetAttributeValueSets extends PlentySoapCall
{

	public function __construct()
	{
		parent::__construct(__CLASS__);
	}

	public function execute()
	{
		$this->getLogger()->debug(__FUNCTION__);

		try
		{

			/*
			 * do soap call
			*/
			
			$oPlentySoapRequest_GetAttributeValueSets = new Request_GetAttributeValueSets();			
			
			$response		=	$this->getPlentySoap()->GetAttributeValueSets($oPlentySoapRequest_GetAttributeValueSets->getRequest(1));


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