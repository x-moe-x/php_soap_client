<?php

require_once ROOT . 'includes/FillObjectFromArray.php';

/**
 * Class Request_SearchOrders
 */
class Request_SearchOrders
{
	/**
	 * @param int $lastUpdate
	 * @param int $currentTime
	 * @param int $page
	 *
	 * @return PlentySoapRequest_SearchOrders
	 */
	public static function getRequest($lastUpdate, $currentTime, $page)
	{
		$oPlentySoapRequest_SearchOrders = new PlentySoapRequest_SearchOrders();

		fillObjectFromArray($oPlentySoapRequest_SearchOrders, array(
			'Page'                    => $page,
			'LastUpdateFrom'          => $lastUpdate,
			'LastUpdateTill'          => $currentTime,
			'MultishopID'             => 0,
			'GetOrderCustomerAddress' => false,
			'GetOrderDeliveryAddress' => false,
			'GetOrderInfo'            => false,
			'GetParcelService'        => false,
			'GetOrderDocumentNumbers' => false,
			'GetSalesOrderProperties' => false,
			'CustomerCountryID'       => null,
			'ExternalOrderID'         => null,
			'InvoiceNumber'           => null,
			'OrderCompletedFrom'      => null,
			'OrderCompletedTill'      => null,
			'OrderCreatedFrom'        => null,
			'OrderCreatedTill'        => null,
			'OrderID'                 => null,
			'OrderPaidFrom'           => null,
			'OrderPaidTill'           => null,
			'ReferrerID'              => null,
			'OrderStatus'             => null
		));

		return $oPlentySoapRequest_SearchOrders;
	}
}