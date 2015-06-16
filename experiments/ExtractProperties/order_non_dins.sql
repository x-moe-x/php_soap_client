SELECT
	x.Key,
	x.Value,
	x.ItemIDs
FROM
	(
		SELECT
			CASE WHEN (siblingData.SiblingRecordID IS NULL) THEN
				original.Key
			ELSE
				siblingData.Key
			END AS `Key`,
			CASE WHEN (siblingData.SiblingRecordID IS NULL) THEN
				original.Value
			WHEN (siblingData.SiblingType = 0) THEN
				siblingData.Value
			ELSE
				original.Value
			END AS `Value`,
			GROUP_CONCAT(original.ItemIDs SEPARATOR ",") AS ItemIDs,
			GROUP_CONCAT(original.RecordIDs SEPARATOR ",") AS RecordIDs,
			SUM(original.RecordCount) AS RecordCount
		FROM
			(
				SELECT
					p.`Key`,
					p.`Value`,
					CAST(GROUP_CONCAT(p.`ItemID` ORDER BY p.`ItemID` ASC SEPARATOR ",") AS CHAR) AS ItemIDs,
					CAST(GROUP_CONCAT(p.`RecordID` ORDER BY p.`RecordID` ASC SEPARATOR ",") AS CHAR) AS RecordIDs,
					MAX(s.SiblingRecordID) AS SiblingRecordID,
					s.`SiblingType`,
					COUNT(*) AS RecordCount
				FROM
					`ExtractedProperties` AS p
					JOIN
					`ItemsBase` AS i
						ON i.ItemID = p.ItemID
					LEFT JOIN
					`PropertySiblings` AS s
						ON
							p.RecordID = s.RecordID
				WHERE
					i.Inactive = 0
				GROUP BY
					`Key`,
					`Value`
			) AS original
			LEFT JOIN
			(
				SELECT
					s.SiblingRecordID,
					s.SiblingType,
					p.Key,
					p.Value
				FROM
					ExtractedProperties AS p
					JOIN
					PropertySiblings AS s
						ON p.RecordID = s.SiblingRecordID
				GROUP BY
					s.SiblingRecordID,
					s.SiblingType) AS siblingData
				ON original.SiblingRecordID = siblingData.SiblingRecordID AND
				   original.SiblingType = siblingData.SiblingType
		GROUP BY
			CASE WHEN (siblingData.SiblingRecordID IS NULL) THEN
				original.Key
			ELSE
				siblingData.Key
			END,
			CASE WHEN (siblingData.SiblingRecordID IS NULL) THEN
				original.Value
			WHEN (siblingData.SiblingType = 0) THEN
				siblingData.Value
			ELSE
				original.Value
			END
	) AS x
WHERE
	x.Key = 'Format / Maß'
	AND x.Value LIKE '%din%'
ORDER BY
	CAST(x.Value AS SIGNED INTEGER) DESC





