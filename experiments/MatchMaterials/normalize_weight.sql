INSERT INTO `SetPropertiesToItem` (`ItemId`, `PropertyId`, `PropertyItemValue`)
SELECT
	x.ItemID,
	361,/*
	CASE WHEN (x.Weight < 1 AND x.WeightUnit = 'kg') THEN
		x.Weight * 1000
	ELSE
		x.Weight
	END AS Weight,*/
	CASE WHEN (x.Weight < 1 AND x.WeightUnit = 'kg') THEN
		21
	ELSE 17 END AS WeightUnit
FROM
	(
		SELECT
			*,
			CASE WHEN (`Value` LIKE 'ca %' AND `Value` LIKE '% kg') THEN
				CAST(REPLACE(REPLACE(REPLACE(`Value`, 'ca ', ''), ' kg', ''), ',', '.') AS DECIMAL(10, 2))
			WHEN (`Value` LIKE 'ca. %' AND `Value` LIKE '% kg') THEN
				CAST(REPLACE(REPLACE(REPLACE(`Value`, 'ca. ', ''), ' kg', ''), ',', '.') AS DECIMAL(10, 2))
			WHEN (`Value` LIKE '% kg') THEN
				CAST(REPLACE(REPLACE(`Value`, ' kg', ''), ',', '.') AS DECIMAL(10, 2))
			WHEN (`Value` LIKE '% g/qm') THEN
				CAST(REPLACE(REPLACE(`Value`, ' g/qm', ''), ',', '.') AS DECIMAL(10, 2))
			WHEN (`Value` LIKE '%g/m²') THEN
				CAST(REPLACE(REPLACE(`Value`, 'g/m²', ''), ',', '.') AS DECIMAL(10, 2))
			WHEN (`Value` LIKE '%g/qm') THEN
				CAST(REPLACE(REPLACE(`Value`, 'g/qm', ''), ',', '.') AS DECIMAL(10, 2))
			END AS Weight,
			CASE WHEN (`Value` LIKE '%kg') THEN
				'kg'
			WHEN (`Value` LIKE '%g/qm') THEN
				'g/qm'
			WHEN (`Value` LIKE '%g/m²') THEN
				'g/qm'
			END AS WeightUnit,
			CASE WHEN (`Value` LIKE 'ca%') THEN
				1
			ELSE
				0
			END AS Approximate

		FROM
			`ExtractedProperties`
		WHERE
			`Key` = 'Gewicht' AND `Value` NOT LIKE '%ca%' AND `Value` NOT LIKE '%g/%'
		GROUP BY
			ItemID
	) AS x
	JOIN
	PropertyChoices AS p
		ON CASE WHEN (x.Weight < 1 AND x.WeightUnit = 'kg') THEN
		'g'
		   ELSE x.WeightUnit END = p.Name