<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(-1);

require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/soap/experiment_loader/NetXpressSoapExperimentLoader.class.php';
require_once ROOT . 'api/ApiTasks.class.php';
require_once ROOT . 'includes/FileLock.class.php';

class NetXpressPseudoDaemon {

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
	 * @var NetXpressPseudoDaemon
	 */
	private static $instance = null;

	private function __construct() {
		$this -> executionLock = new FileLock();
		$this -> dbQueueLock = new FileLock();

		$this -> aRunningQueue = array();
	}

	public static function getInstance() {
		if (!isset(self::$instance) || !(self::$instance instanceof NetXpressPseudoDaemon)) {
			self::$instance = new NetXpressPseudoDaemon();
		}

		return self::$instance;
	}

	private function obtainOutOfSequenceTasks() {
		// for all tasks currently in db-queue:
		// ... insert into running-queue
	}

	private function obtainScheduledTasks() {
		// for all existing tasks:
		// ... if is scheduled for execution ...
		// ... ... then insert into running-queue
		$this -> aRunningQueue = array_merge($this -> aRunningQueue, ApiTasks::getScheduledTasks());
	}

	private function processRunningQueue() {
		// for all tasks in running-queue:
		foreach ($this->aRunningQueue as $currentTask) {
			// ... perform task
			$this -> debug('Pretend to execute Task: ' . $currentTask['name']);
			// ... ... update task-data
			ApiTasks::updateLastExecuteTime($currentTask['id']);
		}
	}

	public function run() {
		$this -> debug('Starting pseudo daemon...');
		$this -> executionLock -> init(ROOT . '/tmp/execution.Lock');
		$this -> dbQueueLock -> init(ROOT . '/tmp/dbQueue.Lock');

		// if execution lock is aquired ...
		if ($this -> executionLock -> tryLock()) {
			$this -> debug('executionLock acquired.');

			// ... then wait till dbQueueLock has been aquired
			$this -> debug('waiting for dbQueueLock ...');
			if ($this -> dbQueueLock -> lock()) {

				$this -> debug('dbQueueLock acquired.');

				$this -> obtainOutOfSequenceTasks();

				$this -> dbQueueLock -> unlock();
				$this -> debug('dbQueueLock released.');

				$this -> obtainScheduledTasks();

				$this -> processRunningQueue();
			} else {
				$this -> debug('dbQueueLock not acquired after given amount of time. Skipping this turn.');
			}
			$this -> executionLock -> unlock();
			$this -> debug('executionLock released.');
		} else {
			// ... otherwise skip this turn
			$this -> debug('executionLock not acquired. Skipping this turn.');
		}
		$this -> debug('executionLock and dbQueueLock discarded.');
		$this -> executionLock -> discard();
		$this -> dbQueueLock -> discard();
	}

	private function debug($message) {
		Logger::instance('NetXpressPseudoDaemon') -> debug($message);
	}

}

NetXpressPseudoDaemon::getInstance() -> run();
?>
