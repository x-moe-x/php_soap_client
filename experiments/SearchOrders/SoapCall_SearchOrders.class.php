<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_SearchOrders.class.php';
require_once ROOT . 'includes/DBLastUpdate.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/*
 * there's a plenty bug when an order contains some non-utf-8 characters, soap will refuse to respond for the whole package of 25 orders
 * in order to work unattended we will workaroud this bug. code regarding this workaround is marked with /* workaround */

class SoapCall_SearchOrders extends PlentySoapCall {

	/**
	 * @var int
	 */
	private $page = 0;

	/**
	 * @var int
	 */
	private $pages = -1;

	/**
	 * @var int
	 */
	private $startAtPage = 0;

	/**
	 * @var PlentySoapRequest_SearchOrders
	 */
	private $oPlentySoapRequest_SearchOrders;

	/**
	 * @var int
	 */
	private $lastSavedPage = 0;

	/**
	 * @var int
	 */
	private static $SAVE_AFTER_PAGES = 100;

	/**
	 * @var array
	 */
	private $aOrderHeads;

	/**
	 * @var array
	 */
	private $aOrderItems;

	/* workaround */
	/**
	 * @var int
	 */
	private $lastOrderID = -1;

	/**
	 * @var int
	 */
	private $caughtUTF8Error = false;

	/**
	 * @return SoapCall_SearchOrders
	 */
	public function __construct() {
		parent::__construct(__CLASS__);
		$this -> aOrderHeads = array();
		$this -> aOrderItems = array();
	}

	public function execute() {
		$this -> getLogger() -> debug(__FUNCTION__);

		list($lastUpdate, $currentTime, $this -> startAtPage) = lastUpdateStart(__CLASS__);

		if ($this -> pages == -1) {
			try {
				$oRequest_SearchOrders = new Request_SearchOrders();

				$this -> oPlentySoapRequest_SearchOrders = $oRequest_SearchOrders -> getRequest($lastUpdate, $currentTime, $this -> startAtPage);

				if ($this -> startAtPage > 0) {
					$this -> getLogger() -> debug(__FUNCTION__ . " Starting at page " . $this -> startAtPage);
				}

				/*
				 * do soap call
				 */
				$oPlentySoapResponse_SearchOrders = $this -> getPlentySoap() -> SearchOrders($this -> oPlentySoapRequest_SearchOrders);

				if (($oPlentySoapResponse_SearchOrders -> Success == true) && isset($oPlentySoapResponse_SearchOrders -> Orders -> item)) {
					$ordersFound = count($oPlentySoapResponse_SearchOrders -> Orders -> item);
					$pagesFound = $oPlentySoapResponse_SearchOrders -> Pages;

					//$this -> getLogger() -> debug(__FUNCTION__ . ' Request Success - orders found : ' . $ordersFound . ' / pages : ' . $pagesFound);

					// auswerten
					$this -> responseInterpretation($oPlentySoapResponse_SearchOrders);

					if ($pagesFound > $this -> page) {
						$this -> page = $this -> startAtPage + 1;
						$this -> pages = $pagesFound;

						lastUpdatePageUpdate(__CLASS__, $this -> page);
						$this -> executePages();
					}

				} else if (($oPlentySoapResponse_SearchOrders -> Success == true) && !isset($oPlentySoapResponse_SearchOrders -> Orders -> item)) {
					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Success - but no matching orders found');
				} else {
					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Error');
				}
			} catch(Exception $e) {
				$this -> onExceptionAction($e);
			}
		} else {
			$this -> executePages();
		}

		$this -> storeToDB();
		lastUpdateFinish($currentTime, __CLASS__);
	}

