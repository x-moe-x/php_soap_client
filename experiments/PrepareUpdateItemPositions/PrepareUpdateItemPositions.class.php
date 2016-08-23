<?php
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/NX_Executable.abstract.php';

class PrepareUpdateItemPositions extends NX_Executable
{
	/**
	 * @var int
	 */
	const MIN_POSITION = 100;

	/**
	 * @var int
	 */
	const POSITION_INTERVAL = 10;

	public function execute()
	{
		$this->debug(__CLASS__ . ' preparing item position update ...');
		DBQuery::getInstance()->truncate(/** @lang SQL */
			'TRUNCATE SetItemsBase');
		DBQuery::getInstance()->set(/** @lang SQL */
			"SET @row_number = " . (self::MIN_POSITION - self::POSITION_INTERVAL)
		);
		$affectedRows = DBQuery::getInstance()->insert($this->getQuery());
		$this->debug(__CLASS__ . ' prepared item position updates for ' . $affectedRows . ' items');
	}

	private function getQuery()
	{
		return "INSERT INTO SetItemsBase (ItemId, Others_Position)
SELECT
	i.ItemID,
	(@row_number:=@row_number + " . self::POSITION_INTERVAL . ") AS Others_Position
FROM (
	SELECT
		i.ItemId,
		avg(c.DailyNeed) AS DailyNeed
	FROM
		ItemsBase AS i

	LEFT JOIN
		CalculatedDailyNeeds AS c
	ON
		i.ItemId = c.ItemID
	GROUP BY
		i.ItemId
	ORDER BY
		avg(c.DailyNeed) DESC
) AS i";
	}
}