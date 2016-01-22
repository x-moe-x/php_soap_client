<?php
require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';

/**
 * Class ApiJansen
 */
class ApiJansen
{

	/**
	 * @var string
	 */
	const JANSEN_DATA_SELECT_BASIC = 'SELECT
	js.EAN,
	js.ExternalItemID,
	nx.ItemID,
	CONCAT(CASE WHEN (nx.BundleType = "bundle") THEN
			"[Bundle] "
		WHEN (nx.BundleType = "bundle_item") THEN
			"[Bundle Artikel] "
		ELSE
			""
		END, nx.Name, CASE WHEN (nx.AttributeValueSetID IS NOT null) THEN
			CONCAT(", ", nx.AttributeValueSetName)
		ELSE
			""
	END) AS Name';

	/**
	 * @var string
	 */
	const JANSEN_DATA_SELECT_ADVANCED = ",
	js.PhysicalStock,
	nx.Timestamp,
	CASE WHEN (nx.ItemID IS NOT NULL) THEN
		LOWER(
			CASE WHEN (nx.AttributeValueSetID IS NULL) THEN
					nx.ExternalItemID
				ELSE
					CASE WHEN (nx.AttributeValueSetID = 1) THEN
						REPLACE(nx.ExternalItemID,' [R/G] ','G')
					WHEN (nx.AttributeValueSetID = 2) THEN
						REPLACE(nx.ExternalItemID,' [R/G] ','R')
					WHEN (nx.AttributeValueSetID = 23) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','RED')
					WHEN (nx.AttributeValueSetID = 24) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','YELLOW')
					WHEN (nx.AttributeValueSetID = 25) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','PURPLE')
					WHEN (nx.AttributeValueSetID = 26) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','WHITE')
					WHEN (nx.AttributeValueSetID = 27) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','PINK')
					WHEN (nx.AttributeValueSetID = 28) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','DARKBLUE')
					WHEN (nx.AttributeValueSetID = 29) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','DARKGREEN')
					WHEN (nx.AttributeValueSetID = 30) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','ORANGE')
					ELSE
						'xxx'
					END
			END
		) = LOWER(js.ExternalItemID)
	ELSE
		FALSE
	END as ExactMatch";

	/**
	 * @var string
	 */
	const JANSEN_DATA_FROM_BASIC = "\nFROM
	JansenStockData as js
LEFT JOIN
	(
		SELECT
			js.EAN,
			nx.ItemID,
			nx.Name,
			nx.ExternalItemID,
			nx.BundleType,
			nx.AttributeValueSetID,
			nx.AttributeValueSetName,
			st.Timestamp
		FROM
			JansenStockData as js
		JOIN
			(
				SELECT
					nx.ItemID,
					nx.Name,
					nx.ExternalItemID,
					nx.BundleType,
					avs.AttributeValueSetID,
					avs.AttributeValueSetName,
					CASE WHEN (avs.AttributeValueSetID IS NULL) THEN
						nx.EAN2
					ELSE
						avs.EAN2
					END AS EAN2
				FROM
					ItemsBase AS nx
				LEFT JOIN
					AttributeValueSets AS avs
				ON
					avs.ItemID = nx.ItemID
			) as nx
		ON
			nx.EAN2 = js.EAN
		LEFT JOIN
			CurrentStocksTiming as st
		ON
			(nx.ItemID = st.ItemID)
		AND
			CASE WHEN (nx.AttributeValueSetID IS NULL) THEN
				0
			ELSE
				nx.AttributeValueSetID
			END = st.AttributeValueSetID
		WHERE
			st.WarehouseID = 2
	) as nx
ON nx.EAN = js.EAN\n";
	// the nested select query is a workaround for a very slow left join in items base directly

	/**
	 * @var string
	 */
	const JANSEN_DATA_FROM_ADVANCED = "\n";

	/**
	 * @var string
	 */
	const JANSEN_DATA_WHERE = "WHERE
	1\n";