	private function storeToDB() {
		// store orders to db
		$countOrderHeads = count($this -> aOrderHeads);
		$countOrderItems = count($this -> aOrderItems);

		if ($countOrderHeads > 0) {

			$this -> getLogger() -> info(__FUNCTION__ . " : storing $countOrderHeads order head and $countOrderItems order item records. Progress: {$this -> page} / {$this -> pages}");

			DBQuery::getInstance() -> insert('INSERT INTO `OrderHead`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this -> aOrderHeads));

			// delete old OrderItems to prevent duplicate insertion
			DBQuery::getInstance() -> delete('DELETE FROM `OrderItem` WHERE `OrderID` IN (\'' . implode('\',\'', array_keys($this -> aOrderHeads)) . '\')');

			DBQuery::getInstance() -> insert('INSERT INTO `OrderItem`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this -> aOrderItems));

			$this -> aOrderHeads = array();
			$this -> aOrderItems = array();
		}
	}

	/* workaround */
	private function proceedAfterUTF8Error() {
		$this -> getLogger() -> debug(__FUNCTION__ . ' Caught UTF-8 Error on page : ' . $this -> page . ', last known working OrderID: ' . $this -> lastOrderID . ', skipping to next page');

		// remember we caught an UTF8-Error ...
		$this -> caughtUTF8Error = true;

		// ... then skip to the next page
		$this -> page++;
	}

	public function executePages() {
		while ($this -> pages > $this -> page) {
			$this -> oPlentySoapRequest_SearchOrders -> Page = $this -> page;
			try {
				$oPlentySoapResponse_SearchOrders = $this -> getPlentySoap() -> SearchOrders($this -> oPlentySoapRequest_SearchOrders);

				if ($oPlentySoapResponse_SearchOrders -> Success == true) {
					$ordersFound = count($oPlentySoapResponse_SearchOrders -> Orders -> item);
					//$this -> getLogger() -> debug(__FUNCTION__ . ' Request Success - orders found : ' . $ordersFound . ' / page : ' . $this -> page);

					// auswerten
					$this -> responseInterpretation($oPlentySoapResponse_SearchOrders);
				}

				$this -> page++;
				lastUpdatePageUpdate(__CLASS__, $this -> page);

			} catch(Exception $e) {
				$this -> onExceptionAction($e);
			}

			if ($this -> page - $this -> lastSavedPage > self::$SAVE_AFTER_PAGES) {
				$this -> storeToDB();
				$this -> lastSavedPage = $this -> page;
			}
		}
	}

	public function onExceptionAction(Exception $e) {
		/* workaround */
		if (is_soap_fault($e) && $e -> faultcode === 'SOAP-ENV:Server') {
			// assume it's an utf-8 error
			$this -> proceedAfterUTF8Error();
		} else {
			$this -> storeToDB();
			parent::onExceptionAction($e);
		}
	}

	private function processOrderHead($oOrderHead) {

		/* workaround */
		if ($this -> caughtUTF8Error) {
			// store failed range to fail db ...
			$query = 'REPLACE INTO `FailedOrderIDRange`' . DBUtils::buildInsert(array('FromOrderID' => $this -> lastOrderID + 1, 'CountOrders' => intval($oOrderHead -> OrderID) - ($this -> lastOrderID + 1), 'Reason' => 'UTF-8 error'));
			DBQuery::getInstance() -> replace($query);

			$this -> getLogger() -> debug(__FUNCTION__ . ' Stored failed OrderID-Range from ' . ($this -> lastOrderID + 1) . ' for ' . (intval($oOrderHead -> OrderID) - ($this -> lastOrderID + 1)) . ' orders, next working OrderID: ' . intval($oOrderHead -> OrderID));

			// ... and carry on in normal mode
			$this -> caughtUTF8Error = false;
		}

		$this -> lastOrderID = intval($oOrderHead -> OrderID);

		// @formatter:off
		$this -> aOrderHeads[$oOrderHead->OrderID] = array(
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
		// TODO rename in db shceme
			'MultishopID'				=>	$oOrderHead->StoreID,
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
		);
        // @formatter:on
	}

	private function processOrderItem($oOrderItem, $oOrderID) {
		// @formatter:off
		$this->aOrderItems[] = 	array(
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
		);
        // @formatter:on
	}

	/**
	 * @var PlentySoapObject_SearchOrders $oPlentySoapObject_SearchOrders
	 */
	private function processOrder($oPlentySoapObject_SearchOrders) {
		$this -> processOrderHead($oPlentySoapObject_SearchOrders -> OrderHead);

		if (isset($oPlentySoapObject_SearchOrders -> OrderItems -> item) && is_array($oPlentySoapObject_SearchOrders -> OrderItems -> item)) {
			foreach ($oPlentySoapObject_SearchOrders->OrderItems->item AS $oitem) {
				$this -> processOrderItem($oitem, $oPlentySoapObject_SearchOrders -> OrderHead -> OrderID);
			}
		} else if (isset($oPlentySoapObject_SearchOrders -> OrderItems -> item)) {
			$this -> processOrderItem($oPlentySoapObject_SearchOrders -> OrderItems -> item, $oPlentySoapObject_SearchOrders -> OrderHead -> OrderID);
		}
	}

	private function responseInterpretation(PlentySoapResponse_SearchOrders $oPlentySoapResponse_SearchOrders) {
		if (is_array($oPlentySoapResponse_SearchOrders -> Orders -> item)) {
			foreach ($oPlentySoapResponse_SearchOrders->Orders->item AS $order) {
				$this -> processOrder($order);
			}
		} else {
			$this -> processOrder($oPlentySoapResponse_SearchOrders -> Orders -> item, $AttributeValueSetIDs);
		}
		// $this->getLogger()->debug(__FUNCTION__.' : done' );
	}

}
?>