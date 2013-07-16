<?php

require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';

require ('smarty/libs/Smarty.class.php');
$smarty = new Smarty();

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

function processPage(DBQueryResult $resultPage) {
	$result = array( array("Art.ID", "Name", "durchschnittlicher Bedarf (Monat)", "durchschnittlicher Bedarf (Tag)", "Markierung", "Empfehlung Meldebestand (Meldebestand alt)", "Mindesabnahme / Bestellvorschlag (Bestellvorschlag aktuell)", "Änderung", "Status Meldebestand", "Datum"));
	for ($i = 0; $i < $resultPage -> getNumRows(); ++$i) {
		$row = $resultPage -> fetchAssoc();
		$preparedRow = array();

		$preparedRow[] = $row['ItemID'];
		if (intval($row['AttributeValueSetID']) == 0) {
			$preparedRow[] = $row['Name'];
		} else {
			$preparedRow[] = $row['Name'] . ', ' . $row['AttributeValueSetName'];
		}
		$preparedRow[] = null;
		$preparedRow[] = null;
		//$rowString .= getCol('●', 'markingColumn marking' . $row['Marking1ID']);
		$preparedRow[] = null;
		$preparedRow[] = null;
		$preparedRow[] = null;
		$preparedRow[] = null;
		$preparedRow[] = null;
		$preparedRow[] = null;

		$result[] = $preparedRow;
	}
	return $result;
}

$page = getPageResult($pagenum, $pagerows);

$pagination = "<ul id='paginationLinks'>";
$pagination .= "<li id='paginateFirst'>" . ($pagenum == 1 ? "<<-First" : "<a href='{$_SERVER['PHP_SELF']}?pagenum=1&pagerows=" . $pagerows . "'> <<-First</a>") . "</li>";
$pagination .= "<li id='paginatePrevious'>" . ($pagenum == 1 ? "<-Previous" : "<a href='{$_SERVER['PHP_SELF']}?pagenum=" . ($pagenum - 1) . "&pagerows=" . $pagerows . "'> <-Previous</a>") . "</li>";
$pagination .= "<li id='paginatePagenum'>" . $pagenum . "</li>";
$pagination .= "<li id='paginateNext'>" . ($pagenum == $last ? "Next ->" : "<a href='{$_SERVER['PHP_SELF']}?pagenum=" . ($pagenum + 1) . "&pagerows=" . $pagerows . "'>Next -></a>") . "</li>";
$pagination .= "<li id='paginateLast'>" . ($pagenum == $last ? "Last ->>" : "<a href='{$_SERVER['PHP_SELF']}?pagenum=" . $last . "&pagerows=" . $pagerows . "'>Last ->></a>") . "</li>";
$pagination .= "</ul>";
$pagination .= "<div id='paginationPagerows'>";
$pagination .= "<select onchange=\"window.location.href = '?pagenum=1&pagerows=' + this.options[this.selectedIndex].value\">";
$pagination .= "<option id='paginationPagerowsCaption'>Artikel / Seite</option><option value='10'>10</option><option value='20'>20</option><option value='50'>50</option>";
$pagination .= "</select>";
$pagination .= "</div>";

$smarty -> setTemplateDir('smarty/templates');
$smarty -> setCompileDir('smarty/templates_c');
$smarty -> setCacheDir('smarty/cache');
$smarty -> setConfigDir('smarty/configs');

$smarty -> assign('pagination', $pagination);

$smarty -> assign('pagenum', $pagenum);
$smarty -> assign('pagerows', $pagerows);
$smarty -> assign('last', ceil(getMaxRows() / $pagerows));
$smarty -> assign('rows', processPage($page));
$smarty -> display('index.tpl');
?>