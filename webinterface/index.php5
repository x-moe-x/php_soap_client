<?php
ob_start();
// prevent verbose functions from tainting output

require_once 'includes/basic_forward.inc.php';
require_once 'includes/helper_functions.inc.php';
require_once 'includes/smarty.inc.php';
require_once ROOT . 'includes/GetConfig.php';
require_once ROOT . 'api/ApiHelper.class.php';
require_once ROOT . 'api/ApiAmazon.class.php';

$smarty -> assign('warehouseList', getWarehouseList());
$smarty -> assign('config', Config::getAll());
$smarty -> assign('reorderSums', getReorderSums());
$smarty -> assign('debug', ob_get_clean() . checkItemSupplierConfiguration() . checkFailedOrders() . checkBadVariants());
// make function output available if needed

/* amazon */
$smarty->assign('amazonStatic', ApiHelper::getSalesOrderReferrer(4));
$smarty->assign('amazonVariables', ApiAmazon::getConfig());

$smarty -> display('index.tpl');
?>
