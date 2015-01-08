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
		 `TaskExecutionInterval` IS NOT NULL AND
		 `TaskExecutionStart` IS NOT NULL ');
		ob_end_clean();

		$result = array();
		$now = new DateTime();
		while ($currentTask = $dbQueryResult -> fetchAssoc()) {
			$currentMinutes = intval($now -> format('H')) * 60 + intval($now -> format('i'));
			$startTime = intval($currentTask['start']);

			if ($startTime <= $currentMinutes && $currentMinutes < $startTime + 2 * self::DAEMON_INTERVAL) {
				// start time reached (within confidence intervall)
				echo "start time reached \n";

				$last = new DateTime('@' . $currentTask['lastExecution']);
				$last -> setTimeZone(new DateTimeZone('Europe/Berlin'));
				$last -> add(new DateInterval('PT' . $currentTask['interval'] . 'M')) -> sub(new DateInterval('PT' . (2 * self::DAEMON_INTERVAL) . 'M'));

				if ($last < $now) {
					// execute
					echo "execute \n";
					$result[] = $currentTask;
				} else {
					echo "don't execute \n";
					// don't execute
				}
			} else {
				// start time not yet reached
				echo "start time not yet reached \n";
			}
		}
		return $result;
	}
}
?>
