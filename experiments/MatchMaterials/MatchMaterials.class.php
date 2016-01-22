<?php
require_once realpath(dirname(__FILE__) . '/../../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/NX_Executable.abstract.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * Class MatchMaterials
 */
class MatchMaterials extends NX_Executable
{
	/**
	 * @var array
	 */
	private $csvData;

	/**
	 * @var array
	 */
	private $propertyToItems;

	/**
	 * @return MatchMaterials
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);

		$this->csvData = array();

		$this->propertyToItems = array();
	}

	public function execute()
	{
		// read csv-file
		$this->readCsvData('amazon_material.csv');

		// check count
		$this->processData();

		$this->storeToDB();
	}

	/**
	 * @param string $filename
	 */
	private function readCsvData($filename)
	{
		// open file if possible
		if (($csvFile = fopen($filename, "r")) !== false)
		{
			// read single line
			while (($data = fgetcsv($csvFile)) !== false)
			{
				$this->csvData[] = array(
					'ItemCategoryPath' => $data[0],
					'Name'             => $data[1],
					'Count'            => $data[2],
					'Kategorie'        => $data[3],
					'Unterkategorie'   => $data[4],
					'Produkt'          => $data[5],
					'Material1'        => $data[6],
					'Material2'        => $data[7],
					'Coating'          => $data[8],
				);
			}
		} else
		{
			$this->debug(__FUNCTION__ . " could not open $filename");
		}
	}

	private function processData()
	{
		foreach ($this->csvData as $index => $itemsMaterialAssociation)
		{
			$dbResult = DBQuery::getInstance()->select('SELECT
	i.ItemID,
	p1.selectionid AS material1Selectionid,
	p2.selectionid AS material2Selectionid,
	p3.selectionid AS coatingSelectionid
FROM
	ItemsBase AS i JOIN
	ItemsCategories c
		ON i.itemid = c.itemid
	JOIN
	ItemsAvailability AS a
		ON i.itemid = a.itemid
	LEFT JOIN
	PropertyChoices AS p1
		ON p1.name = \'' .$itemsMaterialAssociation['Material1']. '\'
LEFT JOIN
PropertyChoices AS p2
ON p2.name = \'' .$itemsMaterialAssociation['Material2']. '\'
LEFT JOIN
PropertyChoices AS p3
ON p3.name = \'' .$itemsMaterialAssociation['Coating']. '\'
WHERE
c.ItemCategoryPath LIKE \'' .$itemsMaterialAssociation['ItemCategoryPath']. '%\'
AND
i.Name LIKE \'' .$itemsMaterialAssociation['Name']. '\'
AND i.Inactive = 0
AND a.Webshop = 1
AND (p1.propertyid = 385 OR p1.propertyid IS NULL)
AND (p2.propertyid = 386 OR p2.propertyid IS NULL)
AND (p3.propertyid = 387 OR p3.propertyid IS NULL)
GROUP BY i.itemid');
			if ($itemsMaterialAssociation['Count'] == -1 || $itemsMaterialAssociation['Count'] == $dbResult->getNumRows()){
				// proceed, check materials
				while ($row = $dbResult->fetchAssoc()){
					if (is_null($row['material1Selectionid'])){
						// error
						print_r($itemsMaterialAssociation);
						die('no match found for material 1');
					}
					else if (is_null($row['material2Selectionid']) && !empty($itemsMaterialAssociation['Material2'])){
						print_r($itemsMaterialAssociation);
						die('no match found for material 2');
					}
					else if (is_null($row['coatingSelectionid']) && !empty($itemsMaterialAssociation['Coating'])){
						print_r($itemsMaterialAssociation);
						die('no match found for coating description');
					}

					$this->preparePropertyChoice($row['ItemID'], 385, $row['material1Selectionid']);

					if (!is_null($row['material2Selectionid']))
					{
						$this->preparePropertyChoice($row['ItemID'], 386, $row['material2Selectionid']);
					}

					if (!is_null($row['coatingSelectionid'])){
						$this->preparePropertyChoice($row['ItemID'], 387, $row['coatingSelectionid']);
					}
				}
			} else {
				// error
				print_r($itemsMaterialAssociation);
				echo $dbResult->getNumRows() ."\n";
				die();
			}
		}
	}

	/**
	 * @param int $itemId
	 * @param int $propertyId
	 * @param int $selectionId
	 */
	private function preparePropertyChoice($itemId, $propertyId, $selectionId)
	{
		$this->propertyToItems[] = array(
			'ItemId'            => $itemId,
			'Lang'              => 'de',
			'PropertyId'        => $propertyId,
			'PropertyItemValue' => $selectionId,
		);
	}

	private function storeToDB()
	{
		DBQuery::getInstance()->insert('INSERT INTO SetPropertiesToItem'.DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->propertyToItems));
	}
}

// execute single instance
(new MatchMaterials())->execute();