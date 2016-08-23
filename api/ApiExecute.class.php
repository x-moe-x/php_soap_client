<?php
require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/soap/experiment_loader/NetXpressSoapExperimentLoader.class.php';
require_once ROOT . 'experiments/CalculateAmazonWeightenedRunningCosts/CalculateAmazonWeightenedRunningCosts.class.php';
require_once ROOT . 'api/ApiTasks.class.php';
require_once ROOT . 'includes/FileLock.class.php';

/**
 * Provides methods to execute or schedule tasks.
 */
class ApiExecute
{
	/**
	 * @var string
	 */
	const JUST_WAIT = 'justWait';

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
	const PREPARE_UPDATE_ITEM_POSITIONS = 'prepareUpdateItemPositions';

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
	const UPDATE_CURRENT_STOCKS = 'updateCurrentStocks';

	/**
	 * @var string
	 */
	const UPDATE_LINKED_ITEMS = 'updateLinkedItems';

	/**
	 * @var string
	 */
	const UPDATE_ALL = 'updateAll';

	/**
	 * @var string
	 */
	const READ_JANSEN_STOCK_IMPORT = 'readJansenStockImport';

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
	const CALCULATE_AMAZON_QUANTITIES = 'calculateAmazonQuantities';

	/**
	 * @var string
	 */
	const CALCULATE_JANSEN_STOCK_MATCHES = 'calculateJansenStockMatches';

	/**
	 * @var string
	 */
	const CALCULATE_JANSEN_STOCK_MATCHES_TOTAL = 'calculateJansenStockMatchesTotal';

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
	const SET_ITEMS_BASE = 'setItemsBase';

	/**
	 * @var string
	 */
	const SET_ITEMS_WAREHOUSE_SETTINGS = 'setItemsWarehouseSettings';

	/**
	 * @var string
	 */
	const UPDATE_ITEMS_PRICE_SETS = 'setItemsPriceSets';

	/**
	 * @var string
	 */
	const UPDATE_ITEM_POSITIONS = 'updateItemPositions';

	/**
	 * @var string
	 */
	const SET_CURRENT_STOCKS = 'setCurrentStocks';

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

	/**
	 * @var string
	 */
	const DAILY_TASK = 'dailyTask';

	/**
	 * @var string
	 */
	const JANSEN_STOCK_UPDATE = 'jansenStockUpdate';

	/**
	 * @var string
	 */
	const JANSEN_STOCK_UPDATE_TOTAL = 'jansenStockUpdateTotal';

	/**
	 * @var array
	 */
	private static $allowedJSONTasks = array(
		self::UPDATE_ALL,
		self::CALCULATE_ALL,
		self::SET_ALL,
		self::RESET_ARTICLES,
		self::RESET_ORDERS,
		self::CALCULATE_AMAZON_RUNNING_COSTS,
		self::UPDATE_ITEMS_PRICE_SETS,
		self::RESET_PRICE_UPDATES
	);

	/**
	 * Executes the specified task (or it is scheduled for execution if another instance or the pseudo daemon is
	 * currently running) and discard it's output. Prints a json object to stdout.
	 * Might result in deferred execution via pseudo daemon!
	 *
	 * @param string $task name of the task to be executed
	 *
	 * @return void
	 */
	public static function sheduleTaskWithOutputJSON($task)
	{
		self::sheduleTaskJSON($task, true);
	}

	/**
	 * Executes the specified task (or it is scheduled for execution if another instance or the pseudo daemon is
	 * currently running) and optionally includes it's output. Prints a json object to stdout.
	 * Might result in deferred execution via pseudo daemon!
	 *
	 * @param string $task       name of the task to be executed
	 * @param bool   $withOutput specify if any form of verbose call-related output is needed
	 *
	 * @return void
	 */
	public static function sheduleTaskJSON($task, $withOutput = false)
	{
		header('Content-Type: application/json');
		$result = array(
			'success' => false,
			'data'    => NULL,
			'error'   => NULL,
		);
		if (is_null($task))
		{
			$result['error'] = "No task specified";
		} else
		{
			if (in_array($task, self::$allowedJSONTasks))
			{
				$executionLock = new FileLock();
				$dbQueueLock = new FileLock();
				try
				{
					$startTime = microtime(true);
					$executionLock->init(ROOT . '/tmp/execution.Lock');
					$dbQueueLock->init(ROOT . '/tmp/dbQueue.Lock');

					// if other instance or the daemon is busy ...
					if ($executionLock->tryLock())
					{
						// ... it's safe to execute

						// if possible ...
						if ($dbQueueLock->tryLock())
						{
							// ... dequeue task because it will be executed just afterwards
							ApiTasks::dequeue($task);
							$dbQueueLock->unlock();
						} else
						{
							// ... but no stress if it's not possible
						}

						ob_start();
						self::executeTasks($task);
						$executionLock->unlock();

						$output = ob_get_clean();

						if ($withOutput)
						{
							$result['data'] = array(
								'task'                => $task,
								'isExecutionDeferred' => false,
								'time'                => number_format(microtime(true) - $startTime, 2),
								'output'              => $output,
							);
						} else
						{
							$result['data'] = array(
								'task'                => $task,
								'isExecutionDeferred' => false,
								'time'                => number_format(microtime(true) - $startTime, 2),
							);
						}
					} else
					{
						// ... otherwise we have to enqueue the task

						if ($dbQueueLock->lock())
						{
							ApiTasks::enqueueTask($task);

							$dbQueueLock->unlock();

							$result['data'] = array(
								'task'                => $task,
								'isExecutionDeferred' => true,
								'time'                => number_format(microtime(true) - $startTime, 2),
							);
						} else
						{
							throw new RuntimeException("Could not acquire dbQueueLock, didn't enqueue $task");
						}


					}

					$executionLock->discard();
					$dbQueueLock->discard();

					$result['success'] = true;
				} catch (Exception $e)
				{
					$executionLock->discard();
					$dbQueueLock->discard();

					$result['error'] = $e->getMessage();
				}
			} else
			{
				$result['error'] = "Task $task not allowed";
			}
		}
		echo json_encode($result);
	}

