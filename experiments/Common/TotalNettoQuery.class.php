<?php

/**
 * Class TotalNettoQuery
 */
class TotalNettoQuery
{
	/**
	 * @var int
	 */
	const DEFAULT_NR_OF_MONTHS_BACKWARDS = 6;

	/**
	 * @var string
	 */
	const CONSIDERED_TIMESTAMP = 'DoneTimestamp';

	/**
	 * for all OrderItems that are:
	 * - from orders in a certain status ( 7 <= status < 8 OR 9 <= status)
	 * - from orders with type 'order'
	 * - from orders of interval [$startAt - $duringBackwardsInterval, $startAt]
	 * - [optionally] from a specific referrer
	 * do:
	 * sum up their netto value and group them by (date, warehouse)
	 *
	 * @param DateTime     $startAt
	 * @param DateInterval $duringBackwardsInterval
	 * @param int          $referrerId
	 *
	 * @return string query
	 */
	public static function getPerWarehouseNettoQuery(DateTime $startAt, DateInterval $duringBackwardsInterval = null, $referrerId = null)
	{
		return "SELECT
	DATE_FORMAT(FROM_UNIXTIME(OrderHead." . self::CONSIDERED_TIMESTAMP . "), '%Y%m01') AS `Date`,
	OrderItem.WarehouseID,
	SUM(OrderItem.Price * OrderItem.Quantity / (1 + OrderItem.VAT / 100)) AS `PerWarehouseNetto`
FROM
	OrderItem
LEFT JOIN
	OrderHead
ON
	OrderItem.OrderID = OrderHead.OrderID
WHERE
	" . self::getConditionQueryPart(clone $startAt, $duringBackwardsInterval, $referrerId) . "
GROUP BY
	`Date`,
	WarehouseID\n";
	}

	/**
	 * @param DateTime     $internalStartAt
	 * @param DateInterval $duringBackwardsInterval
	 * @param null         $referrerId
	 *
	 * @return string
	 */
	private static function getConditionQueryPart(DateTime $internalStartAt, DateInterval $duringBackwardsInterval = null, $referrerId = null)
	{
		if (is_null($duringBackwardsInterval))
		{
			$duringBackwardsInterval = new DateInterval('P' . self::DEFAULT_NR_OF_MONTHS_BACKWARDS . 'M');
		}

		$toDate = $internalStartAt->format('\'Y-m-d\'');
		$fromDate = $internalStartAt->sub($duringBackwardsInterval)->format('\'Y-m-d\'');

		$referrerCondition = is_null($referrerId) ? '1' : "(OrderHead.ReferrerID = $referrerId)";

		return "$referrerCondition
AND
	((OrderHead.OrderStatus >= 7 AND OrderHead.OrderStatus < 8) OR OrderHead.OrderStatus >= 9)
AND
	OrderHead.OrderType = 'order'
AND
	FROM_UNIXTIME(OrderHead." . self::CONSIDERED_TIMESTAMP . ") BETWEEN $fromDate AND $toDate";
	}

	/**
	 * for all Orders that are:
	 * - in a certain status ( 7 <= status < 8 OR 9 <= status)
	 * - of type 'order'
	 * - of interval [$startAt - $duringBackwardsInterval, $startAt]
	 * - [optionally] from a specific referrer
	 * do:
	 * sum up their total netto value as well as total netto shipment costs and group them by date
	 *
	 * @param DateTime     $startAt
	 * @param DateInterval $duringBackwardsInterval
	 * @param int          $referrerId
	 *
	 * @return string query
	 */
	public static function getTotalNettoAndShippingCostsQuery(DateTime $startAt, DateInterval $duringBackwardsInterval = null, $referrerId = null)
	{

		return "SELECT
	DATE_FORMAT(FROM_UNIXTIME(OrderHead." . self::CONSIDERED_TIMESTAMP . "), '%Y%m01') AS `Date`,
	SUM(TotalNetto) AS `TotalNetto`,
	SUM(TotalInvoice - (TotalVAT + TotalNetto)) AS `TotalShippingNetto`
FROM
	OrderHead
WHERE
	" . self::getConditionQueryPart(clone $startAt, $duringBackwardsInterval, $referrerId) . "
GROUP BY
	`Date`\n";
	}

}
