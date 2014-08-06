<?php
ob_start();
// prevent verbose functions from tainting output

require_once 'includes/basic_forward.inc.php';
require_once 'includes/helper_functions.inc.php';
require_once 'includes/smarty.inc.php';
require_once ROOT . 'includes/GetConfig.php';
require_once ROOT . 'api/ApiHelper.class.php';
require_once ROOT . 'api/ApiAmazon.class.php';
require_once ROOT . 'api/ApiWarehouseGrouping.class.php';

$smarty -> assign('warehouseList', getWarehouseList());
$smarty -> assign('config', Config::getAll());
$smarty -> assign('reorderSums', getReorderSums());
$smarty -> assign('debug', ob_get_clean() . checkItemSupplierConfiguration() . checkFailedOrders() . checkBadVariants());
// make function output available if needed

/* amazon */
$smarty -> assign('amazonStatic', ApiHelper::getSalesOrderReferrer(ApiAmazon::AMAZON_REFERRER_ID));
$smarty -> assign('amazonVariables', ApiAmazon::getConfig());

/* warehouse group configuration */

$smarty -> assign('warehouseGroups', ApiWarehouseGrouping::getGroups());

$smarty -> display('index.tpl');
?>
