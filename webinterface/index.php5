<?php

require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';

if (!(isset($_GET['pagenum']))) {
	$pagenum = 1;
} else {
	$pagenum = $_GET['pagenum'];
}

if (!(isset($_GET['pagerows']))) {
	$pagerows = 10;
} else {
	$pagerows = ($_GET['pagerows'] > 50 ? 50 : $_GET['pagerows']);
}

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
			$last = ceil(getMaxRows() / $pagerows);
			$page = getPageResult($pagenum, $pagerows);
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
echo "<ul id='paginationLinks'>";
echo "<li id='paginateFirst'>" . ($pagenum == 1 ? "<<-First" : "<a href='{$_SERVER['PHP_SELF']}?pagenum=1&pagerows=" . $pagerows . "'> <<-First</a>") . "</li>";
echo "<li id='paginatePrevious'>" . ($pagenum == 1 ? "<-Previous" : "<a href='{$_SERVER['PHP_SELF']}?pagenum=" . ($pagenum - 1) . "&pagerows=" . $pagerows . "'> <-Previous</a>") . "</li>";
echo "<li id='paginatePagenum'>" . $pagenum . "</li>";
echo "<li id='paginateNext'>" . ($pagenum == $last ? "Next ->" : "<a href='{$_SERVER['PHP_SELF']}?pagenum=" . ($pagenum + 1) . "&pagerows=" . $pagerows . "'>Next -></a>") . "</li>";
echo "<li id='paginateLast'>" . ($pagenum == $last ? "Last ->>" : "<a href='{$_SERVER['PHP_SELF']}?pagenum=" . $last . "&pagerows=" . $pagerows . "'>Last ->></a>") . "</li>";
echo "</ul>";
echo "<div id='paginationPagerows'>";
echo "<select onchange=\"window.location.href = '?pagenum=1&pagerows=' + this.options[this.selectedIndex].value\">";
echo "<option id='paginationPagerowsCaption'>Artikel / Seite</option><option value='10'>10</option><option value='20'>20</option><option value='50'>50</option>";
echo "</select>";
echo "</div>";
?>

</body>
</html>