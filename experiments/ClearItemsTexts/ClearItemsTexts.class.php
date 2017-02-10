<?php
require_once ROOT . 'includes/NX_Executable.abstract.php';
require_once ROOT . 'includes/DBUtils2.class.php';
require_once ROOT . 'lib/db/DBQuery.class.php';

class ClearItemsTexts extends NX_Executable
{
	private $clearedItemsTexts;
	private $clearedItemsFreeTexts;

	public function __construct()
	{
		parent::__construct(__CLASS__);

		$this->clearedItemsTexts = array();
		$this->clearedItemsFreeTexts = array();
	}

	public function execute()
	{
		// get all items texts
		$dbResult = DBQuery::getInstance()
			->select('SELECT ItemID, Name, Name2, Name3, ShortDescription, LongDescription, MetaDescription, TechnicalData, Free8  FROM ItemsBase');

		// transform them
		while ($itemsTexts = $dbResult->fetchAssoc())
		{
			$this->transform($itemsTexts);
		}

		$this->storeToDb();

	}

	private function storeToDb()
	{
		$countTexts = count($this->clearedItemsTexts);
		$countFreeTexts = count($this->clearedItemsFreeTexts);

		if ($countTexts > 0)
		{
			$this->debug(__FUNCTION__ . " storing $countTexts records of items texts to db");
			DBQuery::getInstance()
				->truncate('TRUNCATE SetItemsTexts');
			DBQuery::getInstance()
				->insert('INSERT INTO SetItemsTexts' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->clearedItemsTexts));
		}

		if ($countFreeTexts > 0)
		{
			$this->debug(__FUNCTION__ . " storing $countFreeTexts records of items free texts to db");
			DBQuery::getInstance()
				->truncate('TRUNCATE SetItemsFreeTextFields');
			DBQuery::getInstance()
				->insert('INSERT INTO SetItemsFreeTextFields' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->clearedItemsFreeTexts));
		}
	}

	private function transform($itemsTexts)
	{
		foreach ($itemsTexts as $key => $value)
		{
			switch ($key)
			{
				case 'Name':
				case 'Name2':
				case 'Name3':
				case 'ShortDescription':
				case 'LongDescription':
				case 'MetaDescription':
				case 'TechnicalData':
				case 'Free8':
					$this->transformText($itemsTexts['ItemID'], $key, $value);
			}
		}
	}

	private function transformText($itemId, $key, $value)
	{
		// create clean data
		$data = array(
			'ItemID' => $itemId,
			$key     => preg_replace('/\\\\n|\\\\\\\\n/', "\n", $value)
		);

		// skip if text was not modified
		if ($data[$key] == $value)
		{
			return;
		}

		// store modified texts
		if ($key == 'Free8')
		{
			$this->clearedItemsFreeTexts[] = $data;
		} else
		{
			if (!array_key_exists($itemId, $this->clearedItemsTexts))
			{
				$this->clearedItemsTexts[$itemId] = $data;
			} else
			{
				$this->clearedItemsTexts[$itemId] = array_merge($this->clearedItemsTexts[$itemId], $data);
			}
		}
	}
}