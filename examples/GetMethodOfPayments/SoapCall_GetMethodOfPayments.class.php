<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';

/**
 *
 * I might be a better idea to run this call via
 * PlentySoap.daemon.php
 * So you can keep you local db/system up2date in an easy way
 *
 */
class SoapCall_GetMethodOfPayments extends PlentySoapCall 
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
			$response	=	$this->getPlentySoap()->GetMethodOfPayments(new PlentySoapRequest_GetMethodOfPayments());
				
			/*
			 * check soap response
			*/
			if( $response->Success == true )
			{
				$this->getLogger()->debug(__FUNCTION__.' Request Success - : GetMethodOfPayments');
				
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
	 * @param PlentySoapResponse_GetMethodOfPayments $response
	 */
	private function parseResponse($response)
	{
		if(is_array($response->MethodOfPayment->item))
		{
			/*
			 * If more than one method of payment
			*/
			foreach ($response->MethodOfPayment->item as $methodOfPayment)
			{
				$this->saveInDatabase($methodOfPayment);
			}
		}
		/*
		 * only one method of payment
		*/
		elseif (is_object($response->MethodOfPayment->item))
		{
			$this->saveInDatabase($response->MethodOfPayment->item);
		}
		else
		{
			$this->getLogger()->debug(__FUNCTION__.' Empty Response');
		}
	}
	
	/**
	 * Save the data in the database
	 *
	 * @param PlentySoapObject_GetMethodOfPayments $methodOfPayment
	 */
	private function saveInDatabase($methodOfPayment)
	{
		$activeCountriesOfDelivery 		= array();
		$activeMultishops 				= array();
		
		/*
		 * parse all active countries of delivery in a string
		 */
		if( is_array($methodOfPayment->ActiveCountriesOfDelivery->item) )
		{
			foreach ($methodOfPayment->ActiveCountriesOfDelivery->item as $countryOfDelivery)
			{
				$activeCountriesOfDelivery[] = $countryOfDelivery->intValue;
			}
		}
		elseif( is_object($methodOfPayment->ActiveCountriesOfDelivery->item) )
		{
			$activeCountriesOfDelivery[] = $methodOfPayment->ActiveCountriesOfDelivery->item->intValue;
		}

		/*
		 * parse all active multishops in a string
		*/
		if( is_array($methodOfPayment->Multishops->item) )
		{
			foreach ($methodOfPayment->ActiveCountriesOfDelivery->item as $multishops)
			{
				$activeMultishops[] = $multishops->intValue;
			}
		}
		elseif( is_object($methodOfPayment->Multishops->item) )
		{
			$activeMultishops[] = $methodOfPayment->Multishops->item->intValue;
		}
		
		$query = 'REPLACE INTO `plenty_method_of_payments` '.
								DBUtils::buildInsert(	array(	'method_of_payment_id'		=>	$methodOfPayment->CountryID,
																'method_of_payment_name'	=>	$methodOfPayment->Name,
																'active_countries'			=>	implode(',', $activeCountriesOfDelivery),
																'active_multishops'			=>	implode(',', $activeMultishops)
															)
													);

		$this->getLogger()->debug(__FUNCTION__.' '.$query);
	
		DBQuery::getInstance()->replace($query);
	}
}
?>