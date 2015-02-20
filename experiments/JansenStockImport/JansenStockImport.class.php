<?php
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/DBLastUpdate.php';
require_once ROOT . 'includes/EanGenerator.class.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * @author    x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class JansenStockImport
{
	/**
	 * @var string
	 */
	private $identifier4Logger;

	/**
	 * @var string
	 */
	private $csvFilePath;

	/**
	 * @var array
	 */
	private $aDBData;

	/**
	 * @var array
	 */
	private $aDBDifferenceData;

	/**
	 * @var int
	 */
	private $currentTime;

	/**
	 * @return JansenStockImport
	 */
	public function __construct()
	{
		$this->identifier4Logger = __CLASS__;

		$this->csvFilePath = '/kunden/homepages/22/d66025481/htdocs/stock_jd/stock.csv';

		$this->aDBData = array();
		$this->aDBDifferenceData = array();
	}

	/**
	 * @return void
	 */
	public function execute()
	{

		list($lastUpdate, ,) = lastUpdateStart(__CLASS__);
		$this->currentTime = filemtime($this->csvFilePath);

		// if file modifikation date younger than last import ...
		if ($this->currentTime > $lastUpdate)
		{

			// ... then read the file ...
			$csvFile = fopen($this->csvFilePath, 'r');
			if ($csvFile)
			{
				// ... for every line ...
				while (($csvData = fgetcsv($csvFile, 1000, ';')) !== false)
				{
					// ... eliminate dummy fields ...
					if (count($csvData) === 3)
					{
						$externalItemID = iconv("Windows-1250", "UTF-8", $csvData[0]);

						// ... check ean for validity and for jansen origin
						if (EanGenerator::valid($csvData[2]) && strpos($csvData[2], '85955783') === 0)
						{
							// ... then store record
							$this->aDBData[] = array(
								'EAN'            => $csvData[2],
								'ExternalItemID' => $externalItemID,
								'PhysicalStock'  => floatval($csvData[1])
							);
						} else
						{
							if (empty($csvData[2]))
							{
								//TODO check if this is a good idea: omit missing EAN messages to save log file space?
								//$this -> getLogger() -> debug(__FUNCTION__ . " EAN missing for article: $externalItemID");
							} else
							{
								// ... otherwise display error
								$this->getLogger()->debug(__FUNCTION__ . " EAN invald for article: $externalItemID, " . (empty($csvData[2]) ? 'no EAN' : $csvData[2]));
							}
						}
					}
				}
				// ... then persistenly store all records in db
				$this->storeToDB();
				lastUpdateFinish($this->currentTime, __CLASS__);
			} else
			{
				//... or error
				$this->getLogger()->debug(__FUNCTION__ . ' unable to read file ' . $this->csvFilePath);
			}
			fclose($csvFile);
		} else
		{
			$this->getLogger()->debug(__FUNCTION__ . " no new data");
		}
	}

	/**
	 *
	 * @return Logger
	 */
	protected function getLogger()
	{
		return Logger::instance($this->identifier4Logger);
	}

	private function storeToDB()
	{
		$recordCount = count($this->aDBData);

		if ($recordCount > 0)
		{

			$this->getLogger()->debug(__FUNCTION__ . " storing $recordCount stock records from jansen");

			$this->generateDifferenceSet();

			$differenceCount = count($this->aDBDifferenceData);

			if ($differenceCount > 0)
			{
				$this->getLogger()->debug(__FUNCTION__ . " storing $differenceCount difference records from jansen");

				//@formatter:off
				DBQuery::getInstance()->insert('INSERT INTO JansenTransactionHead' . DBUtils::buildInsert(array(
						'TransactionID' => null,
						'Timestamp'     => $this->currentTime
					)));
				//@formatter:on

				$transactionID = DBQuery::getInstance()->getInsertId();

				DBQuery::getInstance()->insert("INSERT INTO JansenTransactionItem" . DBUtils::buildMultipleInsert(array_map(function ($row) use ($transactionID)
					{
						$row['TransactionID'] = $transactionID;

						return $row;
					}, $this->aDBDifferenceData)));
			}

			// delete old data
			DBQuery::getInstance()->truncate("TRUNCATE JansenStockData");

			DBQuery::getInstance()->insert("INSERT INTO JansenStockData" . DBUtils::buildMultipleInsert($this->aDBData));
		}
	}

	private function generateDifferenceSet()
	{
		DBQuery::getInstance()->create("CREATE TEMPORARY TABLE `JansenStockDataNew` (
  `EAN`            BIGINT(13) NOT NULL,
  `ExternalItemID` VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `PhysicalStock`  DECIMAL(10, 4) DEFAULT NULL,
  PRIMARY KEY (`EAN`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COLLATE =utf8_unicode_ci");

		DBQuery::getInstance()->insert("INSERT INTO JansenStockDataNew" . DBUtils::buildMultipleInsert($this->aDBData));

		$dbResult = DBQuery::getInstance()->select("SELECT
  o.EAN,
  o.ExternalItemID,
  n.PhysicalStock - o.PhysicalStock AS Difference
FROM
  JansenStockData AS o
  LEFT JOIN
  JansenStockDataNew AS n
    ON
      o.EAN = n.EAN
      AND
      o.ExternalItemID = n.ExternalItemID
WHERE
  n.PhysicalStock - o.PhysicalStock != 0
");
		while ($row = $dbResult->fetchAssoc())
		{
			$this->aDBDifferenceData[] = $row;
		}

		DBQuery::getInstance()->drop("DROP TABLE JansenStockDataNew");
	}

}
