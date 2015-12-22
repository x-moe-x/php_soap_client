<?php
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/NX_Executable.abstract.php';
require_once ROOT . 'includes/DBUtils2.class.php';

class ExtractProperties extends NX_Executable
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

		while ($currentItem = $dbResult->fetchAssoc())
		{
			$htmlString = mb_convert_encoding(preg_replace(["/\\\\n\\s*|\\t/"], [''], $currentItem['TechnicalData']), 'HTML-ENTITIES', 'UTF-8');
			$dom = new DOMDocument();
			$dom->loadHtml($htmlString);

			/** @var DOMNodeList $dlNodes */
			$dlNodes = $dom->getElementsByTagName('dl');

			// extract all key/value pairs
			if ($dlNodes->length === 1 && $dlNodes->item(0)->hasChildNodes())
			{
				$nextIndex = 0;
				$keyValuePairs = array();

				/** @var DOMNode $innerNode */
				foreach ($dlNodes->item(0)->childNodes as $innerNode)
				{
					switch ($innerNode->nodeName)
					{
						case 'dt':
							$keyValuePairs[$nextIndex++] = array(
								'ItemId' => intval($currentItem['ItemID']),
								'Key'    => $innerNode->textContent,
								'Value'  => null
							);
							break;
						case 'dd':
							if (isset($keyValuePairs[$nextIndex - 1]) && is_null($keyValuePairs[$nextIndex - 1]['Value']))
							{
								$keyValuePairs[$nextIndex - 1]['Value'] = $innerNode->textContent;
							} else
							{
								throw new RuntimeException('no dt for current dd found or there\'s already a dd');
							}
							break;
						default:
							throw new RuntimeException('unexpected element traversing dl: ' . $innerNode->nodeName . ': ' . $innerNode->textContent);
					}

				}
				$this->storeData = array_merge($this->storeData, array_values($keyValuePairs));
			}
		}

		// store them unclustered in db
		$this->storeToDB();
	}

	private function storeToDB()
	{
		$countRecords = count($this->storeData);
		if ($countRecords > 0)
		{
			DBQuery::getInstance()->insert('INSERT INTO ExtractedProperties' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->storeData));
			$this->debug(__FUNCTION__ . " storing $countRecords records of item properties key/value data");
		}
	}
}
