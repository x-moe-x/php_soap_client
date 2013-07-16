<?php

require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';

if (!(isset($_GET['pagenum']))) {
	$pagenum = 1;
} else {
	$pagenum = $_GET['pagenum'];
}

define('PAGE_ROWS', 10);

function getQuery() {
	return 'SELECT
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
					ON ItemsBase.ItemID = AttributeValueSets.ItemID';
}

function getMaxRows() {
	return DBQuery::getInstance() -> select(getQuery()) -> getNumRows();
}

function getPageResult($pageNum, $pageRows) {
	$query = getQuery() . '
				LIMIT ' . ($pageNum - 1) * $pageRows . ',' . $pageRows;

	$result = DBQuery::getInstance() -> select($query);
	return $result;
}

function getCol($data, $class = null) {
	return '<td' . ($class != null ? ' class=\'' . $class . '\'' : '') . '>' . $data . '</td>';
}

function processPage(DBQueryResult $resultPage) {
	for ($i = 0; $i < $resultPage -> getNumRows(); ++$i) {
		$row = $resultPage -> fetchAssoc();

		$rowString = '<tr class=\'articleTable' . ($i % 2 == 0 ? 'Even' : 'Odd') . '\'>';
		$rowString .= getCol($row['ItemID']);
		if (intval($row['AttributeValueSetID']) == 0) {
			$rowString .= getCol($row['Name']);
		} else {
			$rowString .= getCol($row['Name'] . ', ' . $row['AttributeValueSetName']);
		}
		$rowString .= getCol('');
		$rowString .= getCol('');
		$rowString .= getCol('●', 'markingColumn marking' . $row['Marking1ID']);
		$rowString .= getCol('');
		$rowString .= getCol('');
		$rowString .= getCol('');
		$rowString .= getCol('');
		$rowString .= getCol('');
		$rowString .= '</tr>';

		echo $rowString;
	}

}
?>
<!DOCTYPE HTML>
<html>
	<head>
		<meta charset="utf-8">
		<title>Net-Xpress, Plenty-Soap GUI</title>
		<link rel="stylesheet" type="text/css" href="style.css"/>
	</head>
	<body>
		<div id="errorMessages">
			<?php
$last = ceil(getMaxRows()/PAGE_ROWS);
$page = getPageResult($pagenum, PAGE_ROWS);
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
processPage($page);
?>
</table>
<?php
if ($pagenum == 1)
	;
else {
	echo " <a href='{$_SERVER['PHP_SELF']}?pagenum=1'> <<-First</a> ";
	echo " ";
	$previous = $pagenum - 1;
	echo " <a href='{$_SERVER['PHP_SELF']}?pagenum=$previous'> <-Previous</a> ";
}

//just a spacer
echo " " . $pagenum . " ";

if ($pagenum == $last)
	;
else {
	$next = $pagenum + 1;
	echo " <a href='{$_SERVER['PHP_SELF']}?pagenum=$next'>Next -></a> ";
	echo " ";
	echo " <a href='{$_SERVER['PHP_SELF']}?pagenum=$last'>Last ->></a> ";
}
?>
</body>
</html>