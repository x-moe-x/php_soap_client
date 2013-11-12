<?php
ob_start();

require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';

$integerKeys = array('calculationTimeA', 'calcualtionTimeB', 'minimumToleratedSpikesA', 'minimumToleratedSpikesB');
$floatKeys = array('standardDeviationFactor', 'spikeTolerance');

if (isset($_POST['key']) && isset($_POST['value'])) {
	if (in_array($_POST['key'], $integerKeys)) {
		$parsedValue = intval($_POST['value']);
	} else if (in_array($_POST['key'], $floatKeys)) {
		$parsedValue = floatval($_POST['value']);
		if ($_POST['key'] === 'spikeTolerance')
			$parsedValue /= 100.0;
	} else {
		// wrong value
		ob_end_clean();
		echo 'wrong key: ' . $_POST['key'];
		exit();
	}

	//TODO add validity checks!
	//TODO check for active
	$query = 'REPLACE INTO `MetaConfig` ' . DBUtils::buildInsert(array('ConfigKey' => ucfirst($_POST['key']), 'ConfigValue' => $parsedValue, 'Active' => 1));
	DBQuery::getInstance() -> replace($query);
	ob_end_clean();
} else {
	ob_end_clean();
	echo 'unsufficient arguments';
}
?>