<?php
require_once realpath(dirname(__FILE__) . '/../../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * Class GenerateFormatMass
 */
class GenerateFormatMass
{
	/**
	 * @var int
	 */
	const DIN_MODE = 0;

	/**
	 * @var int
	 */
	const OTHERS_MODE = 1;

	/**
	 * @var array
	 */
	private $rawData;

	/**
	 * @var array
	 */
	private $propertyGroups;

	/**
	 * @var array
	 */
	private $properties;

	/**
	 * @return GenerateFormatMass
	 */
	public function __construct()
	{
		$this->rawData = array();

		$this->propertyGroups = array();

		$this->properties = array();

		DBQuery::getInstance()->Set('SET SESSION group_concat_max_len = 8192');
	}

	/**
	 *
	 */
	public function execute()
	{
		// get all din data
		$dbResult = DBQuery::getInstance()->select($this->getQuery(self::DIN_MODE));
		while ($row = $dbResult->fetchAssoc())
		{
			$this->rawData[] = array(
				'Key'     => $row['Key'],
				'Value'   => $row['Value'],
				'ItemIDs' => explode(',', $row['ItemIDs']),
			);
		}

		// get all non din data
		$dbResult = DBQuery::getInstance()->select($this->getQuery(self::OTHERS_MODE));
		while ($row = $dbResult->fetchAssoc())
		{
			$this->rawData[] = array(
				'Key'     => $row['Key'],
				'Value'   => $row['Value'],
				'ItemIDs' => explode(',', $row['ItemIDs']),
			);
		}

		$searchGroupID = 10;

		// prepare property groups
		$this->propertyGroups[] = array(
			'id'                => null,
			'PropertyGroupID'   => $searchGroupID,
			'BackendName'       => 'Search: Format/Maß',
			'Lang'              => 'de',
			'PropertyGroupTyp'  => 'none',
			'IsMarkupPercental' => 0,
			'FrontendName'      => 'Format / Maß',
			'Description'       => null,
		);

		$propertyCounter = 158;

		// for all properties ...
		foreach ($this->rawData as $property)
		{
			// ... prepare property
			$this->properties[] = array(
				'id'                          => null,
				'PropertyID'                  => null,
				'PropertyGroupID'             => $searchGroupID,
				'PropertyBackendName'         => 'Search: ' . $property['Value'],
				'PropertyType'                => 'empty',
				'Position'                    => $propertyCounter,
				'Lang'                        => 'de',
				'PropertyFrontendName'        => $property['Value'],
				'Description'                 => '',
				'Searchable'                  => 1,
				'ShowInItemList'              => 1,
				'ShowInPDF'                   => 0,
				'ShowOnItemPage'              => 0,
				'PropertyUnit'                => null,
				'OrderProperty'               => 0,
				'Markup'                      => null,
				'Notice'                      => null,
				'BeezUP'                      => 0,
				'EbayLayout'                  => 0,
				'EbayProperty'                => 0,
				'Home24Property'              => null,
				'Idealo'                      => 0,
				'Kauflux'                     => 0,
				'NeckermannComponent'         => null,
				'NeckermannExternalComponent' => null,
				'NeckermannLogoId'            => null,
				'RicardoLayout'               => 0,
				'ShopShare'                   => 0,
				'Yatego'                      => 0,
			);

			$propertyCounter++;
		}
		$this->storeToDB();
	}

	/**
	 * @param int $mode
	 * @return string
	 */
	private function getQuery($mode)
	{
		return 'SELECT
	x.Key,
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
WHERE
	x.Key = "Format / Maß"
	AND x.Value ' . ($mode === self::OTHERS_MODE ? 'NOT ' : '') . 'LIKE "%din%"
ORDER BY
	CAST(x.Value AS SIGNED INTEGER) DESC';
	}

	/**
	 *
	 */
	private function storeToDB()
	{
		/*
				$countPropertyGroups = count($this->propertyGroups);
				$countProperties = count($this->properties);

				if ($countPropertyGroups > 0)
				{
					DBQuery::getInstance()->truncate('TRUNCATE SetPropertyGroups');
					DBQuery::getInstance()->insert('INSERT INTO SetPropertyGroups' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->propertyGroups));
					$this->debug(__FUNCTION__ . " storing $countPropertyGroups property group records ");
				}

				if ($countProperties > 0)
				{
					DBQuery::getInstance()->truncate('TRUNCATE SetProperties');
					DBQuery::getInstance()->insert('INSERT INTO SetProperties' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->properties));
					$this->debug(__FUNCTION__ . " storing $countProperties property records ");
				}*/
	}

	/**
	 * @param string $message
	 */
	private function debug($message)
	{
		Logger::instance(__CLASS__)->debug($message);
	}
}

$instance = new GenerateFormatMass();
$instance->execute();


