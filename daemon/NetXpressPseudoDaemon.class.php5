<?php
require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/soap/experiment_loader/NetXpressSoapExperimentLoader.class.php';
require_once ROOT . 'api/ApiTasks.class.php';
require_once ROOT . 'api/ApiExecute.class.php';
require_once ROOT . 'includes/FileLock.class.php';

/**
 * Executes tasks that are scheduled for execution. Provides daemon functionality if executed repeatedly (eg. when
 * configured as a cron job). Operates on FileLock to prevent concurrent execution
 */
class NetXpressPseudoDaemon
{

	/**
	 * @var NetXpressPseudoDaemon
	 */
	private static $instance = null;
	/**
	 * @var FileLock
	 */
	private $executionLock = null;
	/**
	 * @var FileLock
	 */
	private $dbQueueLock = null;
	/**
	 * @var array
	 */
	private $aRunningQueue = null;

	/**
	 * @return NetXpressPseudoDaemon
	 */
	private function __construct()
	{
		$this->executionLock = new FileLock();
		$this->dbQueueLock = new FileLock();

		$this->aRunningQueue = array();
	}

	/**
	 * Give access to the NetXpressPseudoDaemon instance
	 *
	 * @return NetXpressPseudoDaemon
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance) || !(self::$instance instanceof NetXpressPseudoDaemon))
		{
			self::$instance = new NetXpressPseudoDaemon();
		}

		return self::$instance;
	}

	/**
	 * Main loop of NetXpressPseudoDaemon. As it's just a pseudo daemon it only runs once per execution.
	 *
	 * @return void
	 */
	public function run()
	{
		$this->debug(__FUNCTION__, 'Starting pseudo daemon...');
		$this->executionLock->init(ROOT . '/tmp/execution.Lock');
		$this->dbQueueLock->init(ROOT . '/tmp/dbQueue.Lock');

		// if execution lock is aquired ...
		if ($this->executionLock->tryLock())
		{

			// ... then wait till dbQueueLock has been aquired
			if ($this->dbQueueLock->lock())
			{

				$this->obtainOutOfSequenceTasks();

				$this->dbQueueLock->unlock();

				$this->obtainScheduledTasks();

				$this->processRunningQueue();
			} else
			{
				$this->debug(__FUNCTION__, 'dbQueueLock not acquired. Skipping this turn.');
			}
			$this->executionLock->unlock();
		} else
		{
			// ... otherwise skip this turn
			$this->debug(__FUNCTION__, 'executionLock not acquired after given amount of time. Skipping this turn.');
		}
		$this->executionLock->discard();
		$this->dbQueueLock->discard();
	}

	/**
	 * Writes debug message to log and to screen
	 *
	 * @param string $function function name
	 * @param string $message  message to log
	 */
	private function debug($function, $message)
	{
		Logger::instance(__CLASS__)->debug($function . ': ' . $message);
	}

	private function obtainOutOfSequenceTasks()
	{
		$this->aRunningQueue = array_merge($this->aRunningQueue, ApiTasks::getQueuedTasks());
	}

	/**
	 * adds all tasks that are scheduled for immediate execution to the running queue
	 *
	 * @return void
	 */
	private function obtainScheduledTasks()
	{
		$this->aRunningQueue = array_merge($this->aRunningQueue, ApiTasks::getScheduledTasks());
	}

	/**
	 * Executes every task currently in the running queue, then updates the tasks last execution time
	 *
	 * @return void
	 */
	private function processRunningQueue()
	{
		foreach ($this->aRunningQueue as $currentTask)
		{
			$this->debug(__FUNCTION__, 'Executing ' . $currentTask['name']);

			ApiExecute::executeTasks($currentTask['name']);
			ApiTasks::updateLastExecuteTime($currentTask['id']);
		}
	}
}

NetXpressPseudoDaemon::getInstance()->run();
?>
