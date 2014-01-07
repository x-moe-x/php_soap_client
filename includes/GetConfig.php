<?php
require_once ROOT . 'lib/log/Logger.class.php';

class Config {

	/**
	 * sets the value of a given config-key
	 */
	function set($key, $value) {
		$query = 'SELECT `ConfigType`, `Active`, `ConfigValue` FROM `MetaConfig` WHERE `ConfigKey` = ' . $key . ' LIMIT 0,2';
		$queryResult = DBQuery::getInstance() -> select($query);

		// if key in db ...
		if ($queryResult -> getNumRows() !== 1) {
			$currentConfig = $queryResult -> fetchAssoc();

			// ... then check if key is active  ...
			if ($currentConfig['Active'] === 1) {

				// ... then check if type matches ...
				if ((($currentConfig['ConfigType'] === 'int') && (is_float($value))) xor (($currentConfig['ConfigType'] === 'float') && (is_int($value)))) {

					// ... then set given value, update timestamp and make log entry
					$oldValue = $currentConfig['ConfigValue'];
					$updateQuery = 'UPDATE `MetaConfig` SET `ConfigValue` = ' . $value . ', `LastUpdate` = ' . time() . ' WHERE `ConfigKey` = ' . $key;
					DBQuery::getInstance() -> update($updateQuery);
					$this -> getLogger() -> debug(__FUNCTION__ . ' Changed value of key ' . $key . ' from ' . $oldValue . ' to ' . $value);
					if ($currentConfig['ConfigType'] === 'int') {
						return intval($value);
					} else {
						return floatval($value);
					}
				} else {
					// ... otherwise trow exception and make log entry
					$this -> getLogger() -> err(__FUNCTION__ . ' Type of ' . $value . ' does not match ' . $currentConfig['ConfigType']);
					throw new Exception('Type of ' . $value . ' does not match ' . $currentConfig['ConfigType']);
				}
			} else {
				// ... otherwise don't change value and make log entry
				$this -> getLogger() -> debug(__FUNCTION__ . ' Key ' . $key . ' is inactive and value ' . $value . ' is not applied');
				if ($currentConfig['ConfigType'] === 'int') {
					return intval($currentConfig['ConfigValue']);
				} else {
					return floatval($currentConfig['ConfigValue']);
				}
			}
		} else {
			// ... otherwise throw exception and make log entry
			$this -> getLogger() -> err(__FUNCTION__ . ' Could not resolve desired key ' . $key);
			throw new Exception('Could not resolve desired key ' . $key);
		}
	}

	/**
	 * gets the value of a given config key
	 */
	function get($key) {
		// if key in db ...
		// ... then check if key is active ...
		// ... ... then return value
		// ... ... otherwise return 'inactive'-constant
		// ... otherwise throw exception
	}

	protected function getLogger() {
		return Logger::instance(__CLASS__);
	}

}

/**
 * extract config values form database
 *
 * @return array of config keys mapped on config values
 */
function getConfig() {
	$intValues = array('CalculationTimeA', 'CalculationTimeB', 'MinimumToleratedSpikesA', 'MinimumToleratedSpikesB', 'MinimumOrdersA', 'MinimumOrdersB');
	$floatValues = array('SpikeTolerance', 'StandardDeviationFactor');

	$query = 'SELECT
				* FROM `MetaConfig`
				WHERE';

	$nrOfIntVals = count($intValues);
	$nrOfFloatVals = count($floatValues);
	for ($i = 0; $i < $nrOfIntVals; ++$i){
		$query .= '
					`ConfigKey` = "'. $intValues[$i] .'" OR';
	}
	for ($i = 0; $i < $nrOfFloatVals; ++$i){
		$query .= '
					`ConfigKey` = "'. $floatValues[$i] .'" ' . ($i + 1 < $nrOfFloatVals ? 'OR':'');
	}

	$resultConfigQuery = DBQuery::getInstance() -> select($query);

	$result = array();
	for ($i = 0; $i < $resultConfigQuery -> getNumRows(); ++$i) {
		$configRow = $resultConfigQuery -> fetchAssoc();
		if ($configRow['ConfigKey'] == 'SpikeTolerance' || $configRow['ConfigKey'] == 'StandardDeviationFactor')
			$result[$configRow['ConfigKey']]['Value'] = floatval($configRow['ConfigValue']);
		else
			$result[$configRow['ConfigKey']]['Value'] = intval($configRow['ConfigValue']);

		$result[$configRow['ConfigKey']]['Active'] = intval($configRow['Active']);
	}

	foreach (array_merge($intValues, $floatValues) as $key) {
		if (!isset($result[$key]))
			throw new RuntimeException('Missing Config Key: ' . $key);
	}

	return $result;
}

?>