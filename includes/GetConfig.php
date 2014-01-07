<?php
require_once ROOT . 'lib/log/Logger.class.php';

class Config {

	/**
	 * sets the value of a given config-key
	 */
	public static function set($key, $value) {
		$query = 'SELECT `ConfigType`, `Active`, `ConfigValue` FROM `MetaConfig` WHERE `ConfigKey` = \'' . $key . '\' LIMIT 0,2';
		$queryResult = DBQuery::getInstance() -> select($query);

		// if key in db ...
		if ($queryResult -> getNumRows() === 1) {
			
			$currentConfig = $queryResult -> fetchAssoc();
			$oldValue = $currentConfig['ConfigType'] === 'int' ? intval($currentConfig['ConfigValue']) : floatval($currentConfig['ConfigValue']);

			// ... then check if key is active  ...
			if (intval($currentConfig['Active']) === 1) {

				$parsedValue = $currentConfig['ConfigType'] === 'int' ? intval($value) : floatval($value);

				// ... then check if type matches ...
				if ((($currentConfig['ConfigType'] === 'int') && (is_int($parsedValue))) xor (($currentConfig['ConfigType'] === 'float') && (is_float($parsedValue)))) {

					// ... then set given value, update timestamp and make log entry
					$updateQuery = 'UPDATE `MetaConfig` SET `ConfigValue` = ' . $value . ', `LastUpdate` = ' . time() . ' WHERE `ConfigKey` = \'' . $key . '\'';
					DBQuery::getInstance() -> update($updateQuery);
					Config::getLogger() -> debug(__FUNCTION__ . ' Changed value of key ' . $key . ' from ' . $oldValue . ' to ' . $value);
					return $parsedValue;
				} else {
					// ... otherwise trow exception and make log entry
					Config::getLogger() -> debug(__FUNCTION__ . ' Type of ' . $value . ' does not match ' . $currentConfig['ConfigType']);
					throw new Exception('Type of ' . $value . ' does not match ' . $currentConfig['ConfigType']);
					return $oldValue;
				}
			} else {
				// ... otherwise don't change value and make log entry
				Config::getLogger() -> debug(__FUNCTION__ . ' Key ' . $key . ' is inactive and value ' . $value . ' is not applied');
				return $oldValue;
			}
		} else {
			// ... otherwise throw exception and make log entry
			Config::getLogger() -> debug(__FUNCTION__ . ' Could not resolve desired key ' . $key);
			throw new Exception('Could not resolve desired key ' . $key);
			return null;
		}
	}

	/**
	 * get all config values at once
	 */
	public static function getAll() {
		$query = 'SELECT * FROM `MetaConfig`';
		$queryResult = DBQuery::getInstance() -> select($query);

		$result = array();
		while ($currentConfig = $queryResult -> fetchAssoc()) {
			$parsedValue = $currentConfig['ConfigType'] === 'int' ? intval($currentConfig['ConfigValue']) : floatval($currentConfig['ConfigValue']);
			$result[$currentConfig['ConfigKey']]['Value'] = $parsedValue;
			$result[$currentConfig['ConfigKey']]['Active'] = intval($currentConfig['Active']);
		}
		return $result;
	}

	/**
	 * get the value of a specific config key
	 */
	public static function get($key) {
		$config = Config::getAll();

		// if key in db ...
		if (in_array($key, $config)) {
			// ... then check if key is active ...
			if ($config[$key]['Active'] === 1) {
				// ... ... then return value
				return $config[$key]['Value'];
			} else {
				// ... ... otherwise return 'inactive'-constant
				return 'inactive';
			}
		} else {
			// ... otherwise throw exception
			Config::getLogger() -> debug(__FUNCTION__ . ' Could not resolve desired key ' . $key);
			throw new Exception('Could not resolve desired key ' . $key);
		}
	}

	protected static function getLogger() {
		return Logger::instance(__CLASS__);
	}

}

/*

 function getConfig() {
 $intValues = array('CalculationTimeA', 'CalculationTimeB', 'MinimumToleratedSpikesA', 'MinimumToleratedSpikesB', 'MinimumOrdersA', 'MinimumOrdersB');
 $floatValues = array('SpikeTolerance', 'StandardDeviationFactor');

 $query = 'SELECT
 * FROM `MetaConfig`
 WHERE';

 $nrOfIntVals = count($intValues);
 $nrOfFloatVals = count($floatValues);
 for ($i = 0; $i < $nrOfIntVals; ++$i) {
 $query .= '
 `ConfigKey` = "' . $intValues[$i] . '" OR';
 }
 for ($i = 0; $i < $nrOfFloatVals; ++$i) {
 $query .= '
 `ConfigKey` = "' . $floatValues[$i] . '" ' . ($i + 1 < $nrOfFloatVals ? 'OR' : '');
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
 }*/
?>