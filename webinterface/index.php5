<?php
ob_start();	// prevent verbose functions from tainting output

require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';

require ('smarty/libs/Smarty.class.php');
$smarty = new Smarty();

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

$smarty -> setTemplateDir('smarty/templates');
$smarty -> setCompileDir('smarty/templates_c');
$smarty -> setCacheDir('smarty/cache');
$smarty -> setConfigDir('smarty/configs');

$smarty -> assign('warehouseList', getWarehouseList());
$smarty -> assign('config', getConfig());
$smarty -> assign('debug', ob_get_clean());	// make function output available if needed
$smarty -> display('index.tpl');
?>
