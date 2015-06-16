INSERT INTO SetPropertiesToItem (id, ItemId, PropertyId)
	SELECT
		x.id,
		x.ItemId,
		308 AS PropertyId
	FROM
		(
			SELECT
				NULL AS id,
				i.ItemID AS ItemId,
				p.PropertyID AS PropertyId,
				GROUP_CONCAT(CAST(p.PropertyGroupID AS CHAR)) AS PropertyGroupIds,
				GROUP_CONCAT(p.PropertyName) AS PropertyNames,
				count(*) AS Counter
			FROM
				ItemsBase AS i
				JOIN
				ItemsCategories AS c
					ON i.ItemID = c.ItemID
				LEFT JOIN
				ItemsProperties AS p
					ON i.ItemID = p.ItemID
			WHERE
				(c.ItemCategoryPath LIKE '%354%' OR c.ItemCategoryPath LIKE '%292%')
			GROUP BY
				i.ItemID) AS x
	WHERE
		x.PropertyGroupIds NOT LIKE '%10%'
