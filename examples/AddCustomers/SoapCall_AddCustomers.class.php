<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_AddCustomers.class.php';


class SoapCall_AddCustomers extends PlentySoapCall
{
	private $CustomerID	= 0;

	public function __construct()
	{
		parent::__construct(__CLASS__);
	}

	public function execute()
	{
		try
		{
			$this->getLogger()->debug(__FUNCTION__.' start');

			$oRequest_AddCustomers = new Request_AddCustomers();

			/*
			 * do soap call
			 */
			$response	=	$this->getPlentySoap()->AddCustomers( $oRequest_AddCustomers->getRequest() );

			/*
			 * check soap response
			 */
			if( $response->Success == true )
			{
				$this->CustomerID = $response->ResponseMessages->item[0]->SuccessMessages->item[0]->Value;

				$this->getLogger()->debug(__FUNCTION__.' Request Success - AddCustomers : ' . $this->CustomerID );
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

	public function getCustomerID()
	{
		return $this->CustomerID;
	}
}

?>