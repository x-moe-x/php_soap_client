SET @KEYWORD = '%elox%' COLLATE 'utf8_unicode_ci';
SELECT
	i.ItemID
FROM
	ItemsBase AS i
WHERE
/*	Name LIKE @KEYWORD
OR
	Name2 LIKE @KEYWORD
OR
	Keywords LIKE @KEYWORD
OR*/
	LongDescription LIKE @KEYWORD
	OR
	MetaDescription LIKE @KEYWORD
	OR
	ShortDescription LIKE @KEYWORD
	OR
	TechnicalData LIKE @KEYWORD