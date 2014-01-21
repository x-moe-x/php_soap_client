<?php
ob_start();

require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'includes/GetConfig.php';

$result = array('Message' => null, 'Value' => null);

if (isset($_POST['key']) && isset($_POST['value' ])) {
	$key = ucfirst($_POST['key']);
	$newValue = $_POST['value'];
	try {
		$oldValue = Config::get($key);
		// throws exception on fail

		// if key is active ...
		if ($oldValue !== 'inactive') {
			// ... then proceed

			if ($key === 'SpikeTolerance') {
				// try to convert from percentage value to float ...
				$setResult = Config::set($key, floatval($newValue) / 100);
			} else if ((($key === 'CalculationTimeA') && (intval($newValue) < $oldValue)) || (($key === 'CalculationTimeB') && ($oldValue < intval($newValue)))) {
				// prevent calculation time a beeing smaller than calculation time b
				if ($key === 'CalculationTimeA') {
					$result['Value'] = $oldValue;
				} else {
					$result['Value'] = $oldValue;
				}
				$result['Message'] = 'calculation time a < calculation time b';
			} else {
				// try to apply normal case scenario ...
				$setResult = Config::set($key, $newValue);
			}
		} else {
			// ... otherwise report failure
			$result['Message'] = 'Key ' . $key . ' is inactive and value ' . $newValue . ' is not applied';
		}
	} catch (Exception $e) {
		// report unresolved key
		$result['Message'] = $e -> getMessage();
	}
} else {
	$result['Message'] = 'insufficient arguments';
}
ob_end_clean();

// generate json
echo json_encode($result);
?>