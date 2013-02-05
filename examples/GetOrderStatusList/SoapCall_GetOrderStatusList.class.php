<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';

/**
 *
 * It might be a better idea to run this call via
 * PlentySoap.daemon.php
 * So you can keep you local db/system up2date in an easy way
 *
 */
class SoapCall_GetOrderStatusList extends PlentySoapCall 
{
	public function __construct()
	{
		parent::__construct(__CLASS__);
	}
	
	/**
	 * the lang for the request
	 * @var string
	 */
	private $sLang = 'de';
	
	public function execute()
	{
		try
		{
			$this->getLogger()->debug(__FUNCTION__.' start');
	
			/*
			 * Build the request and set the lang
			 */
			$orderStatusListRequest = new PlentySoapRequest_GetOrderStatusList();
			$orderStatusListRequest->Lang = $this->sLang;
			
			/*
			 * do soap call
			 */
			$response	=	$this->getPlentySoap()->GetOrderStatusList($orderStatusListRequest);
	
			/*
			 * check soap response
			*/
			if( $response->Success == true )
			{
				$this->getLogger()->debug(__FUNCTION__.' Request Success - : GetOrderStatusList');
				
				/*
				 * Parse and save the data
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
	 * @param PlentySoapResponse_GetOrderStatusList $response
	 */
	private function parseResponse($response)
	{
		if(is_array($response->OrderStatus->item))
		{
			/*
			 * If more than one order status
			*/
			foreach ($response->OrderStatus->item as $orderStatus)
			{
				$this->saveInDatabase($orderStatus);
			}
		}
		/*
		 * only one order status
		*/
		elseif (is_object($response->OrderStatus->item))
		{
			$this->saveInDatabase($response->OrderStatus->item);
		}
	}
	
	/**
	 * Save the data in the database
	 *
	 * @param PlentySoapObject_GetOrderStatus $orderStatus
	 */
	private function saveInDatabase($orderStatus)
	{
		$query = 'REPLACE INTO `plenty_order_status` '.
								DBUtils::buildInsert(	array(	'order_status'	=>	$orderStatus->OrderStatus,
																'lang'			=>	$this->sLang,
																'status_name'	=>	$orderStatus->OrderStatusName
															)
													);
	
		$this->getLogger()->debug(__FUNCTION__.' '.$query);
	
		DBQuery::getInstance()->replace($query);
	}
}

?>