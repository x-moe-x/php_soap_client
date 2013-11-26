<?php
ob_start();

require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/GetConfig.php';

$integerKeys = array('calculationTimeA', 'calculationTimeB', 'minimumToleratedSpikesA', 'minimumToleratedSpikesB', 'minimumOrdersA', 'minimumOrdersB');
$floatKeys = array('standardDeviationFactor', 'spikeTolerance');
$config = getConfig();
$key = $_POST['key'];
$ucfirstKey = ucfirst($key);

if (isset($_POST['key']) && isset($_POST['value'])) {
	if (in_array($key, $integerKeys)) {
		$parsedValue = intval($_POST['value']);
	} else if (in_array($_POST['key'], $floatKeys)) {
		$parsedValue = floatval($_POST['value']);
		if ($key === 'spikeTolerance')
			$parsedValue /= 100.0;
	} else {
		// wrong value
		ob_end_clean();
		echo 'wrong key: ' . $key;
		exit();
	}

	if (!isset($config[$ucfirstKey])){
		ob_end_clean();
		echo 'wrong key: ' . $ucfirstKey;
	} else if ($config[$ucfirstKey]['Active'] !== 1){
		ob_end_clean();
		echo 'tried to modify inactive key: ' . $ucfirstKey;
	} else if ((($ucfirstKey === 'CalculationTimeA') && ($parsedValue < $config['CalculationTimeB']['Value']))||(($ucfirstKey === 'CalculationTimeB') && ($config['CalculationTimeA']['Value'] < $parsedValue))){
		ob_end_clean();
		echo 'calculation time a < calculation time b';
	} else {
		$query = 'REPLACE INTO `MetaConfig` ' . DBUtils::buildInsert(array('ConfigKey' => $ucfirstKey, 'ConfigValue' => $parsedValue, 'Active' => 1));
		DBQuery::getInstance() -> replace($query);
		ob_end_clean();
	}
} else {
	ob_end_clean();
	echo 'unsufficient arguments';
}
?>