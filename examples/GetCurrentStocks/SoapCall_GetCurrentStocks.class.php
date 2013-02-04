<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';

class SoapCall_GetCurrentStocks extends PlentySoapCall 
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
			$response	=	$this->getPlentySoap()->GetCurrentStocks(new PlentySoapRequest_GetCurrentStocks());
			
			/*
			 * check soap response
			 */
			if( $response->Success == true )
			{
				$this->getLogger()->debug(__FUNCTION__.' Request Success - : GetCurrentStocks');
				
				/*
				 * parse and save the data
				 */
				$this->parseResponse($response);
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
	
	/**
	 * Parse the response
	 * 
	 * @param PlentySoapResponse_GetCurrentStocks $response
	 */
	private function parseResponse($response)
	{
		
	}
	
	/**
	 * Save the data in the database
	 * 
	 * @param PlentySoapObject_GetCurrentStocks 
	 */
	private function saveInDatabase()
	{
		
	}
}

?>