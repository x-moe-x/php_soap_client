<?php



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

			#resultTable th {
				font-weight: normal;
				border: 1px solid black;
				padding-top: 1em;
			}
		</style>
	</head>
	<body>
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
		</table>
	</body>
</html>