	/**
	 * @var string
	 */
	const JANSEN_UNMATCHED_DATA_QUERY = "SELECT
	i.EAN,
	i.ExternalItemID,
	jsu.ItemID,
	i.Name
FROM
	JansenStockUnmatched AS jsu
JOIN
	(
		SELECT
			i.ItemID,
			CONCAT(CASE WHEN (i.BundleType = 'bundle') THEN
					'[Bundle] '
				WHEN (i.BundleType = 'bundle_item') THEN
					'[Bundle Artikel] '
				ELSE
					''
				END, i.Name, CASE WHEN (avs.AttributeValueSetID IS NOT NULL) THEN
					CONCAT(', ', avs.AttributeValueSetName)
				ELSE
					''
			END) AS Name,
			CASE WHEN (avs.AttributeValueSetID IS NULL) THEN
				i.EAN2
			ELSE
				avs.EAN2
			END AS EAN,
			CASE WHEN (avs.AttributeValueSetID IS NULL) THEN
				0
			ELSE
				avs.AttributeValueSetID
			END AS AttributeValueSetID,
			i.ExternalItemID
		FROM
			ItemsBase AS i
		LEFT JOIN
			AttributeValueSets AS avs
		ON
			i.ItemID = avs.ItemID
	) AS i
ON
	jsu.ItemID = i.ItemID
AND
	jsu.AttributeValueSetID = i.AttributeValueSetID\n";

