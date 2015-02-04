<?php
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/DBUtils2.class.php';


class PrepareGlossarUpdate
{

	/**
	 * @var array
	 */
	private $aGlossarPageCategoryIDs;

	private $aGlossarPages;

	/**
	 * @return PrepareGlossarUpdate
	 */
	public function __construct()
	{
		$this->aGlossarPageCategoryIDs = array(
			38,
			39,
			40,
			41,
			42,
			43,
			44,
			45,
			46,
			47,
			49,
			51,
			52,
			53,
			55,
			57,
			58,
			59,
			60,
			61,
			62,
			63,
			64,
			65,
			66,
			67,
			68,
			69,
			70,
			72,
			73,
			74,
			75,
			77,
			78,
			79,
			80,
			81,
			82,
			83,
			84,
			89,
			90,
			91,
			92,
			93,
			94,
			95,
			96,
			97,
			99,
			100,
			101,
			102,
			103,
			104,
			105,
			106,
			107,
			108,
			109,
			110,
			112,
			113,
			114,
			115,
			116,
			117,
			118,
			119,
			120,
			121,
			122,
			123,
			124,
			125,
			126,
			127,
			128,
			129,
			130,
			131,
			132,
			133,
			134,
			135,
			136,
			137,
			138,
			139,
			140,
			141,
			142,
			143,
			144,
			145,
			146,
			147,
			148,
			173,
			174,
			175,
			176,
			177,
			178,
			179,
			180,
			181,
			182,
			183,
			184,
			185,
			186,
			187,
			188,
			189,
			190,
			194,
			195,
			196,
			197,
			198,
			199,
			200,
			201,
			202,
			203,
			204,
			205,
			206,
			207,
			208,
			209,
			210,
			211,
			212,
			213,
			214,
			215,
			216,
			217,
			218,
			219,
			220,
			221,
			222,
			223,
			224,
			225,
			226,
			227,
			228,
			229,
			230,
			231,
			232,
			233,
			234,
			235,
		);

		$this->aGlossarPages = array();
	}

	/**
	 *
	 */
	public function execute()
	{
		// get all desired content pages
		$getGlossarPagesDBResult = DBQuery::getInstance()->select('SELECT * FROM ContentPages WHERE CategoryID IN (' . implode(',', $this->aGlossarPageCategoryIDs) . ')');

		while ($glossarPage = $getGlossarPagesDBResult->fetchAssoc())
		{
			$this->aGlossarPages[] = $glossarPage;
		}

		// put them into SetContentPage-Table
		$this->storeToDB();
	}

	private function storeToDB()
	{
		$countGlossarPages = count($this->aGlossarPages);

		if ($countGlossarPages > 0)
		{
			$this->debug(__FUNCTION__ . " prepare $countGlossarPages glossar pages for update");

			DBQuery::getInstance()->insert('INSERT INTO SetContentPage' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->aGlossarPages));
		}
	}

	/**
	 * Writes $message to screen and log
	 *
	 * @param string $message the message to be logged
	 */
	private function debug($message)
	{
		Logger::instance(__CLASS__)->debug($message);
	}
}
