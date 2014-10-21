<?php
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/DBLastUpdate.php';
require_once ROOT . 'includes/EanGenerator.class.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * @author x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class JansenStockImport {

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
	 * @return JansenStockImport
	 */
	public function __construct() {
		$this -> identifier4Logger = __CLASS__;

		$this -> csvFilePath = '/kunden/homepages/22/d66025481/htdocs/stock_jd/stock.csv';

		$this -> aDBData = array();
	}

	/**
	 * @return void
	 */
	public function execute() {

		list($lastUpdate, , ) = lastUpdateStart(__CLASS__);
		$currentTime = filemtime($this -> csvFilePath);

		// if file modifikation date younger than last import ...
		if ($currentTime > $lastUpdate) {

			// ... then read the file ...
			$csvFile = fopen($this -> csvFilePath, 'r');
			if ($csvFile) {
				// ... for every line ...
				while (($csvData = fgetcsv($csvFile, 1000, ';')) !== false) {
					// ... eliminate dummy fields ...
					if (count($csvData) === 3) {
						// ... check ean
						if (EanGenerator::valid($csvData[2])) {
							// ... then store record
							$externalItemID = iconv("Windows-1250", "UTF-8", $csvData[0]);
							$this -> aDBData[] = array('EAN' => $csvData[2], 'ExternalItemID' => $externalItemID, 'PhysicalStock' => floatval($csvData[1]));
						} else {
							// ... otherwise display error
							$this -> getLogger() -> debug(__FUNCTION__ . " EAN invald for article: $externalItemID, " . (empty($csvData[2]) ? 'no EAN' : $csvData[2]));
						}
					}
				}
				// ... then persistenly store all records in db
				$this -> storeToDB();
				lastUpdateFinish($currentTime, __CLASS__);
			} else {
				//... or error
				$this -> getLogger() -> debug(__FUNCTION__ . ' unable to read file ' . $this -> csvFilePath);
			}
			fclose($csvFile);
		} else {
			$this -> getLogger() -> debug(__FUNCTION__ . " no new data");
		}
	}

	private function storeToDB() {
		// delete old data
		DBQuery::getInstance() -> truncate("TRUNCATE JansenStockData");

		DBQuery::getInstance() -> insert("INSERT INTO JansenStockData" . DBUtils::buildMultipleInsert($this -> aDBData));
	}

	/**
	 *
	 * @return Logger
	 */
	protected function getLogger() {
		return Logger::instance($this -> identifier4Logger);
	}

}
?>
