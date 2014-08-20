<?php
ob_start();
require_once 'includes/basic_forward.inc.php';
require_once 'includes/smarty.inc.php';
require_once ROOT . 'api/ApiRunningCosts.class.php';
require_once ROOT . 'api/ApiGeneralCosts.class.php';

$data = ApiRunningCosts::getRunningCostsTable();
ob_end_clean();

$months = array();
foreach (array_keys($data) as $date) {
	$currentDate = new DateTime($date);
	$months[$date] = $currentDate -> format('M. Y');	
}
//$generalCosts = ApiGeneralCosts::getGeneralCosts();

$smarty -> assign('months', $months);
//$smarty -> assign('generalCosts', $generalCosts);
$smarty -> assign('data', $data);

header('Content-type: text/xml');
$smarty -> display('runningCost-post-new.tpl');
?>