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

	AND
	p.Key NOT IN
	('Gewicht', 'Sichtmaß', 'Rahmenbreite', 'Gesamthöhe', 'Gesamhöhe', 'Grundfläche', 'Gesammthöhe', 'Aufstellbreite', 'Rahmengröße', 'Fülltiefe', 'Innenbreite', 'Gesamtbreite', 'Gesamttiefe', 'Länge', 'Lieferumfang', 'Logoplatte, Format', 'Licht', 'Leuchtstoffröhre', 'Bodensteinformat', 'Fächeranzahl', 'Höhe', 'Logoplatte', 'Breite', 'Tiefe', 'Füllbreite', 'Ständerhöhe', 'Papierausrichtung', 'Fülltiefe je Fach', 'Fachinnenbreite', 'Standprofilhöhe', 'Standfläche', 'Material', 'Stärke', 'Brandschutzklasse', 'Abmessungen', 'Öffnungsrichtung', 'Farbe', 'Anzahl', 'Durchmesser', 'Bannerbreite', 'Profillänge', 'Leistungsaufnahme', 'Sichtmaß Hauptrahmen', 'Rahmengröße Toprahmen', 'Tafeloberfläche', 'Rahmenmaß', 'Außenmaß', 'Durchmesser Fußplatte', 'Kordellänge', 'Standhöhe', 'Rahmenmaß', '	Logoplattenhöhe', 'Grundfläche', 'Logoplattenhöhe', 'Abmessungen der Logoplatte', 'Rahmenfarbton', 'Bannergewicht', 'Bannerstärke', 'Brandschutzklasse (Banner)', 'Durchmesser Ständer', 'Gurtlänge', 'Materialstärke', 'Gebindegröße', 'Maße Bodenplatte', 'Profilmaß Ständeranlage', 'Höhe Ständeranlage', 'Fußdurchmesser', 'Logoplattenformat', 'Acrylglasstärke', 'Durchmesser Abstandhalter', 'Anforderungen an Trägerplatte', 'Wandabstand', 'Sichtmaß Toprahmen')


