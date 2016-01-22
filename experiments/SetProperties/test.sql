INSERT INTO `db473835270`.`SetPropertiesToItem` (`id`, `ItemId`, `Lang`, `PropertyId`) VALUES (null, 780, 'de', 357),
	(null, 781, 'de', 357),
	(null, 777, 'de', 357),
	(null, 782, 'de', 357),
	(null, 778, 'de', 357),
	(null, 779, 'de', 357);

SELECT
		NULL AS id,
		x.ItemID as ItemId,
		'de' as Lang,
		p.PropertyId

	FROM
		(
			SELECT
				ep.ItemID,
				SUBSTRING(ep.Value, 1 + POSITION('(' IN ep.Value),
						  POSITION(')' IN ep.Value) - POSITION('(' IN ep.Value) - 1) AS Aufteilung
			FROM
				ExtractedProperties ep JOIN
				ItemsCategories AS c
					ON ep.ItemID = c.ItemID
			WHERE
				c.ItemCategoryPath LIKE '305%' AND ep.Key != 'Gewicht' AND ep.Key NOT LIKE 'höhe%' AND
				ep.Key NOT LIKE 'leistung%' AND ep.Key NOT LIKE 'logoplatten%' AND ep.Key NOT LIKE '%bodenplatte%' AND
				ep.Key NOT LIKE 'öffnung%' AND ep.Key NOT LIKE 'profilmaß%' AND ep.Key NOT LIKE 'Rahmen%' AND
				ep.Key NOT LIKE 'Sichtmaß%' AND ep.ItemID NOT IN (780, 781, 777, 782, 778, 779)) AS x
		JOIN
		Properties AS p
			ON
				CONCAT(SUBSTR(x.Aufteilung, 1, 1), ' x ', SUBSTR(x.Aufteilung, 3, 1)) = p.PropertyFrontendName