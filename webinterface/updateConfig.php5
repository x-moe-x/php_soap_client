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
$preJSON = array('Message' => null, 'Value' => null);

if (isset($_POST['key']) && isset($_POST['value'])) {
	try {
		if ($key !== 'spikeTolerance') {
			$setResult = Config::set($ucfirstKey, $_POST['value']);
		} else {
			$setResult = Config::set($ucfirstKey, floatval($_POST['value']) / 100);
		}
	} catch (Exception $e) {
		$preJSON['Message'] = $e -> getMessage();
		$preJSON['Value'] = $setResult;
	}
	/*
	 if (in_array($key, $integerKeys)) {
	 $parsedValue = intval($_POST['value']);
	 } else if (in_array($_POST['key'], $floatKeys)) {
	 $parsedValue = floatval($_POST['value']);
	 if ($key === 'spikeTolerance')
	 $parsedValue /= 100.0;
	 } else {
	 // wrong value
	 $preJSON['Message'] = 'wrong key: ' . $key;
	 }

	 if (!isset($config[$ucfirstKey])) {
	 $preJSON['Message'] = 'wrong key: ' . $ucfirstKey;
	 } else if ($config[$ucfirstKey]['Active'] !== 1) {
	 $preJSON['Message'] = 'tried to modify inactive key: ' . $ucfirstKey;
	 } else if ((($ucfirstKey === 'CalculationTimeA') && ($parsedValue < $config['CalculationTimeB']['Value'])) || (($ucfirstKey === 'CalculationTimeB') && ($config['CalculationTimeA']['Value'] < $parsedValue))) {
	 if ($ucfirstKey === 'CalculationTimeA')
	 $preJSON['Value'] = $config['CalculationTimeA']['Value'];
	 else
	 $preJSON['Value'] = $config['CalculationTimeB']['Value'];
	 $preJSON['Message'] = 'calculation time a < calculation time b';
	 } else {
	 $query = 'REPLACE INTO `MetaConfig` ' . DBUtils::buildInsert(array('ConfigKey' => $ucfirstKey, 'ConfigValue' => $parsedValue, 'Active' => 1));
	 $preJSON['Value'] = $parsedValue;
	 DBQuery::getInstance() -> replace($query);
	 }*/
} else {
	$preJSON['Message'] = 'unsufficient arguments';
}
ob_end_clean();

// generate json
echo json_encode($preJSON);
?>