SET @COLOR_ALU = 327, @COLOR_TRANSPARENT = 328, @COLOR_ANDERE = 329, @COLOR_BLACK = 330, @COLOR_BLUE = 331, @COLOR_WHITE = 332, @COLOR_RED = 333, @COLOR_GOLD = 334, @B1_WITH = 340, @B1_WITHOUT = 341;
INSERT INTO /*RemovePropertyFromItem*/SetPropertiesToItem (id, ItemId, PropertyId)
	SELECT
		NULL AS id,
		x.ItemID AS ItemId,
		@COLOR_WHITE AS PropertyId
	FROM
		(
			SELECT
				i.ItemID,
				i.Name,
				SUBSTRING(
					SUBSTRING_INDEX(c.ItemCategoryPath, ';', 1),
					LENGTH(SUBSTRING_INDEX(c.ItemCategoryPath, ';', 0)) + 1
				) AS FirstCategory,
				SUBSTRING(
					SUBSTRING_INDEX(c.ItemCategoryPathNames, ';', 1),
					LENGTH(SUBSTRING_INDEX(c.ItemCategoryPathNames, ';', 0)) + 1
				) AS FirstCategoryName
			FROM
				ItemsBase AS i
				JOIN
				ItemsCategories AS c
					ON i.ItemID = c.ItemID
			WHERE
				c.ItemCategoryID NOT IN (0, 328)
			GROUP BY
				i.ItemID,
				FirstCategory
		) AS x
	WHERE
		x.FirstCategory IN (321)