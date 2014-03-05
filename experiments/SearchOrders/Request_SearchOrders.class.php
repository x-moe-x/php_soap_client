<?php

require_once ROOT . 'includes/FillObjectFromArray.php';

class Request_SearchOrders {

	public function getRequest($lastUpdate, $currentTime, $page) {
		$oPlentySoapRequest_SearchOrders = new PlentySoapRequest_SearchOrders();

		// @formatter:off
		fillObjectFromArray($oPlentySoapRequest_SearchOrders, array(
			'Page' =>						$page,
			'LastUpdateFrom' =>				$lastUpdate,
			'LastUpdateTill' =>				$currentTime,
			'MultishopID' =>				0,
			'GetOrderCustomerAddress' =>	false,
			'GetOrderDeliveryAddress' =>	false,
			'GetOrderInfo' =>				false,
			'GetParcelService' =>			false,
			'GetOrderDocumentNumbers' =>	false,
			'GetSalesOrderProperties' =>	false,
			'CustomerCountryID' =>			null,
			'ExternalOrderID' =>			null,
			'InvoiceNumber' =>				null,
			'OrderCompletedFrom' =>			null,
			'OrderCompletedTill' =>			null,
			'OrderCreatedFrom' =>			null,
			'OrderCreatedTill' =>			null,
			'OrderID' =>					null,
			'OrderPaidFrom' =>				null,
			'OrderPaidTill' =>				null,
			'ReferrerID' =>					null,
			'OrderStatus' =>				null
		));
		// @formatter:on

		return $oPlentySoapRequest_SearchOrders;
	}

}
?>