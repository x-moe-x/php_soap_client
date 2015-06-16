SELECT
	i.ItemID,
	i.Name,
	pp.Key,
	CASE WHEN (s.SiblingType IS NULL OR s.SiblingType != 0) THEN
		p.Value
	ELSE
		pp.Value
	END AS `Value`,
	p.Key,
	p.Value,
	s.SiblingType,
	p.RecordID
FROM
	ItemsBase AS i
	JOIN
	ExtractedProperties AS p
		ON
			i.ItemID = p.ItemID
	LEFT JOIN
	PropertySiblings AS s
		ON
			p.RecordID = s.RecordID
	LEFT JOIN
	ExtractedProperties AS pp
		ON
			s.SiblingRecordID = pp.RecordID
WHERE
	i.inactive = 0

	AND p.Key = 'Bannerbreite'


