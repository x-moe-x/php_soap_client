<?php
require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/soap/experiment_loader/NetXpressSoapExperimentLoader.class.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(-1);

class ApiExecute {

	private static $allowedJSONTasks = array('updateAll', 'calculateAll', 'setAll', 'resetArticles', 'resetOrders');

	public static function executeTaskWithOutputJSON($task) {
		self::executeTaskJSON($task, true);
	}

	public static function executeTaskJSON($task, $withOutput = false) {
		header('Content-Type: application/json');
		$result = array('success' => false, 'data' => NULL, 'error' => NULL);
		if (is_null($task)) {
			$result['error'] = "No task specified";
		} else {
			if (in_array($task, self::$allowedJSONTasks)) {
				try {
					$startTime = microtime(true);
					ob_start();
					self::executeTasks($task);
					$output = ob_get_clean();

					if ($withOutput) {
						$result['data'] = array('task' => $task, 'time' => number_format(microtime(true) - $startTime, 2), 'output' => $output);
					} else {
						$result['data'] = array('task' => $task, 'time' => number_format(microtime(true) - $startTime, 2));
					}
					$result['success'] = true;
				} catch(Exception $e) {
					$result['error'] = $e -> getMessage();
				}
			} else {
				$result['error'] = "Task $task not allowed";
			}
		}
		echo json_encode($result);
	}

	public static function executeTasks($tasks) {
		if (is_null($tasks)) {
			throw new RuntimeException('No task specified');
		} else if (!is_array($tasks)) {
			$tasks = array($tasks);
		}

		foreach ($tasks as $task) {
			switch ($task) {
				// checking single functions
				case 'updateOrders' :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'SearchOrders'));
					break;
				case 'updateItems' :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetItemsBase'));
					break;
				case 'updateItemsWarehouseSettings' :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetItemsWarehouseSettings'));
					break;
				case 'updateItemsSuppliers' :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetItemsSuppliers'));
					break;
				case 'updateWarehouseList' :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetWarehouseList'));
					break;
				case 'updateSalesOrderReferrer' :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetSalesOrderReferrer'));
					break;
				case 'calculateTotalNetto' :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'CalculateTotalNetto', 'CalculateTotalNetto'));
					break;
				case 'calculateDailyNeed' :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'CalculateDailyNeed', 'CalculateDailyNeed'));
					break;
				case 'calculateWriteBackData' :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'CalculateWriteBackData', 'CalculateWriteBackData'));
					break;
				case 'calculateWritePermissions' :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'DetermineWritePermissions', 'DetermineWritePermissions'));
					break;
				case 'setItemsSuppliers' :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'SetItemsSuppliers'));
					break;
				case 'setItemsWarehouseSettings' :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'SetItemsWarehouseSettings'));
					break;
				case 'resetArticles' :
					DBQuery::getInstance() -> truncate('TRUNCATE `ItemsBase`');
					DBQuery::getInstance() -> delete('DELETE FROM MetaLastUpdate WHERE Function = "SoapCall_GetItemsBase"');
					DBQuery::getInstance() -> delete('DELETE FROM MetaLastUpdate WHERE Function = "Adapter_GetItemsBase"');
					DBQuery::getInstance() -> truncate('TRUNCATE `ItemsWarehouseSettings`');
					DBQuery::getInstance() -> truncate('TRUNCATE `ItemSuppliers`');
					break;
				case 'resetOrders' :
					DBQuery::getInstance() -> truncate('TRUNCATE `OrderHead`');
					DBQuery::getInstance() -> delete('DELETE FROM MetaLastUpdate WHERE Function = "SoapCall_SearchOrders"');
					DBQuery::getInstance() -> delete('DELETE FROM MetaLastUpdate WHERE Function = "Adapter_SearchOrders"');
					DBQuery::getInstance() -> truncate('TRUNCATE `OrderItem`');
					break;
				default :
					// checking group functions
					switch ($task) {
						case 'updateAll' :
							self::executeTasks(array('updateOrders', 'updateItems', 'updateItemsWarehouseSettings', 'updateItemsSuppliers', 'updateWarehouseList', 'updateSalesOrderReferrer'));
							break;
						case 'calculateAll' :
							self::executeTasks(array('calculateTotalNetto', 'calculateDailyNeed', 'calculateWriteBackData', 'calculateWritePermissions'));
							break;
						case 'setAll' :
							self::executeTasks(array('setItemsSuppliers', 'setItemsWarehouseSettings'));
							break;
						default :
							throw new RuntimeException('Unknown task ' . $task);
					}
			}
		}
	}

}
?>
