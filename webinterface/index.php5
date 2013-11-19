<?php
ob_start();	// prevent verbose functions from tainting output

require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/GetConfig.php';

require ('smarty/libs/Smarty.class.php');
$smarty = new Smarty();

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
