<?php
ob_start();	// prevent verbose functions from tainting output

require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';

require ('smarty/libs/Smarty.class.php');
$smarty = new Smarty();

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

		// item id
		$preparedRow[] = $row['ItemID'];

		// name (& avsn)
		if (intval($row['AttributeValueSetID']) == 0) {
			$preparedRow[] = $row['Name'];
		} else {
			$preparedRow[] = $row['Name'] . ', ' . $row['AttributeValueSetName'];
		}

		// average need (per month)
		$preparedRow[] = null;

		// average need (per day)
		$preparedRow[] = null;

		// mark
		$preparedRow[] = $row['Marking1ID'];

		// suggested reorder level (old reorder level)
		$preparedRow[] = null;

		// minimum purchase / order suggestion (current order suggestion)
		$preparedRow[] = null;

		// change
		$preparedRow[] = null;

		// status reorder level
		$preparedRow[] = null;

		// date
		$preparedRow[] = null;

		$result[] = $preparedRow;
	}
	return $result;
}

function getConfig() {
	$query = 'SELECT
				* FROM `MetaConfig`
				WHERE
					`ConfigKey` = "CalculationTimeSingleWeighted" OR
					`ConfigKey` = "CalcualtionTimeDoubleWeighted" OR
					`ConfigKey` = "MinimumToleratedSpikes" OR
					`ConfigKey` = "SpikeTolerance" OR
					`ConfigKey` = "StandardDeviationFactor"';
	$resultConfigQuery = DBQuery::getInstance() -> select($query);

	$result = array();
	//TODO add validity check!
	for ($i = 0; $i < $resultConfigQuery -> getNumRows(); ++$i) {
		$configRow = $resultConfigQuery -> fetchAssoc();
		if ($configRow['ConfigKey'] == 'SpikeTolerance' || $configRow['ConfigKey'] == 'StandardDeviationFactor')
			$result[$configRow['ConfigKey']]['Value'] = floatval($configRow['ConfigValue']);
		else
			$result[$configRow['ConfigKey']]['Value'] = intval($configRow['ConfigValue']);

		$result[$configRow['ConfigKey']]['Active'] = intval($configRow['Active']);
	}
	return $result;
}

function getWarehouseList() {
	$query = 'SELECT * FROM `WarehouseList`';
	$resultWarehouseList = DBQuery::getInstance() -> select($query);

	$result = array();
	for ($i = 0; $i < $resultWarehouseList -> getNumRows(); ++$i) {
		$warehouse = $resultWarehouseList -> fetchAssoc();
		$result[] = array('id' => $warehouse['WarehouseID'], 'name' => $warehouse['Name']);
	}
	return $result;
}

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

$page = getPageResult($pagenum, $pagerows);

$smarty -> setTemplateDir('smarty/templates');
$smarty -> setCompileDir('smarty/templates_c');
$smarty -> setCacheDir('smarty/cache');
$smarty -> setConfigDir('smarty/configs');

$smarty -> assign('pagination', $pagination);

$smarty -> assign('pagenum', $pagenum);
$smarty -> assign('pagerows', $pagerows);
$smarty -> assign('last', ceil(getMaxRows() / $pagerows));
$smarty -> assign('rows', processPage($page));
$smarty -> assign('warehouseList', getWarehouseList());
$smarty -> assign('config', getConfig());
$smarty -> assign('debug', ob_get_clean());	// make function output available if needed
$smarty -> display('index.tpl');
?>
