<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';

/**
 * Save all order status params to local datatable.
 * 
 * @author phileon
 *
 */
class Adapter_GetOrderStatusList extends PlentySoapCall 
{
	public function __construct()
	{
		parent::__construct(__CLASS__);
	}
	
	/**
	 * lang for the request
	 * 
	 * @var string
	 */
	private $lang = 'de';
	
	/**
	 * 
	 * @param string $lang
	 */
	public function setLang($lang)
	{
		$this->lang = $lang;
	}
	
	public function execute()
	{
		try
		{
			/*
			 * Build the request and set the lang
			 */
			$orderStatusListRequest = new PlentySoapRequest_GetOrderStatusList();
			$orderStatusListRequest->Lang = $this->lang;
			
			/*
			 * do soap call
			 */
			$response	=	$this->getPlentySoap()->GetOrderStatusList($orderStatusListRequest);
	
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
				 * Parse and save the data
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
																'lang'			=>	$this->lang,
																'status_name'	=>	$orderStatus->OrderStatusName
															)
													);
	
		$this->getLogger()->debug(__FUNCTION__.' '.$query);
	
		DBQuery::getInstance()->replace($query);
	}
	
	/**
	 * delete existing data
	 */
	private function truncateTable()
	{
		DBQuery::getInstance()->truncate('TRUNCATE plenty_order_status');
	}
}

?>