<?php

require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';

function getArticles() {
	$query = 'SELECT
				ItemsBase.ItemID,
				ItemsBase.ItemNo,
				ItemsBase.Name,
				ItemsBase.Marking1ID,
				CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
					"0"
				ELSE
					AttributeValueSets.AttributeValueSetID
				END AttributeValueSetID,
				CASE WHEN (AttributeValueSets.AttributeValueSetName IS null) THEN
					""
				ELSE
					AttributeValueSets.AttributeValueSetName
				END AttributeValueSetName
				FROM ItemsBase
				LEFT JOIN AttributeValueSets
					ON ItemsBase.ItemID = AttributeValueSets.ItemID
				LIMIT 25';

	$result = DBQuery::getInstance() -> select($query);
	return $result;
}

function getCol($data) {
	return "<td>" . $data . "</td>";
}

function getRows(DBQueryResult $queryResult) {
	for ($i = 0; $i < $queryResult -> getNumRows(); ++$i) {
		$row = $queryResult -> fetchAssoc();

		$rowString = "<tr class='articleTable" . ($i % 2 == 0 ? "Even" : "Odd") . "'>";
		$rowString .= getCol($row['ItemID']);
		if (intval($row['AttributeValueSetID']) == 0) {
			$rowString .= getCol($row['Name']);
		} else {
			$rowString .= getCol($row['Name'] . ", " . $row['AttributeValueSetName']);
		}
		$rowString .= getCol("");
		$rowString .= getCol("");
		$rowString .= getCol($row['Marking1ID']);
		$rowString .= getCol("");
		$rowString .= getCol("");
		$rowString .= getCol("");
		$rowString .= getCol("");
		$rowString .= getCol("");
		$rowString .= "</tr>\n";

		echo $rowString;
	}

}
?>
<!DOCTYPE HTML>
<html>
	<head>
		<meta charset="utf-8">
		<title>Net-Xpress, Plenty-Soap GUI</title>
		<style>
			* {
				font-family: Arial;
				font-size: 10pt;
			}

			#filterSelection {
				background-color: #ff950e;
			}

			#resultTable {
				border: 1px solid black;
				border-collapse: collapse;
				width: 100%;
			}

			#resultTable td {
				border-left: 1px solid #ccc;
			}

			#resultTable .articleTableEven {
				background-color: #eee;
			}

			#resultTable .articleTableOdd {
				background-color: #fff;
			}

			#resultTable th {
				font-weight: normal;
				border: 1px solid black;
				padding-top: 1em;
			}

			#errorMessages {
				background-color: #00ff00;
			}
		</style>
	</head>
	<body>
		<div id="errorMessages">
			<?php
			$result = getArticles();
			?>
		</div>
		<ul>
			<li>
				<label for='calculationTimeSingleWeighted'> Zeitraum zur Berechnung (einfach gewichtet): </label>
				<input id='calculationTimeSingleWeighted' />
			<li>
				<label for='calcualtionTimeDoubleWeighted'> Zeitraum zur Berechnung (doppelt gewichtet): </label>
				<input id='calcualtionTimeDoubleWeighted'/>
			<li>
				<label for='standardDeviationFaktor'> Faktor Standardabweichung: </label>
				<input id='standardDeviationFaktor'/>
		</ul>

		<div id='filterSelection'>
			Filter: Alle anzeigen
		</div>
		<table id='resultTable'>
			<tr>
				<th>Art.ID</th>
				<th>Name</th>
				<th>durchschnittlicher Bedarf (Monat)</th>
				<th>durchschnittlicher Bedarf (Tag)</th>
				<th>Markierung</th>
				<th>Empfehlung Meldebestand (Meldebestand alt)</th>
				<th>Mindesabnahme / Bestellvorschlag (Bestellvorschlag aktuell)</th>
				<th>Änderung</th>
				<th>Status Meldebestand </th>
				<th>Datum</th>
			</tr>
			<?php
			getRows($result);
			?>
		</table>
	</body>
</html>