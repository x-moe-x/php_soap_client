<?php

require_once ROOT . 'includes/NX_Executable.abstract.php';
require_once ROOT . 'includes/DBUtils2.class.php';
require_once ROOT . 'lib/db/DBQuery.class.php';

/**
 * Class Histogram
 */
class Histogram
{
	/**
	 * @var array
	 */
	private $data;

	/**
	 * Histogram constructor.
	 */
	public function __construct()
	{
		$this->data = [];
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function push($key, $value, $itemId)
	{
		// create new slot if necessary
		if (!array_key_exists($key, $this->data))
		{
			$this->data[$key] = [];
		}

		// create new slot[slot] if necessary
		if (!array_key_exists($value, $this->data[$key]))
		{
			$this->data[$key][$value] = [];
		}

		$this->data[$key][$value][] = $itemId;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		ksort($this->data);

		$result = "";
		foreach ($this->data as $key => $values)
		{
			if (strpos($key, 'Motiv') === false)
			{
				//	continue;
			}

			$count = 0;
			foreach ($values as $value => $itemIds)
			{
				$count += count($itemIds);
			}
			$result .= $key . ": $count\n";
			//$result .= print_r($values, true);
		}
		return $result;
	}

	/**
	 * @return string
	 */
	public function toString()
	{
		$sortedData = [];


		foreach ($this->data as $key => $values)
		{
			$count = 0;
			foreach ($values as $value => $itemIds)
			{
				$count += count($itemIds);
			}
			$sortedData[$key] = $count;

		}

		arsort($sortedData);

		$result = "";
		foreach ($sortedData as $key => $value)
		{
			$result .= $key . ": $value\n";
			//$result .= print_r($values, true);
		}

		return $result;
	}
}

/**
 * Class GetTechdataHistogram
 */
class GetTechdataHistogram extends NX_Executable
{


	/**
	 * @var Histogram
	 */
	private $histogram;

	public function __construct()
	{
		parent::__construct(__CLASS__);

		$this->histogram = new Histogram();
	}

	public function execute()
	{
		$itemsTextsDbResult = DBQuery::getInstance()
			->select("SELECT ItemID, TechnicalData FROM ItemsTexts");

		while ($item = $itemsTextsDbResult->fetchAssoc())
		{
			// skip items with no techdata
			if (empty($item["TechnicalData"]))
			{
				continue;
			}

			$techDataRows = explode("\n", $item["TechnicalData"]);
			foreach ($techDataRows as $techDataRow)
			{
				if (preg_match('/(?<key>.+):\s*(?<value>.+)/u', $techDataRow, $matches) === false)
				{
					echo "Item " . $item["ItemID"] . "'s techdata did not conform to necessary format: " . $techDataRow . "\n";
				}

				if ($matches['key'] == "Einlegermaß")
				{
					//die($item["ItemID"] . "\n");
				}

				$this->histogram->push($matches['key'], $matches['value'], $item["ItemID"]);
			}

		}
		echo $this->histogram->__toString();
	}
}