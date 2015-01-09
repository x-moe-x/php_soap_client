<?php
require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'api/ApiHelper.class.php';

class ApiTasks {

	/** @var int */
	const DAEMON_INTERVAL = 5;

	/**
	 * @param int $id TaskID to be updated
	 * @return void
	 */
	public static function updateLastExecuteTime($id) {
		ob_start();
		$now = time();
		DBQuery::getInstance() -> insert("INSERT INTO
	`TaskData`
	(`TaskID`, `TaskLastExecutionTimestamp`)
	VALUES($id, $now)
ON DUPLICATE KEY UPDATE
	`TaskLastExecutionTimestamp` = $now");
		ob_end_clean();
	}

	/**
	 * @return array Array of all tasks, scheduled for immediate execution
	 */
	public static function getScheduledTasks() {
		ob_start();
		$dbQueryResult = DBQuery::getInstance() -> select('SELECT
		 def.TaskID as `id`,
		 def.TaskName as `name`,
		 def.TaskExecutionInterval as `interval`,
		 def.TaskExecutionStart as `start`,
		 dat.TaskLastExecutionTimestamp as `lastExecution`
		 FROM
		 `TaskDefinitions` as `def`
		 LEFT JOIN
		 `TaskData` as `dat`
		 ON
		 def.TaskID = dat.TaskID
		 WHERE
	(`TaskExecutionInterval` IS NOT NULL AND `TaskExecutionStart` IS NULL)
OR
	(`TaskExecutionInterval` IS NULL AND `TaskExecutionStart` IS NOT NULL)');
		ob_end_clean();

		$result = array();
		$now = new DateTime();

		// for every existing task...
		while ($currentTask = $dbQueryResult -> fetchAssoc()) {
			// ... prepare next execution base
			$next = new DateTime('@' . $currentTask['lastExecution']);
			$next -> setTimeZone(new DateTimeZone('Europe/Berlin'));

			// ... if task is daily task ...
			if (isset($currentTask['start'])) {
				$currentMinutes = intval($now -> format('H')) * 60 + intval($now -> format('i'));
				$startTime = intval($currentTask['start']);

				// ... then check if start time is reached (within confidence intervall) ...
				if ($startTime <= $currentMinutes && $currentMinutes < $startTime + 2 * self::DAEMON_INTERVAL) {
					// ... then adjust next execution time (so no daily task is scheduled accidentally twice or more that day)
					$next -> add(new DateInterval('PT' . (2 * self::DAEMON_INTERVAL) . 'M'));
				}
				// ... or if start time isn't reached ...
				else {
					// ... then do nothing
					continue;
				}
			}
			// ... or if task is periodic task
			else {
				// ... then adjust next execution time
				$next -> add(new DateInterval('PT' . $currentTask['interval'] . 'M'));
			}

			// ... then check if execution is scheduled
			if ($next < $now) {
				// ... enqueue task
				$result[] = $currentTask;
			}
			// ... or if execution isn't scheduled
			else {
				// ... then do nothing
			}
		}
		return $result;
	}

}
?>
