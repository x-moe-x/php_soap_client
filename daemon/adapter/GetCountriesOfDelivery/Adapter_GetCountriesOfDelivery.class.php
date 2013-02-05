<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';

/**
 * Save all country of delivery names to local datatable.
 *
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class Adapter_GetCountriesOfDelivery extends PlentySoapCall 
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
			$response	=	$this->getPlentySoap()->GetCountriesOfDelivery(new PlentySoapRequest_GetCountriesOfDelivery);
			
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
	 * @param PlentySoapResponse_GetCountriesOfDelivery $response
	 */
	private function parseResponse($response)
	{
		if(is_array($response->CountriesOfDelivery->item))
		{
			/*
			 * If more than one country of delivery
			 */
			foreach ($response->CountriesOfDelivery->item as $countryOfDelivery)
			{
				$this->saveInDatabase($countryOfDelivery);
			}
		}
		/*
		 * only one country of delivery 
		 */
		elseif (is_object($response->CountriesOfDelivery->item))
		{
			$this->saveInDatabase($response->CountriesOfDelivery->item);
		}
	}
	
	/**
	 * save data in plenty_countries_of_delivery
	 * 
	 * @param PlentySoapObject_GetCountriesOfDelivery $countryOfDelivery
	 */
	private function saveInDatabase($countryOfDelivery)
	{
		$query = 'REPLACE INTO `plenty_countries_of_delivery` 
						'.DBUtils::buildInsert(	array(	'country_id'	=>	$countryOfDelivery->CountryID,
														'active'		=>	$countryOfDelivery->CountryActive,
														'country_name'	=>	$countryOfDelivery->CountryName,
														'iso_code_2'	=>	$countryOfDelivery->CountryISO2
												)
											);
		
		$this->getLogger()->debug(__FUNCTION__.' save country '.$countryOfDelivery->CountryISO2.' '.$countryOfDelivery->CountryName);
		
		DBQuery::getInstance()->replace($query);
	}
	
	/**
	 * delete existing data
	 */
	private function truncateTable()
	{
		DBQuery::getInstance()->truncate('TRUNCATE plenty_countries_of_delivery');
	}
}

?>