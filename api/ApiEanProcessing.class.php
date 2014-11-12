<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(-1);

require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'api/ApiHelper.class.php';

class ApiEANProcessing {

	/**
	 * @param string $key
	 * @return array: key/value-pair or array of key/value-pairs
	 */
	public static function getConfigJSON($key = null){
		ApiHelper::getConfigJSON($key, 'eanProcessing');
	}

	/**
	 * @param string $key
	 * @param float $value
	 * @return array: key/value-pair
	 */
	public static function setConfigJSON($key, $value){
		ApiHelper::setConfigJSON($key, $value, 'eanProcessing');
	}
}
?>