	/**
	 * Executes given task(s) immediately (and in case of multiple tasks in array order)
	 *
	 * @param string|string[] $tasks name (or array of names) of task(s) to be executed
	 *
	 * @return void
	 */
	public static function executeTasks($tasks)
	{
		// normalize parameter
		if (is_null($tasks))
		{
			throw new RuntimeException('No task specified');
		} else
		{
			if (!is_array($tasks))
			{
				$tasks = array($tasks);
			}
		}

		foreach ($tasks as $task)
		{
			switch ($task)
			{
				case self::JUST_WAIT:
					for ($i = 0; $i < 60 * 4; $i++)
					{
						sleep(1);
						if ($i % 5 === 0)
						{
							Logger::instance(__CLASS__)->debug(__FUNCTION__ . ': waiting for ' . (60 * 4 - $i) . ' seconds');
						}
					}
					break;
				case self::UPDATE_ORDERS :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'SearchOrders',
					));
					break;
				case self::UPDATE_ITEMS :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'GetItemsBase',
					));
					break;
				case self::UPDATE_ITEMS_WAREHOUSE_SETTINGS :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'GetItemsWarehouseSettings',
					));
					break;
				case self::UPDATE_ITEMS_SUPPLIERS :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'GetItemsSuppliers',
					));
					break;
				case self::UPDATE_ITEMS_PRICE_LISTS :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'GetItemsPriceLists',
					));
					break;
				case self::UPDATE_WAREHOUSE_LIST :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'GetWarehouseList',
					));
					break;
				case self::UPDATE_SALES_ORDER_REFERRER :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'GetSalesOrderReferrer',
					));
					break;
				case self::UPDATE_CURRENT_STOCKS :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'GetCurrentStocks',
					));
					break;
				case self::UPDATE_LINKED_ITEMS :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'GetLinkedItems',
					));
					break;
				case self::JANSEN_STOCK_UPDATE :
					self::executeTasks(array(
						self::READ_JANSEN_STOCK_IMPORT,
						self::CALCULATE_JANSEN_STOCK_MATCHES,
						self::SET_CURRENT_STOCKS,
					));
					break;
				case self::JANSEN_STOCK_UPDATE_TOTAL :
					self::executeTasks(array(
						self::READ_JANSEN_STOCK_IMPORT,
						self::CALCULATE_JANSEN_STOCK_MATCHES_TOTAL,
						self::SET_CURRENT_STOCKS,
					));
					break;
				case self::READ_JANSEN_STOCK_IMPORT :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'JansenStockImport',
						'JansenStockImport',
					));
					break;
				case self::CALCULATE_TOTAL_NETTO :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'CalculateTotalNetto',
						'CalculateTotalNetto',
					));
					break;
				case self::CALCULATE_DAILY_NEED :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'CalculateDailyNeed',
						'CalculateDailyNeed',
					));
					break;
				case self::CALCULATE_WRITE_BACK_DATA :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'CalculateWriteBackData',
						'CalculateWriteBackData',
					));
					break;
				case self::CALCULATE_WRITE_PERMISSIONS :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'DetermineWritePermissions',
						'DetermineWritePermissions',
					));
					break;
				case self::CALCULATE_AMAZON_RUNNING_COSTS :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'CalculateAmazonWeightenedRunningCosts',
						'CalculateAmazonWeightenedRunningCosts',
					));
					break;
				case self::CALCULATE_AMAZON_QUANTITIES :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'CalculateAmazonQuantities',
						'CalculateAmazonQuantities',
					));
					break;
				case self::CALCULATE_JANSEN_STOCK_MATCHES :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'JansenStockMatchForUpdate',
						'JansenStockMatchForUpdate',
					));
					break;
				case self::CALCULATE_JANSEN_STOCK_MATCHES_TOTAL :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'JansenStockMatchForUpdateTotal',
						'JansenStockMatchForUpdateTotal',
					));
					break;
				case self::SET_ITEMS_SUPPLIERS :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'SetItemsSuppliers',
					));
					break;
				case self::UPDATE_ITEMS_PRICE_SETS :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'UpdatePriceSets',
						'UpdatePriceSets',
					));
					break;
				case self::SET_ITEMS_WAREHOUSE_SETTINGS :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'SetItemsWarehouseSettings',
					));
					break;
				case self::PREPARE_UPDATE_ITEM_POSITIONS :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'PrepareUpdateItemPositions',
						'PrepareUpdateItemPositions',
					));
					break;
				case self::SET_ITEMS_BASE :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'SetItemsBase',
					));
					break;
				case self::SET_CURRENT_STOCKS :
					NetXpressSoapExperimentLoader::getInstance()->run(array(
						'',
						'SetCurrentStocks',
					));
					break;
				case self::RESET_ARTICLES :
					DBQuery::getInstance()->truncate('TRUNCATE `ItemsBase`');
					DBQuery::getInstance()->delete('DELETE FROM MetaLastUpdate WHERE Function = "SoapCall_GetItemsBase"');
					DBQuery::getInstance()->delete('DELETE FROM MetaLastUpdate WHERE Function = "Adapter_GetItemsBase"');
					DBQuery::getInstance()->truncate('TRUNCATE `ItemsWarehouseSettings`');
					DBQuery::getInstance()->truncate('TRUNCATE `ItemSuppliers`');
					break;
				case self::RESET_ORDERS :
					DBQuery::getInstance()->truncate('TRUNCATE `OrderHead`');
					DBQuery::getInstance()->delete('DELETE FROM MetaLastUpdate WHERE Function = "SoapCall_SearchOrders"');
					DBQuery::getInstance()->delete('DELETE FROM MetaLastUpdate WHERE Function = "Adapter_SearchOrders"');
					DBQuery::getInstance()->truncate('TRUNCATE `OrderItem`');
					break;
				case self::RESET_PRICE_UPDATES :
					DBQuery::getInstance()->truncate('TRUNCATE `PriceUpdate`');
					break;
				case self::UPDATE_ITEM_POSITIONS :
					self::executeTasks(array(
						self::PREPARE_UPDATE_ITEM_POSITIONS,
						self::SET_ITEMS_BASE
					));
					break;
				case self::DAILY_TASK :
					self::executeTasks(array(
						self::UPDATE_ORDERS,
						self::UPDATE_ITEMS,
						self::UPDATE_ITEMS_PRICE_LISTS,
						self::UPDATE_ITEMS_WAREHOUSE_SETTINGS,
						self::UPDATE_ITEMS_SUPPLIERS,
						self::UPDATE_WAREHOUSE_LIST,
						self::UPDATE_CURRENT_STOCKS,
						self::UPDATE_LINKED_ITEMS,
						self::UPDATE_SALES_ORDER_REFERRER,
						self::CALCULATE_TOTAL_NETTO,
						self::CALCULATE_DAILY_NEED,
						self::CALCULATE_WRITE_BACK_DATA,
						self::CALCULATE_AMAZON_QUANTITIES,
						self::CALCULATE_AMAZON_RUNNING_COSTS,
						self::CALCULATE_WRITE_PERMISSIONS,
						self::SET_ITEMS_SUPPLIERS,
						self::SET_ITEMS_WAREHOUSE_SETTINGS,
						self::JANSEN_STOCK_UPDATE_TOTAL,
					));
					break;
				default :
					switch ($task)
					{
						case self::UPDATE_ALL :
							self::executeTasks(array(
								self::UPDATE_ORDERS,
								self::UPDATE_ITEMS,
								self::UPDATE_ITEMS_PRICE_LISTS,
								self::UPDATE_ITEMS_WAREHOUSE_SETTINGS,
								self::UPDATE_ITEMS_SUPPLIERS,
								self::UPDATE_WAREHOUSE_LIST,
								self::UPDATE_SALES_ORDER_REFERRER,
							));
							break;
						case self::CALCULATE_ALL :
							self::executeTasks(array(
								self::CALCULATE_TOTAL_NETTO,
								self::CALCULATE_DAILY_NEED,
								self::CALCULATE_WRITE_BACK_DATA,
								self::CALCULATE_WRITE_PERMISSIONS,
							));
							break;
						case self::SET_ALL :
							self::executeTasks(array(
								self::SET_ITEMS_SUPPLIERS,
								self::SET_ITEMS_WAREHOUSE_SETTINGS,
								self::UPDATE_ITEMS_PRICE_SETS,
							));
							break;
						default :
							throw new RuntimeException('Unknown task ' . $task);
					}
			}
		}
	}
}
