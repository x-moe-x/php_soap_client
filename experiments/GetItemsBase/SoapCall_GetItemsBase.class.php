<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetItemsBase.class.php';



class SoapCall_GetItemsBase extends PlentySoapCall
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

			$oPlentySoapRequest_GetItemsBase = new Request_GetItemsBase();

			$response		=	$this->getPlentySoap()->GetItemsBase($oPlentySoapRequest_GetItemsBase->getRequest());

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