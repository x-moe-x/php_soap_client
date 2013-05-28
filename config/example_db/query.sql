SELECT
	OrderItem.ItemID,
	SUM(CAST(OrderItem.Quantity AS SIGNED)) AS `quantity`,
	AVG(`quantity`) + STDDEV(`quantity`)*1.35 AS `range`,
	CAST(GROUP_CONCAT(IF(OrderItem.Quantity > 0 ,CAST(OrderItem.Quantity AS SIGNED),NULL) ORDER BY OrderItem.Quantity DESC SEPARATOR ",") AS CHAR) AS `quantities`,
	ItemsBase.Marking1ID FROM OrderItem LEFT JOIN (OrderHead, ItemsBase) ON (OrderHead.OrderID = OrderItem.OrderID AND OrderItem.ItemID = ItemsBase.ItemID)
WHERE
	(OrderHead.OrderTimestamp BETWEEN 1367359199-(60*60*24*90) AND 1367359199) AND
	(OrderHead.OrderStatus < 8 OR OrderHead.OrderStatus >= 9) AND
	OrderType = "order" AND
	ItemsBase.Marking1ID IN (9,12,16,20) /* yellow, red, green, black */
GROUP BY
	OrderItem.ItemID
ORDER BY
	ItemID LIMIT 2000