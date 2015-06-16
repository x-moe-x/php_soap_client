<?php
require_once realpath(dirname(__FILE__) . '/../../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * Class GenerateFormatMass
 */
class AssignFormatMass
{
	/**
	 * @var array
	 */
	private $propertyToItemData;

	/**
	 * @return AssignFormatMass
	 */
	public function __construct()
	{
		$this->propertyToItemData = array();

		DBQuery::getInstance()->Set('SET SESSION group_concat_max_len = 8192');
	}

	/**
	 *
	 */
	public function execute()
	{
		$dbResult = DBQuery::getInstance()->select($this->getQuery());

		while ($rawData = $dbResult->fetchAssoc())
		{
			$itemIDs = explode(',', $rawData['ItemIDs']);

			foreach ($itemIDs AS $itemID)
			{
				$this->propertyToItemData[] = array(
					'ItemId'            => $itemID,
					'Lang'              => 'de',
					'PropertyId'        => $rawData['PropertyID'],
					'PropertyItemValue' => null,
				);
			}
		}

		$this->storeToDB();
	}

	/**
	 * @return string
	 */
	private function getQuery()
	{
		return 'SELECT
	p.PropertyID,
	x.Value,
	x.ItemIDs
FROM
	(
		SELECT
			CASE WHEN (siblingData.SiblingRecordID IS NULL) THEN
				original.Key
			ELSE
				siblingData.Key
			END AS `Key`,
			CASE WHEN (siblingData.SiblingRecordID IS NULL) THEN
				original.Value
			WHEN (siblingData.SiblingType = 0) THEN
				siblingData.Value
			ELSE
				original.Value
			END AS `Value`,
			GROUP_CONCAT(original.ItemIDs SEPARATOR ",") AS ItemIDs,
			GROUP_CONCAT(original.RecordIDs SEPARATOR ",") AS RecordIDs,
			SUM(original.RecordCount) AS RecordCount
		FROM
			(
				SELECT
					p.`Key`,
					p.`Value`,
					CAST(GROUP_CONCAT(p.`ItemID` ORDER BY p.`ItemID` ASC SEPARATOR ",") AS CHAR) AS ItemIDs,
					CAST(GROUP_CONCAT(p.`RecordID` ORDER BY p.`RecordID` ASC SEPARATOR ",") AS CHAR) AS RecordIDs,
					MAX(s.SiblingRecordID) AS SiblingRecordID,
					s.`SiblingType`,
					COUNT(*) AS RecordCount
				FROM
					`ExtractedProperties` AS p
					JOIN
					`ItemsBase` AS i
						ON i.ItemID = p.ItemID
					LEFT JOIN
					`PropertySiblings` AS s
						ON
							p.RecordID = s.RecordID
				WHERE
					i.Inactive = 0
				GROUP BY
					`Key`,
					`Value`
			) AS original
			LEFT JOIN
			(
				SELECT
					s.SiblingRecordID,
					s.SiblingType,
					p.Key,
					p.Value
				FROM
					ExtractedProperties AS p
					JOIN
					PropertySiblings AS s
						ON p.RecordID = s.SiblingRecordID
				GROUP BY
					s.SiblingRecordID,
					s.SiblingType) AS siblingData
				ON original.SiblingRecordID = siblingData.SiblingRecordID AND
				   original.SiblingType = siblingData.SiblingType
		GROUP BY
			CASE WHEN (siblingData.SiblingRecordID IS NULL) THEN
				original.Key
			ELSE
				siblingData.Key
			END,
			CASE WHEN (siblingData.SiblingRecordID IS NULL) THEN
				original.Value
			WHEN (siblingData.SiblingType = 0) THEN
				siblingData.Value
			ELSE
				original.Value
			END
	) AS x
	JOIN
	Properties AS p
		ON p.PropertyFrontendName = x.Value
WHERE
	x.Key = "Format / Maß"';
	}

	/**
	 *
	 */
	private function storeToDB()
	{
		$countPropertyToItemIDs = count($this->propertyToItemData);

		if ($countPropertyToItemIDs > 0)
		{
			DBQuery::getInstance()->truncate('TRUNCATE SetPropertiesToItem');
			DBQuery::getInstance()->insert('INSERT INTO SetPropertiesToItem' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->propertyToItemData));

			$this->debug(__FUNCTION__." stored $countPropertyToItemIDs PropertyToItem records");
		}
	}

	/**
	 * @param string $message
	 */
	private function debug($message)
	{
		Logger::instance(__CLASS__)->debug($message);
	}
}

$instance = new AssignFormatMass();
$instance->execute();


