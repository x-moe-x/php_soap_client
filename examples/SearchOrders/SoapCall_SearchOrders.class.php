<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_SearchOrders.class.php';


class SoapCall_SearchOrders extends PlentySoapCall 
{
	
	private $page								=	0;
	private $pages								=	-1;
	private $oPlentySoapRequest_SearchOrders	=	null;
	
	public function __construct()
	{
		parent::__construct(__CLASS__);
	}
	
	public function execute()
	{
		$this->getLogger()->debug(__FUNCTION__);
		
		if( $this->pages == -1 )
		{
			try
			{
				$oRequest_SearchOrders					=	new Request_SearchOrders();
				
				$this->oPlentySoapRequest_SearchOrders	=	$oRequest_SearchOrders->getRequest();
				
				/*
				 * do soap call
				 */
				$response		=	$this->getPlentySoap()->SearchOrders( $this->oPlentySoapRequest_SearchOrders );
				
				
				if( $response->Success == true )
				{
					$ordersFound	=	count($response->Orders->item);
					$pagesFound		=	$response->Pages;
					
					$this->getLogger()->debug(__FUNCTION__.' Request Success - orders found : '.$ordersFound .' / pages : '.$pagesFound );
					
					// auswerten
					$this->responseInterpretation( $response );
					
					if( $pagesFound > $this->page )
					{
						$this->page 	= 	1;
						$this->pages 	=	$pagesFound;
						
						$this->executePages();
					}
										
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
		else
		{
			$this->executePages();
		}
	}
	
	
	
	public function executePages()
	{
		while( $this->pages > $this->page )
		{
			$this->oPlentySoapRequest_SearchOrders->Page = $this->page;
			try
			{
				$response		=	$this->getPlentySoap()->SearchOrders( $this->oPlentySoapRequest_SearchOrders );
				
				if( $response->Success == true )
				{
					$ordersFound	=	count($response->Orders->item);
					$this->getLogger()->debug(__FUNCTION__.' Request Success - orders found : '.$ordersFound .' / page : '.$this->page );
					
					// auswerten
					$this->responseInterpretation( $response );
				}
				
				$this->page++;
				
			}	
			catch(Exception $e)
			{
				$this->onExceptionAction($e);
			}
		}
	}
	
	
	
	private function responseInterpretation(PlentySoapResponse_SearchOrders $oPlentySoapResponse_SearchOrders )
	{
		if( is_array( $oPlentySoapResponse_SearchOrders->Orders->item ) )
		{
			foreach( $oPlentySoapResponse_SearchOrders->Orders->item AS $order)
			{
				$this->getLogger()->debug(__FUNCTION__.' : ' 
								. 	' OrderID : '			.$order->OrderHead->OrderID				.','
								.	' ExternalOrderID : '	.$order->OrderHead->ExternalOrderID		.','
								.	' CustomerID : '		.$order->OrderHead->CustomerID			.','
								.	' TotalInvoice : '		.$order->OrderHead->TotalInvoice	
									);
				if( isset($order->OrderItems->item) && is_array( $order->OrderItems->item ) )
				{
					foreach( $order->OrderItems->item AS $oitems)
					{
						$this->getLogger()->debug(__FUNCTION__.' : '
								. 	' OrderID : '			.$order->OrderHead->OrderID	.','
								.	' Item SKU : '			.$oitems->SKU				.','
								.	' Quantity : '			.$oitems->Quantity			.','
								.	' Price : '				.$oitems->Price
							);
					}
				}
				else
				{
					if( isset($order->OrderItems->item) )
					{
						$this->getLogger()->debug(__FUNCTION__.' : '
								. 	' OrderID : '			.$order->OrderHead->OrderID			.','
								.	' Item SKU : '			.$order->OrderItems->item->SKU		.','
								.	' Quantity : '			.$order->OrderItems->item->Quantity	.','
								.	' Price : '				.$order->OrderItems->item->Price
						);
					}
				}

			}
		}
		else
		{
			$this->getLogger()->debug(__FUNCTION__.' : ' 
								. 	' OrderID : '			.$oPlentySoapResponse_SearchOrders->Orders->item->OrderID				.','
								.	' ExternalOrderID : '	.$oPlentySoapResponse_SearchOrders->Orders->item->ExternalOrderID		.','
								.	' CustomerID : '		.$oPlentySoapResponse_SearchOrders->Orders->item->CustomerID			.','
								.	' TotalInvoice : '		.$oPlentySoapResponse_SearchOrders->Orders->item->TotalInvoice	
									);
			if( is_array( $oPlentySoapResponse_SearchOrders->Orders->item->OrderItems->item ) )
			{
				foreach( $oPlentySoapResponse_SearchOrders->Orders->item->item AS $oitems)
				{
					$this->getLogger()->debug(__FUNCTION__.' : '
							. 	' OrderID : '			.$oPlentySoapResponse_SearchOrders->Orders->item->OrderID	.','
							.	' Item SKU : '			.$oitems->SKU				.','
							.	' Quantity : '			.$order->Quantity			.','
							.	' Price : '				.$order->Price
					);
				}
			}
			else
			{
				$this->getLogger()->debug(__FUNCTION__.' : '
						. 	' OrderID : '			.$oPlentySoapResponse_SearchOrders->Orders->item->OrderHead->OrderID			.','
						.	' Item SKU : '			.$oPlentySoapResponse_SearchOrders->Orders->item->OrderItems->item->SKU		.','
						.	' Quantity : '			.$oPlentySoapResponse_SearchOrders->Orders->item->OrderItems->item->Quantity	.','
						.	' Price : '				.$oPlentySoapResponse_SearchOrders->Orders->item->OrderItems->item->Price
				);
			}
		}
		$this->getLogger()->debug(__FUNCTION__.' : done' );
	}
}

?>