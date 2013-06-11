<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_SearchOrders.class.php';
require_once ROOT.'experiments/GetAttributeValueSets/Request_GetAttributeValueSets.class.php';
require_once ROOT.'includes/DBLastUpdate.php';


class SoapCall_SearchOrders extends PlentySoapCall
{

	private $page								=	0;
	private $pages								=	-1;
	private $oPlentySoapRequest_SearchOrders	=	null;
	private $oAttributeValueSetIDs				=	array();

	/// db-function name to store corresponding last update timestamps
	private $functionName						=	'SearchOrderExperiment';

	public function __construct()
	{
		parent::__construct(__CLASS__);
	}

	public function execute()
	{
		$this->getLogger()->debug(__FUNCTION__);

		list($lastUpdate, $currentTime, $id) = lastUpdateStart($this->functionName);

		if( $this->pages == -1 )
		{
			try
			{
				$oRequest_SearchOrders					=	new Request_SearchOrders();

				$this->oPlentySoapRequest_SearchOrders	=	$oRequest_SearchOrders->getRequest($lastUpdate, $currentTime);

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
		
		// process any found AttributeValueSetIDs
		if (count($this->oAttributeValueSetIDs) > 0)
			$this->processAttributeValueSetIDs(array_unique($this->oAttributeValueSetIDs));

		lastUpdateFinish($id,$currentTime,$this->functionName);
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

	private function processOrderHead($oOrderHead)
	{
		$this->getLogger()->debug(__FUNCTION__.' : '
				. 	' OrderID : '			.$oOrderHead->OrderID				.','
				.	' ExternalOrderID : '	.$oOrderHead->ExternalOrderID		.','
				.	' CustomerID : '		.$oOrderHead->CustomerID			.','
				.	' TotalInvoice : '		.$oOrderHead->TotalInvoice
		);

		// store OrderHeads into DB		
		$query = 'REPLACE INTO `OrderHead` '.
				DBUtils::buildInsert(
						array(
								'Currency'					=>	$oOrderHead->Currency,
								'CustomerID'				=>	$oOrderHead->CustomerID,
								'DeliveryAddressID'			=>	$oOrderHead->DeliveryAddressID,
								'DoneTimestamp'				=>	$oOrderHead->DoneTimestamp,
								'DunningLevel'				=>	$oOrderHead->DunningLevel,
								'EbaySellerAccount'			=>	$oOrderHead->EbaySellerAccount,
								'EstimatedTimeOfShipment'	=>	$oOrderHead->EstimatedTimeOfShipment,
								'ExchangeRatio'				=>	$oOrderHead->ExchangeRatio,
								'ExternalOrderID'			=>	$oOrderHead->ExternalOrderID,
								'Invoice'					=>	$oOrderHead->Invoice,
								'IsNetto'					=>	$oOrderHead->IsNetto,
								'LastUpdate'				=>	$oOrderHead->LastUpdate,
								'Marking1ID'				=>	$oOrderHead->Marking1ID,
								'MethodOfPaymentID'			=>	$oOrderHead->MethodOfPaymentID,
								'MultishopID'				=>	$oOrderHead->MultishopID,
							/*	'OrderDocumentNumbers'		=>	$oOrderHead->OrderDocumentNumbers, ignored since not part of the request */
								'OrderID'					=>	$oOrderHead->OrderID,
							/*	'OrderInfos'				=>	$oOrderHead->OrderInfos, ignored since not part of the request */
								'OrderStatus'				=>	$oOrderHead->OrderStatus,
								'OrderTimestamp'			=>	$oOrderHead->OrderTimestamp,
								'OrderType'					=>	$oOrderHead->OrderType,
								'PackageNumber'				=>	$oOrderHead->PackageNumber,
								'PaidTimestamp'				=>	$oOrderHead->PaidTimestamp,
								'ParentOrderID'				=>	$oOrderHead->ParentOrderID,
								'PaymentStatus'				=>	$oOrderHead->PaymentStatus,
								'ReferrerID'				=>	$oOrderHead->ReferrerID,
								'RemoteIP'					=>	$oOrderHead->RemoteIP,
								'ResponsibleID'				=>	$oOrderHead->ResponsibleID,
								'SalesAgentID'				=>	$oOrderHead->SalesAgentID,
								'SellerAccount'				=>	$oOrderHead->SellerAccount,
								'ShippingCosts'				=>	$oOrderHead->ShippingCosts,
								'ShippingID'				=>	$oOrderHead->ShippingID,
								'ShippingMethodID'			=>	$oOrderHead->ShippingMethodID,
								'ShippingProfileID'			=>	$oOrderHead->ShippingProfileID,
								'TotalBrutto'				=>	$oOrderHead->TotalBrutto,
								'TotalInvoice'				=>	$oOrderHead->TotalInvoice,
								'TotalNetto'				=>	$oOrderHead->TotalNetto,
								'TotalVAT'					=>	$oOrderHead->TotalVAT,
								'WarehouseID'				=>	$oOrderHead->WarehouseID
						)
				);

		DBQuery::getInstance()->replace($query);

		// delete old OrderItems to prevent duplicate insertion

		$query = 'DELETE FROM `OrderItem` WHERE `OrderID` = ' . $oOrderHead->OrderID;
		DBQuery::getInstance()->delete($query);
	}

	private function processOrderItem($oOrderItem, $oOrderID)
	{
		$this->getLogger()->debug(__FUNCTION__.' : '
				. 	' OrderID : '			.$oOrderID	.','
				.	' Item SKU : '			.$oOrderItem->SKU				.','
				.	' Quantity : '			.$oOrderItem->Quantity			.','
				.	' Price : '				.$oOrderItem->Price
		);
		
		// store OrderHeads into DB
		$query = 'REPLACE INTO `OrderItem` '.
				DBUtils::buildInsert(
						array(
								'BundleItemID'			=>	$oOrderItem->BundleItemID,
								'Currency'				=>	$oOrderItem->Currency,
								'ExternalItemID'		=>	$oOrderItem->ExternalItemID,
								'ExternalOrderItemID'	=>	$oOrderItem->ExternalOrderItemID,
								'ItemID'				=>	$oOrderItem->ItemID,
								'ItemNo'				=>	$oOrderItem->ItemNo,
								'ItemRebate'			=>	$oOrderItem->ItemRebate,
								'ItemText'				=>	$oOrderItem->ItemText,
								'NeckermannItemNo'		=>	$oOrderItem->NeckermannItemNo,
								'OrderID'				=>	$oOrderItem->OrderID,
								'OrderRowID'			=>	$oOrderItem->OrderRowID,
								'Price'					=>	$oOrderItem->Price,
								'Quantity'				=>	$oOrderItem->Quantity,
								'ReferrerID'			=>	$oOrderItem->ReferrerID,
								'SKU'					=>	$oOrderItem->SKU,
							/*	'SalesOrderProperties'	=>	$oOrderItem->SalesOrderProperties, ignored since not part of the request */
								'VAT'					=>	$oOrderItem->VAT,
								'WarehouseID'			=>	$oOrderItem->WarehouseID
								
						)
				);
		
		DBQuery::getInstance()->replace($query);

		// check SKU for non-zero AttributeValueSetId
		$matches = array();
		if (preg_match('/\d+-\d+-(\d+)/', $oOrderItem->SKU, $matches) && (count($matches) == 2))
		{
			$oAttributeValueSetID = intval($matches[1]);

			if ($oAttributeValueSetID != 0)
			{
				$this->oAttributeValueSetIDs[] = $oAttributeValueSetID;
			}
		}
	}

	private function processOrder($oOrder)
	{
		$this->processOrderHead($oOrder->OrderHead);

		if( isset($oOrder->OrderItems->item) && is_array( $oOrder->OrderItems->item ) )
		{
			foreach( $oOrder->OrderItems->item AS $oitem)
			{
				$this->processOrderItem($oitem, $oOrder->OrderHead->OrderID);
			}
		}
		else if( isset($oOrder->OrderItems->item) )
		{
			$this->processOrderItem($oOrder->OrderItems->item, $oOrder->OrderHead->OrderID);
		}
	}

	private function responseInterpretation($oPlentySoapResponse_SearchOrders)
	{
		if( is_array( $oPlentySoapResponse_SearchOrders->Orders->item ) )
		{
			foreach( $oPlentySoapResponse_SearchOrders->Orders->item AS $order)
			{
				$this->processOrder($order);
			}
		}
		else
		{
			$this->processOrder($oPlentySoapResponse_SearchOrders->Orders->item, $AttributeValueSetIDs);
		}
		$this->getLogger()->debug(__FUNCTION__.' : done' );
	}

	private function processAttributeValueSetIDs()
	{
		$oPlentySoapRequest_GetAttributeValueSets = new Request_GetAttributeValueSets();

		$response		=	$this->getPlentySoap()->GetAttributeValueSets($oPlentySoapRequest_GetAttributeValueSets->getRequest($this->oAttributeValueSetIDs));

		if( $response->Success == true )
		{
			$this->getLogger()->debug(__FUNCTION__.' Request Success, '.
					count($response->AttributeValueSets->item)
					.' AttributeValueSets found');

			foreach ($response->AttributeValueSets->item as $currentAVS)
			{
				// store AttributeValueSets into DB
				$query = 'REPLACE INTO `AttributeValueSet` '.
						DBUtils::buildInsert(
								array(
										'AttributeValueSetID'					=>	$currentAVS->AttributeValueSetID,
										'AttributeValueSetFrontendName'				=>	$currentAVS->AttributeValueSetFrontendName,
										'AttributeValueSetBackendName'				=>	$currentAVS->AttributeValueSetBackendName
								)
						);

				DBQuery::getInstance()->replace($query);
			}
		}
		else
		{
			$this->getLogger()->debug(__FUNCTION__.' Request Error');
		}
	}
}

?>