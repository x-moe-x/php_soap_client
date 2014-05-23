<?php
ob_start();
// prevent verbose functions from tainting output

require_once 'includes/basic_forward.inc.php';
require_once 'includes/helper_functions.inc.php';
require_once 'includes/smarty.inc.php';
require_once ROOT . 'includes/GetConfig.php';

$smarty -> assign('warehouseList', getWarehouseList());
$smarty -> assign('config', Config::getAll());
$smarty -> assign('reorderSums', getReorderSums());
$smarty -> assign('debug', ob_get_clean() . checkItemSupplierConfiguration() . checkFailedOrders() . checkBadVariants());
// make function output available if needed
$smarty -> display('index.tpl');
?>