	/**
	 * @param int                  $page
	 * @param int                  $rowsPerPage
	 * @param string               $sortByColumn
	 * @param string               $sortOrder
	 * @param null|string|string[] $eans
	 * @param null|string|string[] $externalItemIDs
	 * @param null|int|int[]       $itemIDs
	 * @param null|string|string[] $names
	 * @param null|int             $jansenMatch
	 *
	 * @return array
	 */
	public static function getJansenStockData($page = 1, $rowsPerPage = 10, $sortByColumn = 'EAN', $sortOrder = 'ASC', $eans = null, $externalItemIDs = null, $itemIDs = null, $names = null, $jansenMatch = null)
	{
		$data = array(
			'page'  => $page,
			'total' => null,
			'rows'  => array()
		);

		ob_start();
		$whereCondition = "";

		// prepare filter conditions
		if (!is_null($jansenMatch))
		{
			if (!is_array($jansenMatch))
			{
				$jansenMatch = array($jansenMatch);
			}
			$whereCondition .= "AND\n\t(\n";
			$matches = array();
			foreach ($jansenMatch as $matchIndex)
			{
				switch ($matchIndex)
				{
					case 0 :
						$matches[] = "\t\tnx.ItemID IS NULL\n";
						break;
					case 1 :
						$matches[] = "\t\tnx.ItemID IS NOT NULL AND
		LOWER(
			CASE WHEN (nx.AttributeValueSetID IS NULL) THEN
					nx.ExternalItemID
				ELSE
					CASE WHEN (nx.AttributeValueSetID = 1) THEN
						REPLACE(nx.ExternalItemID,' [R/G] ','G')
					WHEN (nx.AttributeValueSetID = 2) THEN
						REPLACE(nx.ExternalItemID,' [R/G] ','R')
					WHEN (nx.AttributeValueSetID = 23) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','RED')
					WHEN (nx.AttributeValueSetID = 24) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','YELLOW')
					WHEN (nx.AttributeValueSetID = 25) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','PURPLE')
					WHEN (nx.AttributeValueSetID = 26) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','WHITE')
					WHEN (nx.AttributeValueSetID = 27) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','PINK')
					WHEN (nx.AttributeValueSetID = 28) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','DARKBLUE')
					WHEN (nx.AttributeValueSetID = 29) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','DARKGREEN')
					WHEN (nx.AttributeValueSetID = 30) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','ORANGE')
					ELSE
						'xxx'
					END
			END
		) != LOWER(js.ExternalItemID)\n";
						break;
					case 2 :
						$matches[] = "\t\tnx.ItemID IS NOT NULL AND
		LOWER(
			CASE WHEN (nx.AttributeValueSetID IS NULL) THEN
					nx.ExternalItemID
				ELSE
					CASE WHEN (nx.AttributeValueSetID = 1) THEN
						REPLACE(nx.ExternalItemID,' [R/G] ','G')
					WHEN (nx.AttributeValueSetID = 2) THEN
						REPLACE(nx.ExternalItemID,' [R/G] ','R')
					WHEN (nx.AttributeValueSetID = 23) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','RED')
					WHEN (nx.AttributeValueSetID = 24) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','YELLOW')
					WHEN (nx.AttributeValueSetID = 25) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','PURPLE')
					WHEN (nx.AttributeValueSetID = 26) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','WHITE')
					WHEN (nx.AttributeValueSetID = 27) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','PINK')
					WHEN (nx.AttributeValueSetID = 28) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','DARKBLUE')
					WHEN (nx.AttributeValueSetID = 29) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','DARKGREEN')
					WHEN (nx.AttributeValueSetID = 30) THEN
						REPLACE(nx.ExternalItemID,'+[Color]','ORANGE')
					ELSE
						'xxx'
					END
			END
		) = LOWER(js.ExternalItemID)\n";
						break;
					default :
						throw new RuntimeException("Illegal match index $matchIndex");
						break;
				}
			}
			$whereCondition .= implode("\tOR\n", $matches) . "\t)\n";
		}

		if (!is_null($eans))
		{
			if (!is_array($eans))
			{
				$eans = array($eans);
			}
			$whereCondition .= "AND\n\tjs.EAN IN  ('" . implode('\',\'', $eans) . "')\n";
		} else
		{
			if (!is_null($externalItemIDs))
			{
				if (!is_array($externalItemIDs))
				{
					$externalItemIDs = array($externalItemIDs);
				}
				$whereCondition .= "AND\n\tjs.ExternalItemID IN  ('" . implode('\',\'', $externalItemIDs) . "')\n";
			} else
			{
				if (!is_null($itemIDs))
				{
					if (!is_array($itemIDs))
					{
						$itemIDs = array($itemIDs);
					}
					$whereCondition .= "AND\n\tnx.ItemID IN  ('" . implode('\',\'', $itemIDs) . "')\n";
				} else
				{
					if (!is_null($names))
					{
						if (!is_array($names))
						{
							$names = array($names);
						}

						foreach ($names as $name)
						{
							$whereCondition .= "AND
	nx.Name LIKE \"%$name%\"\n";
						}
					}
				}
			}
		}

		$data['total'] = DBQuery::getInstance()->select(self::JANSEN_DATA_SELECT_BASIC . self::JANSEN_DATA_FROM_BASIC . self::JANSEN_DATA_WHERE . $whereCondition)->getNumRows();

		$sort = "\nORDER BY $sortByColumn $sortOrder\n";
		$start = (($page - 1) * $rowsPerPage);
		$limit = "LIMIT $start,$rowsPerPage";

		// add price id to select advanced clause
		$query = self::JANSEN_DATA_SELECT_BASIC . self::JANSEN_DATA_SELECT_ADVANCED . self::JANSEN_DATA_FROM_BASIC . self::JANSEN_DATA_FROM_ADVANCED . self::JANSEN_DATA_WHERE . $whereCondition . $sort . $limit;
		$jansenStockDataDBResult = DBQuery::getInstance()->select($query);
		ob_end_clean();

		while ($jansenStockDataData = $jansenStockDataDBResult->fetchAssoc())
		{
			$data['rows'][$jansenStockDataData['EAN']] = array(
				'ean'            => $jansenStockDataData['EAN'],
				'externalItemID' => $jansenStockDataData['ExternalItemID'],
				'physicalStock'  => $jansenStockDataData['PhysicalStock'],
				'itemID'         => $jansenStockDataData['ItemID'],
				'name'           => $jansenStockDataData['Name'],
				'date'           => isset($jansenStockDataData['Timestamp']) ? date('d.m.y, H:i:s', $jansenStockDataData['Timestamp']) : null,
				'data'           => array(
					'match'      => isset($jansenStockDataData['ItemID']),
					'exactMatch' => $jansenStockDataData['ExactMatch'] == 1
				)
			);
		}

		return $data;
	}

	public static function getJansenUnmatchedData()
	{
		$data = array(
			'page'  => 1,
			'total' => null,
			'rows'  => array()
		);

		ob_start();
		$jansenUnmatchedDBResult = DBQuery::getInstance()->select(self::JANSEN_UNMATCHED_DATA_QUERY);
		ob_end_clean();

		$data['total'] = $jansenUnmatchedDBResult->getNumRows();

		while ($jansenUnmatched = $jansenUnmatchedDBResult->fetchAssoc())
		{
			$data['rows'][$jansenUnmatched['EAN']] = array(
				'ean'            => $jansenUnmatched['EAN'],
				'externalItemID' => $jansenUnmatched['ExternalItemID'],
				'itemID'         => $jansenUnmatched['ItemID'],
				'name'           => $jansenUnmatched['Name']
			);
		}

		return $data;
	}
}