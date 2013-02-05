<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';

/**
 * Save VAT configurations to local datatable.
 *
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class Adapter_GetVATConfig extends PlentySoapCall 
{
	public function __construct()
	{
		parent::__construct(__CLASS__);
	}
	
	/**
	 * the default country id
	 * @var integer
	 */
	private $defaultCountryId = 0;
	
	public function execute()
	{
		try
		{
			/*
			 * do soap call
			 */
			$response	=	$this->getPlentySoap()->GetVATConfig();
	
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
				 * parse the response and save the data
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
	 * parse the response
	 * 
	 * @param PlentySoapResponse_GetVATConfig $response
	 */
	private function parseResponse($response)
	{
		/*
		 * parse the default country 
		 */
		$this->parseVatConfig(	$response->DefaultVAT, 
								$this->defaultCountryId);
		
		/*
		 * parse all the other countries 
		 */
		if(is_array($response->CountryVAT->item))
		{
			foreach ($response->CountryVAT->item as $countryVat)
			{
				$this->parseVatConfig(	$countryVat->CountryVAT, 
										$countryVat->CountryID);
			}
		}
		elseif (is_object($response->CountryVAT->item))
		{
			$this->parseVatConfig(	$response->CountryVAT->item->CountryVAT, 
									$response->CountryVAT->item->CountryID);
		}
	}
	
	/**
	 * parse the vat config
	 * 
	 * @param PlentySoapObject_GetVATConfig $vat
	 * @param unknown_type $countryId
	 */
	private function parseVatConfig($vat, $countryId)
	{
		if(is_array($vat->item))
		{
			foreach ($vat->item as $vatConfig)
			{
				$this->saveInDatabase($vatConfig, $countryId);
			}
		}
		elseif (is_object($vat->item))
		{
			$this->saveInDatabase($vat->item, $countryId);
		}	
	}
	
	/**
	 * save the data in plenty_vat_config
	 * 
	 * @param PlentySoapObject_GetVatConfig $vatConfig
	 * @param integer $countryId
	 */
	private function saveInDatabase($vatConfig, $countryId)
	{
		$query = 'REPLACE INTO `plenty_vat_config` '.
								DBUtils::buildInsert(	array(	'country_id'	=>	$countryId,
																'vat_id'		=>	$vatConfig->InternalVATID,
																'vat_value'		=>	$vatConfig->VATValue
															)
													);
	
		$this->getLogger()->debug(__FUNCTION__.' save VAT config id:'.$vatConfig->InternalVATID.' countryId:'.$countryId.' '.$vatConfig->VATValue.'%');
	
		DBQuery::getInstance()->replace($query);
	}
	
	/**
	 * delete existing data
	 */
	private function truncateTable()
	{
		DBQuery::getInstance()->truncate('TRUNCATE plenty_vat_config');
	}
}

?>