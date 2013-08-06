<?php
ob_start();

require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';

$integerKeys = array('calculationTimeSingleWeighted', 'calcualtionTimeDoubleWeighted', 'minimumToleratedSpikes');
$floatKeys = array('standardDeviationFactor', 'spikeTolerance');

$parsedValue = null;

if (isset($_POST['key']) && isset($_POST['value'])) {
	if (in_array($_POST['key'], $integerKeys)) {
		$parsedValue = intval($_POST['value']);
	} else if (in_array($_POST['key'], $floatKeys)) {
		$parsedValue = floatval($_POST['value']);
	} else {
		// wrong value
		header("status: 400");
		exit();
	}
}

//TODO add validity checks!
$query = 'REPLACE INTO `MetaConfig` ' . DBUtils::buildInsert(array('ConfigKey' => ucfirst($_POST['key']), 'ConfigValue' => $parsedValue));

DBQuery::getInstance() -> replace($query);

$output = ob_get_clean();
?>