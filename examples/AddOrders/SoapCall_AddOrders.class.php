<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';
require_once ROOT.'examples/AddCustomers/SoapCall_AddCustomers.class.php';

class SoapCall_AddOrders extends PlentySoapCall
{
	public function __construct()
	{
		parent::__construct(__CLASS__);
	}
	
	private $OrderId = 0;
	
	public function execute()
	{
		try
		{
			$this->getLogger()->debug(__FUNCTION__.' start');
			
			$oAddCustomer = new SoapCall_AddCustomers();
			$oAddCustomer->execute();
			$customerId = $oAddCustomer->getCustomerID();
			
			if(isset($customerId) && $customerId > 0)
			{
				$oPlentySoapRequest_AddOrders = $this->buildRequest($customerId);
				/*
				 * do soap call
				 */
				$response	=	$this->getPlentySoap()->AddOrders( $oPlentySoapRequest_AddOrders );
				$this->getLogger()->debug(__LINE__.print_r($response,true));
				/*
				 * check soap response
				 */
				if( $response->Success == true )
				{
					$result = explode(';', $response->SuccessMessages->item[0]->Message);
					$this->OrderId = $result[1];
				
					$this->getLogger()->debug(__FUNCTION__.' Request Success - AddOrders : ' . $this->OrderId );
				}
				else
				{
					$this->getLogger()->debug(__FUNCTION__.' Request Error');
				}
			}
			else 
			{
				$this->getLogger()->debug(__FUNCTION__.' Invalid CustomerId');
			}			
		}
		catch(Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}
	
	/**
	 * Build the AddOrders Request Object
	 * 
	 * @param integer $customerId
	 * @return PlentySoapRequest_AddOrders
	 */
	private function buildRequest($customerId)
	{
		$request = new PlentySoapRequest_AddOrders();
		
		/*
		 * Build the PlentySoapObject_Order 
		 */
		$order = new PlentySoapObject_Order();
		
		/*
		 * Build order head
		 */
		$order->OrderHead = $this->getOrderHead($customerId);
		
		/*
		 * Build order item
		 */
		$order->OrderItems[] = $this->getFirstOrderItem();
		
		/*
		 * Build a secound order item
		 */
		$order->OrderItems[] = $this->getSecoundOrderItem();
		
		/*
		 * add the order to the request object
		 */
		$request->Orders[] = $order;
		
		return $request;
	}
	
	/**
	 * Build the order head object
	 * 
	 * @var integer $customerId
	 * @return PlentySoapObject_OrderHead
	 */
	private function getOrderHead($customerId)
	{
		$orderHead = new PlentySoapObject_OrderHead();
			
		$orderHead->OrderID = null;
		$orderHead->ExternalOrderID = 'SOAPOrderEx1';
		$orderHead->OrderType = 'order';
		$orderHead->OrderStatus = 7.00;
		$orderHead->OrderTimestamp = 1358672400; //20.01.2013 10:00 Uhr
		$orderHead->CustomerID = $customerId;
		$orderHead->MethodOfPaymentID = 0;
		$orderHead->ShippingMethodID = 1;
		$orderHead->ShippingProfileID = 1;
		$orderHead->ShippingID = 1;
		$orderHead->ShippingCosts = 4.90;
		$orderHead->ReferrerID = 1;
		$orderHead->Marking1ID = 20;
		$orderHead->ResponsibleID = 1;
		$orderHead->WarehouseID = 1;
		$orderHead->MultishopID = 0;
		$orderHead->Currency = 'EUR';
		$orderHead->EstimatedTimeOfShipment = 1359190800; //26.01.2013 10:00 Uhr
		$orderHead->PaidTimestamp = 1358845200; //22.01.2013 10:00 Uhr
		$orderHead->DoneTimestamp = 1359018000; //24.01.2013 10:00 Uhr
		$orderHead->PaymentStatus = 1;
		$orderHead->TotalVAT = 212.64;
		$orderHead->TotalNetto = 1115.04;
		$orderHead->TotalBrutto = 1326.90;
		$orderHead->IsNetto = 0;
		$orderHead->TotalInvoice = 1331.80;
		
		return $orderHead;
	}
	
	/**
	 * Build order item object
	 * 
	 * @return PlentySoapObject_OrderItem
	 */
	private function getFirstOrderItem()
	{
		$orderItem = new PlentySoapObject_OrderItem();
		$orderItem->OrderID = null;
		$orderItem->OrderRowID = null;
		$orderItem->ItemID = '100001';
		$orderItem->ExternalOrderItemID = 'Example-SOAP-Item-1';
		$orderItem->ReferrerID = 1;
		$orderItem->Quantity = 10;
		$orderItem->ItemText = 'Example for the SOAP Call Item - 1';
		$orderItem->VAT = 19.00;
		$orderItem->Price = 100.99;
		$orderItem->WarehouseID = 1;
		$orderItem->Currency = 'EUR';
		$orderItem->ItemNo = 'EXSO01';
		$orderItem->ExternalItemID = 'ExSOAP01';
		
		return $orderItem;
	}
	
	/**
	 * Build order item object
	 *
	 * @return PlentySoapObject_OrderItem
	 */
	private function getSecoundOrderItem()
	{
		$orderItem = new PlentySoapObject_OrderItem();
		$orderItem->OrderID = null;
		$orderItem->OrderRowID = null;
		$orderItem->ItemID = '100002';
		$orderItem->ExternalOrderItemID = 'Example-SOAP-Item-2';
		$orderItem->ReferrerID = 1;
		$orderItem->Quantity = 20;
		$orderItem->ItemText = 'Example for the SOAP Call Item - 2';
		$orderItem->VAT = 19.00;
		$orderItem->Price = 15.85;
		$orderItem->WarehouseID = 1;
		$orderItem->Currency = 'EUR';
		$orderItem->ItemNo = 'EXSO02';
		$orderItem->ExternalItemID = 'ExSOAP02';
	
		return $orderItem;
	}
	
	public function getOrderId()
	{
		return $this->OrderId;
	}
}

?>