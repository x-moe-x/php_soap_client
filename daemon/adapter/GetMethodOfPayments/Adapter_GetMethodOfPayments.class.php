<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';

/**
 * Save all active method of payments types to local datatable.
 *
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class Adapter_GetMethodOfPayments extends PlentySoapCall 
{
	public function __construct()
	{
		parent::__construct(__CLASS__);
	}
	
	public function execute()
	{
		try
		{
			/*
			 * do soap call
		 	 */
			$response	=	$this->getPlentySoap()->GetMethodOfPayments(new PlentySoapRequest_GetMethodOfPayments());
				
			/*
			 * check soap response
			 */
			if( $response->Success == true )
			{
				$this->getLogger()->debug(__FUNCTION__.' request succeed');
				
				/*
				 * delete old data
				 */
				$this->truncateTable();
				
				/*
				 * parse and save the data
				 */
				$this->parseResponse($response);
			}
			else
			{
				$this->getLogger()->debug(__FUNCTION__.' request error');
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
			$this->getLogger()->err(__FUNCTION__.' empty response');
		}
	}
	
	/**
	 * Save the data in plenty_method_of_payments
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
								DBUtils::buildInsert(	array(	'method_of_payment_id'		=>	$methodOfPayment->MethodOfPaymentID,
																'method_of_payment_name'	=>	$methodOfPayment->Name,
																'active_countries'			=>	implode(',', $activeCountriesOfDelivery),
																'active_multishops'			=>	implode(',', $activeMultishops)
															)
													);

		$this->getLogger()->debug(__FUNCTION__.' new MOP '.$methodOfPayment->MethodOfPaymentID.' '.$methodOfPayment->Name);
	
		DBQuery::getInstance()->replace($query);
	}
	
	/**
	 * delete existing data
	 */
	private function truncateTable()
	{
		DBQuery::getInstance()->truncate('TRUNCATE plenty_method_of_payments');
	}
}
?>