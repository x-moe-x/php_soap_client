SELECT
	SUM(CAST(OrderItem.Quantity AS SIGNED)) AS `quantity`,
	CAST(GROUP_CONCAT(CAST(OrderItem.Quantity AS SIGNED) ORDER BY OrderItem.Quantity DESC SEPARATOR ', ') AS CHAR(200)) AS `quantities`,
	FROM_UNIXTIME(OrderHead.OrderTimestamp, '%Y') AS `year`,
	FROM_UNIXTIME(OrderHead.OrderTimestamp, '%m') AS `month`,
	FROM_UNIXTIME(OrderHead.OrderTimestamp, '%d') AS `day`,
	COUNT(OrderHead.OrderID) AS `orders`,
	AVG(`quantity`) as `µ`,
	STDDEV(`quantity`) AS `σ`,
	(AVG(`quantity`) + STDDEV(`quantity`))*1.35 AS `range`,
	OrderItem.ItemID,
	ItemsBase.Marking1ID FROM OrderItem LEFT JOIN (OrderHead, ItemsBase) ON (OrderHead.OrderID = OrderItem.OrderID AND OrderItem.ItemID = ItemsBase.ItemID)
WHERE
	(OrderHead.OrderTimestamp BETWEEN 1364767200 AND 1367334415) AND
	(OrderHead.OrderStatus < 8 OR OrderHead.OrderStatus >= 9) AND
	OrderType = 'order' AND
	ItemsBase.Marking1ID IN (9,12,16,20) /* yellow, red, green, black */
GROUP BY
	`month`,
	OrderItem.ItemID
ORDER BY
	`quantity` DESC LIMIT 1000