<?php
require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';

class ApiStock {

	public static function getConfigJSON($key = null) {
		header('Content-Type: application/json');
		$result = array('success' => false, 'data' => NULL, 'error' => NULL);

		list($data, $error) = array_pad(self::getConfig($key), 2, 'unexpected padding occurred: getConfigJSON()');

		if (!is_null($error)) {
			$result['error'] = $error;
		} else {
			$result['success'] = true;
			$result['data'] = $data;
		}
		echo json_encode($result);
	}

	public static function setConfigJSON($key = null, $value = null) {
		header('Content-Type: application/json');
		$result = array('success' => false, 'data' => NULL, 'error' => NULL);

		if (is_null($key)) {
			$result['error'] = "No key to set value = '$value' in stock config";
		} else {;
			if (is_null($value)) {
				$result['error'] = "No value for $key in stock config";
			} else {
				list($data, $error) = array_pad(self::setConfig($key, $value), 2, 'unexpected padding occurred: setConfigJSON()');

				if (!is_null($error)) {
					$result['error'] = $error;
				} else {
					$result['success'] = true;
					$result['data'] = $data;
				}
			}
		}

		echo json_encode($result);
	}

	public static function getConfig($key = null) {
		$query = 'SELECT `ConfigType` AS `type`, `ConfigKey` AS `key`, `ConfigValue` AS `value` FROM MetaConfig WHERE `Active` = 1 AND `Domain` = \'stock\'';
		if (is_null($key)) {
			// getting all active k/v-pairs from stock config
		} else if (is_array($key)) {
			// getting value of $key from stock config;
			$query .= ' AND `ConfigKey` IN (' . implode(',', $key) . ')';
		} else {
			// getting value of $key from stock config;
			$query .= ' AND `ConfigKey` = \'' . $key . '\'';
		}

		ob_start();
		$dbResult = DBQuery::getInstance() -> select($query);
		ob_end_clean();

		$result = array();
		$error = '';

		while ($row = $dbResult -> fetchAssoc()) {
			switch ($row['type']) {
				case 'int' :
					$result[$row['key']] = intval($row['value']);
					break;
				case 'float' :
					$result[$row['key']] = floatval($row['value']);
					break;
				default :
					$error .= "ConfigType {$row['type']} not allowed\n";
			}
		}

		if (count($result) === 0) {
			$error .= "No data available for keys: " . implode(', ', $key) . "\n";
		}

		return array($result, $error === '' ? NULL : $error);
	}

	public static function setConfig($key, $value) {
		$query = "SELECT `ConfigType` AS `type`, `Active` AS `active`, `ConfigKey` AS `key`, `ConfigValue` AS `value` FROM MetaConfig WHERE `ConfigKey` = '$key' AND `Domain` = 'stock'";

		ob_start();
		$dbResult = DBQuery::getInstance() -> select($query);
		ob_end_clean();

		$result = array($key => NULL);
		$error = '';

		// check if key is available
		if ($dbResult -> getNumRows() === 1 && $row = $dbResult -> fetchAssoc()) {
			// ... then check if it is active
			if (intval($row['active']) === 1) {
				// ... ... then set value
				ob_start();
				DBQuery::getInstance() -> update("UPDATE MetaConfig SET `ConfigValue`='$value' WHERE `ConfigKey` = '$key' AND `Domain` = 'stock'");
				$dbResult = DBQuery::getInstance() -> select($query);
				ob_end_clean();
				if (($updatedRow = $dbResult -> fetchAssoc()) && ($updatedRow['value'] == $value)) {
					$result[$key] = $value;
				} else {
					$result[$key] = $updatedRow['value'];
					$error .= "unable to update key $key, value is still {$updatedRow['value']}\n";
				}

			} else {
				// ... ... otherwise: error
				$error .= "trying to set inactive key $key\n";
			}
		} else {
			// ... otherwise: error
			$error .= "key $key unavailable\n";
		}

		return array($result, $error === '' ? NULL : $error);
	}

};
?>