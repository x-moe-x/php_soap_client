<?php
require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'api/ApiHelper.class.php';

/**
 * Provides methods to manage tasks and the task queue
 */
class ApiTasks
{
	/**
	 * @var int
	 */
	const DAEMON_INTERVAL = 5;

	/**
	 * @var int
	 */
	const EXECUTION_ESTIMATE_SECONDS = 30;

	/**
	 * Updates the tasks execution timestamp
	 *
	 * @param int $id id of to be updated task
	 *
	 * @return void
	 */
	public static function updateLastExecuteTime($id)
	{
		$now = time();

		ob_start();
		DBQuery::getInstance()->insert("INSERT INTO `TaskData` (`TaskID`, `TaskLastExecutionTimestamp`)	VALUES($id, $now) ON DUPLICATE KEY UPDATE `TaskLastExecutionTimestamp` = $now");
		ob_end_clean();
	}

	/**
	 * Checks database for tasks that have been queued during last execution interval
	 *
	 * @return array Array of queued tasks
	 */
	public static function getQueuedTasks()
	{
		ob_start();
		$queuedTasksDbResult = DBQuery::getInstance()->select('SELECT tq.TaskID AS id, td.TaskName AS name FROM TaskQueue AS tq JOIN TaskDefinitions AS td ON tq.TaskID = td.TaskID ');
		ob_end_clean();

		$result = array();

		while ($currentTask = $queuedTasksDbResult->fetchAssoc())
		{
			$result[] = array(
				'id'   => $currentTask['id'],
				'name' => $currentTask['name'],
			);
		}

		return $result;
	}

	/**
	 * Checks database for tasks that are scheduled for immediate execution
	 *
	 * @return array Array of tasks scheduled for execution
	 */
	public static function getScheduledTasks()
	{
		ob_start();
		$dbQueryResult = DBQuery::getInstance()->select(self::getScheduledTasksQuery());
		ob_end_clean();

		$result = array();
		$now = new DateTime();

		// for every existing task...
		while ($currentTask = $dbQueryResult->fetchAssoc())
		{
			// ... prepare next execution base

			// deal with tasks never executed before
			if (is_null($currentTask['lastExecution']))
			{
				$currentTask['lastExecution'] = 0;
			}

			$next = new DateTime('@' . $currentTask['lastExecution']);
			$next->setTimeZone(new DateTimeZone('Europe/Berlin'));

			// ... if task is daily task ...
			if (isset($currentTask['start']))
			{
				$currentMinutes = intval($now->format('H')) * 60 + intval($now->format('i'));
				$startTime = intval($currentTask['start']);

				// ... then check if start time is reached (within confidence intervall) ...
				if ($startTime <= $currentMinutes && $currentMinutes < $startTime + 2 * self::DAEMON_INTERVAL)
				{
					// ... then adjust next execution time (so no daily task is scheduled accidentally twice or more that day)
					$next->add(new DateInterval('PT' . (2 * self::DAEMON_INTERVAL) . 'M'));
				} // ... or if start time isn't reached ...
				else
				{
					// ... then do nothing
					continue;
				}
			} // ... or if task is periodic task
			else
			{
				// ... then adjust next execution time
				$next->add(new DateInterval('PT' . $currentTask['interval'] . 'M'))->sub(new DateInterval('PT' . self::EXECUTION_ESTIMATE_SECONDS . 'S'));
			}

			// ... then check if execution is scheduled
			if ($next < $now)
			{
				// ... enqueue task
				$result[] = array(
					'id'   => $currentTask['id'],
					'name' => $currentTask['name'],
				);
			} // ... or if execution isn't scheduled
			else
			{
				// ... then do nothing
			}
		}

		return $result;
	}

	/**
	 * Generates query to obtain possibly scheduled tasks from database. Tasks can be returned that either have a given
	 * execution interval (periodic tasks) or a given start time (daily task) <b>but not both or neither</b>
	 *
	 * @return string query for scheduled tasks
	 */
	private static function getScheduledTasksQuery()
	{
		return 'SELECT
	def.TaskID AS `id`,
	def.TaskName AS `name`,
	def.TaskExecutionInterval AS `interval`,
	def.TaskExecutionStart AS `start`,
	dat.TaskLastExecutionTimestamp AS `lastExecution`
FROM
	`TaskDefinitions` AS `def`
LEFT JOIN
	`TaskData` AS `dat`
ON
	def.TaskID = dat.TaskID
WHERE
	(`TaskExecutionInterval` IS NOT NULL AND `TaskExecutionStart` IS NULL)
OR
	(`TaskExecutionInterval` IS NULL AND `TaskExecutionStart` IS NOT NULL)';
	}

	/**
	 * Enqueues a task for deferred execution. A task can be only enqueued once, successive requests are ignored
	 *
	 * @param string $task name of the task to be enqueued
	 *
	 * @return void
	 */
	public static function enqueueTask($task)
	{
		ob_start();
		DBQuery::getInstance()->insert("INSERT INTO `TaskQueue` (`TaskID`, `TaskInsertionTimestamp`) SELECT `TaskID`, UNIX_TIMESTAMP(NOW()) FROM `TaskDefinitions` WHERE `TaskName` = '$task' ON DUPLICATE KEY UPDATE `TaskInsertionTimestamp` = `TaskInsertionTimestamp`");
		ob_end_clean();
	}
}

?>
