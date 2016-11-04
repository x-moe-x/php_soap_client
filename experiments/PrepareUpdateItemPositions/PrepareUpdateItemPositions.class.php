<?php
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/NX_Executable.abstract.php';

class PrepareUpdateItemPositions extends NX_Executable
{

	/**
	 * PrepareUpdateItemPositions constructor.
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);
	}

	public function execute()
	{
		$this->debug(__CLASS__ . ' preparing item position update ...');
		DBQuery::getInstance()->truncate("TRUNCATE SetItemsBase");

		$dbMetaResult = DBQuery::getInstance()->select($this->getMetaQuery());

		//$max = $dbMetaResult->getNumRows();
		//$normalizer = $dbMetaResult->fetchAssoc()['DailyNeed'];

		// use algorithm that's not dependent on a rapidly changing normalization value
		// so positions are closer together and therefore don't change so often
		$max = $dbMetaResult->getNumRows() / 2;
		$normalizer = 2000;

		$affectedRows = DBQuery::getInstance()->insert($this->getQuery($max, $normalizer));
		$this->debug(__CLASS__ . ' prepared item position updates for ' . $affectedRows . ' items');
	}

	/**
	 * Set normalize Others_Position to [0, $max]
	 *
	 * @param int   $max
	 * @param float $normalizer
	 *
	 * @return string query
	 */
	private function getQuery($max, $normalizer)
	{
		return "INSERT INTO SetItemsBase (ItemId, Others_Position)
  SELECT
    i.ItemID,
    ((1 - i.DailyNeed / $normalizer) * $max) AS Others_Position
  FROM (
         SELECT
           i.ItemId,
           IF(c.DailyNeed IS NULL, 0, avg(c.DailyNeed)) AS DailyNeed,
           i.Others_Position
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
       ) AS i
WHERE CAST(((1 - i.DailyNeed / $normalizer) * $max) AS UNSIGNED) != i.Others_Position";
	}

	/**
	 * query to obtain $max and $normalize
	 * @return string
	 */
	private function getMetaQuery()
	{
		return "SELECT
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
		avg(c.DailyNeed) DESC";
	}
}