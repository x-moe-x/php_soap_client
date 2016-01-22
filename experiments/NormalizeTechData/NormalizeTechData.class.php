<?php
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/NX_Executable.abstract.php';
require_once ROOT . 'includes/DBUtils2.class.php';

class NormalizeTechData extends NX_Executable
{

	private $storeData;

	public function __construct()
	{
		parent::__construct(__CLASS__);

		$this->storeData = array();
	}

	public function execute()
	{
		// load all tech data records
		$dbResult = DBQuery::getInstance()->select('SELECT ItemID, TechnicalData FROM `ItemsBase`');

		$nrOfOversizedItems = 0;

		while ($currentItem = $dbResult->fetchAssoc())
		{
			$htmlString = mb_convert_encoding(preg_replace(["/\\\\n\\s*|\\t/"], [''], $currentItem['TechnicalData']), 'HTML-ENTITIES', 'UTF-8');
			$dom = new DOMDocument();
			$dom->loadHtml($htmlString);

			/** @var DOMNodeList $dlNodes */
			$dlNodes = $dom->getElementsByTagName('dl');

			$currentTechData = array(
				'ItemID'        => intval($currentItem['ItemID']),
				'TechnicalData' => '',
			);
			$nrOfProperties = 0;

			// extract all key/value pairs
			if ($dlNodes->length === 1 && $dlNodes->item(0)->hasChildNodes())
			{
				$lastKey = null;

				/** @var DOMNode $innerNode */
				foreach ($dlNodes->item(0)->childNodes as $innerNode)
				{
					switch ($innerNode->nodeName)
					{
						case 'dt':
							$lastKey = $innerNode->textContent;
							break;
						case 'dd':
							if (!is_null($lastKey))
							{
								$currentTechData['TechnicalData'] .= "$lastKey: " . $innerNode->textContent . "\n";
								$nrOfProperties++;
							} else
							{
								throw new RuntimeException('no dt for current dd found or there\'s already a dd');
							}
							// reset last key
							$lastKey = null;
							break;
						default:
							throw new RuntimeException('unexpected element traversing dl: ' . $innerNode->nodeName . ': ' . $innerNode->textContent);
					}

				}
				// remove trailing newline
				$currentTechData['TechnicalData'] = rtrim($currentTechData['TechnicalData']);
			}
			$this->storeData[] = $currentTechData;

			if ($nrOfProperties > 5)
			{
				$nrOfOversizedItems++;
				echo 'Item ' . $currentTechData['ItemID'] . " has more than 5 properties\n";
				echo $currentTechData['TechnicalData'] . "\n\n";
			}
		}

		echo "$nrOfOversizedItems items are 'oversized' for amazon\n";

		// store them unclustered in db
		//TODO remove this after debugging
		//$this->storeToDB();
	}

	private function storeToDB()
	{
		$countRecords = count($this->storeData);
		if ($countRecords > 0)
		{
			DBQuery::getInstance()->insert('INSERT INTO SetItemsTexts' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->storeData));
			$this->debug(__FUNCTION__ . " storing $countRecords records of item properties key/value data");
		}
	}
}
