<?php
require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/soap/experiment_loader/NetXpressSoapExperimentLoader.class.php';
require_once ROOT . 'experiments/CalculateAmazonWeightenedRunningCosts/CalculateAmazonWeightenedRunningCosts.class.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(-1);

class ApiExecute {

	private static $allowedJSONTasks = array(self::UPDATE_ALL, self::CALCULATE_ALL, self::SET_ALL, self::RESET_ARTICLES, self::RESET_ORDERS, self::CALCULATE_AMAZON_RUNNING_COSTS, self::SET_ITEMS_PRICE_SETS, self::RESET_PRICE_UPDATES);

	/**
	 * @var string
	 */
	const UPDATE_ORDERS = 'updateOrders';

	/**
	 * @var string
	 */
	const UPDATE_ITEMS = 'updateItems';

	/**
	 * @var string
	 */
	const UPDATE_ITEMS_WAREHOUSE_SETTINGS = 'updateItemsWarehouseSettings';

	/**
	 * @var string
	 */
	const UPDATE_ITEMS_SUPPLIERS = 'updateItemsSuppliers';

	/**
	 * @var string
	 */
	const UPDATE_WAREHOUSE_LIST = 'updateWarehouseList';

	/**
	 * @var string
	 */
	const UPDATE_SALES_ORDER_REFERRER = 'updateSalesOrderReferrer';

	/**
	 * @var string
	 */
	const UPDATE_ITEMS_PRICE_LISTS = 'updateItemsPriceLists';

	/**
	 * @var string
	 */
	const UPDATE_ALL = 'updateAll';

	/**
	 * @var string
	 */
	const CALCULATE_TOTAL_NETTO = 'calculateTotalNetto';

	/**
	 * @var string
	 */
	const CALCULATE_DAILY_NEED = 'calculateDailyNeed';

	/**
	 * @var string
	 */
	const CALCULATE_WRITE_BACK_DATA = 'calculateWriteBackData';

	/**
	 * @var string
	 */
	const CALCULATE_WRITE_PERMISSIONS = 'calculateWritePermissions';

	/**
	 * @var string
	 */
	const CALCULATE_AMAZON_RUNNING_COSTS = 'calculateAmazonRunningCosts';

	/**
	 * @var string
	 */
	const CALCULATE_ALL = 'calculateAll';

	/**
	 * @var string
	 */
	const SET_ITEMS_SUPPLIERS = 'setItemsSuppliers';

	/**
	 * @var string
	 */
	const SET_ITEMS_WAREHOUSE_SETTINGS = 'setItemsWarehouseSettings';

	/**
	 * @var string
	 */
	const SET_ITEMS_PRICE_SETS = 'setItemsPriceSets';

	/**
	 * @var string
	 */
	const SET_ALL = 'setAll';

	/**
	 * @var string
	 */
	const RESET_ARTICLES = 'resetArticles';

	/**
	 * @var string
	 */
	const RESET_ORDERS = 'resetOrders';

	/**
	 * @var string
	 */
	const RESET_PRICE_UPDATES = 'resetPriceUpdates';

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
				case self::UPDATE_ORDERS :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'SearchOrders'));
					break;
				case self::UPDATE_ITEMS :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetItemsBase'));
					break;
				case self::UPDATE_ITEMS_WAREHOUSE_SETTINGS :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetItemsWarehouseSettings'));
					break;
				case self::UPDATE_ITEMS_SUPPLIERS :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetItemsSuppliers'));
					break;
				case self::UPDATE_ITEMS_PRICE_LISTS :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetItemsPriceLists'));
					break;
				case self::UPDATE_WAREHOUSE_LIST :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetWarehouseList'));
					break;
				case self::UPDATE_SALES_ORDER_REFERRER :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetSalesOrderReferrer'));
					break;
				case self::CALCULATE_TOTAL_NETTO :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'CalculateTotalNetto', 'CalculateTotalNetto'));
					break;
				case self::CALCULATE_DAILY_NEED :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'CalculateDailyNeed', 'CalculateDailyNeed'));
					break;
				case self::CALCULATE_WRITE_BACK_DATA :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'CalculateWriteBackData', 'CalculateWriteBackData'));
					break;
				case self::CALCULATE_WRITE_PERMISSIONS :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'DetermineWritePermissions', 'DetermineWritePermissions'));
					break;
				case self::CALCULATE_AMAZON_RUNNING_COSTS :
					if (CalculateAmazonWeightenedRunningCosts::arePrequisitesMet()) {
						NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'CalculateAmazonWeightenedRunningCosts', 'CalculateAmazonWeightenedRunningCosts'));
					} else {
						throw new RuntimeException('Prequisites not met for ' . self::CALCULATE_AMAZON_RUNNING_COSTS);
					}
					break;
				case self::SET_ITEMS_SUPPLIERS :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'SetItemsSuppliers'));
					break;
				case self::SET_ITEMS_PRICE_SETS :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'SetPriceSets'));
					break;
				case self::SET_ITEMS_WAREHOUSE_SETTINGS :
					NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'SetItemsWarehouseSettings'));
					break;
				case self::RESET_ARTICLES :
					DBQuery::getInstance() -> truncate('TRUNCATE `ItemsBase`');
					DBQuery::getInstance() -> delete('DELETE FROM MetaLastUpdate WHERE Function = "SoapCall_GetItemsBase"');
					DBQuery::getInstance() -> delete('DELETE FROM MetaLastUpdate WHERE Function = "Adapter_GetItemsBase"');
					DBQuery::getInstance() -> truncate('TRUNCATE `ItemsWarehouseSettings`');
					DBQuery::getInstance() -> truncate('TRUNCATE `ItemSuppliers`');
					break;
				case self::RESET_ORDERS :
					DBQuery::getInstance() -> truncate('TRUNCATE `OrderHead`');
					DBQuery::getInstance() -> delete('DELETE FROM MetaLastUpdate WHERE Function = "SoapCall_SearchOrders"');
					DBQuery::getInstance() -> delete('DELETE FROM MetaLastUpdate WHERE Function = "Adapter_SearchOrders"');
					DBQuery::getInstance() -> truncate('TRUNCATE `OrderItem`');
					break;
				case self::RESET_PRICE_UPDATES :
					DBQuery::getInstance() -> truncate('TRUNCATE `PriceUpdate`');
					break;
				default :
					// checking group functions
					switch ($task) {
						case self::UPDATE_ALL :
							self::executeTasks(array(self::UPDATE_ORDERS, self::UPDATE_ITEMS, self::UPDATE_ITEMS_PRICE_LISTS, self::UPDATE_ITEMS_WAREHOUSE_SETTINGS, self::UPDATE_ITEMS_SUPPLIERS, self::UPDATE_WAREHOUSE_LIST, self::UPDATE_SALES_ORDER_REFERRER));
							break;
						case self::CALCULATE_ALL :
							self::executeTasks(array(self::CALCULATE_TOTAL_NETTO, self::CALCULATE_DAILY_NEED, self::CALCULATE_WRITE_BACK_DATA, self::CALCULATE_WRITE_PERMISSIONS));
							break;
						case self::SET_ALL :
							self::executeTasks(array(self::SET_ITEMS_SUPPLIERS, self::SET_ITEMS_WAREHOUSE_SETTINGS, self::SET_ITEMS_PRICE_SETS));
							break;
						default :
							throw new RuntimeException('Unknown task ' . $task);
					}
			}
		}
	}

}
?>
