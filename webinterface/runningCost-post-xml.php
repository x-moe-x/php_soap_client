<?php
ob_start();
require_once 'includes/basic_forward.inc.php';
require_once 'includes/smarty.inc.php';
require_once ROOT . 'api/ApiGeneralCosts.class.php';

$data = ApiGeneralCosts::getCostsTotal();
ob_end_clean();

$months = array();
foreach (array_keys($data[-1]) as $date) {
	if ($date !== 'average') {
		$currentDate = new DateTime($date);
		$months[$date] = $currentDate -> format('M. Y');
	} else {
		$months[$date] = 'Durchschnitt';
	}
}

$smarty -> assign('months', $months);
$smarty -> assign('data', $data);

header('Content-type: text/xml');
$smarty -> display('runningCost-post.tpl');
?>