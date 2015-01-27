<?php
require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';

class ApiJansen {

	/**
	 * @var string
	 */
	const JANSEN_DATA_SELECT_BASIC = 'SELECT
	js.EAN,
	js.ExternalItemID,
	nx.ItemID,
	nx.Name';

	/**
	 * @var string
	 */
	const JANSEN_DATA_SELECT_ADVANCED = ',
	js.PhysicalStock,
	nx.Timestamp,
	CASE WHEN (nx.ItemID IS NOT NULL) THEN
		js.ExternalItemID = nx.ExternalItemID
	ELSE
		NULL
	END as ExactMatch';

	/**
	 * @var string
	 */
	const JANSEN_DATA_FROM_BASIC = "\nFROM
	JansenStockData as js
LEFT JOIN
	(SELECT
		js.EAN,
		nx.ItemID,
		nx.Name,
		nx.ExternalItemID,
		st.Timestamp
	FROM
		JansenStockData as js
	JOIN
		(
			SELECT
				nx.ItemID,
				nx.Name,
				nx.ExternalItemID,
				avs.AttributeValueSetID,
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

	public static function getJansenStockData($page = 1, $rowsPerPage = 10, $sortByColumn = 'EAN', $sortOrder = 'ASC', $eans = null, $externalItemIDs = null, $itemIDs = null, $names = null) {
		$data = array('page' => $page, 'total' => null, 'rows' => array());

		ob_start();
		$whereCondition = "";

		// prepare filter conditions
		if (!is_null($eans)) {
			if (is_array($eans)) {
				$aEans = $eans;
			} else {
				$aEans = array($eans);
			}
			$whereCondition .= "AND\n\tjs.EAN IN  ('" . implode('\',\'', $aEans) . "')\n";
		} else if (!is_null($externalItemIDs)) {
			if (is_array($externalItemIDs)) {
				$aExternalItemIDs = $externalItemIDs;
			} else {
				$aExternalItemIDs = array($externalItemIDs);
			}
			$whereCondition .= "AND\n\tjs.ExternalItemID IN  ('" . implode('\',\'', $aExternalItemIDs) . "')\n";
		} else if (!is_null($itemIDs)) {
			if (is_array($itemIDs)) {
				$aItemIDs = $itemIDs;
			} else {
				$aItemIDs = array($itemIDs);
			}
			$whereCondition .= "AND\n\tnx.ItemID IN  ('" . implode('\',\'', $aItemIDs) . "')\n";
		} else if (!is_null($names)) {
			if (is_array($names)) {
				$aItemNames = $names;
			} else {
				$aItemNames = array($names);
			}

			foreach ($aItemNames as $name) {
				$whereCondition .= "AND
	nx.Name LIKE \"%$name%\"\n";
			}
		}

		$data['total'] = DBQuery::getInstance() -> select(self::JANSEN_DATA_SELECT_BASIC . self::JANSEN_DATA_FROM_BASIC . self::JANSEN_DATA_WHERE . $whereCondition) -> getNumRows();

		$sort = "\nORDER BY $sortByColumn $sortOrder\n";
		$start = (($page - 1) * $rowsPerPage);
		$limit = "LIMIT $start,$rowsPerPage";

		// add price id to select advanced clause
		$query = self::JANSEN_DATA_SELECT_BASIC . self::JANSEN_DATA_SELECT_ADVANCED . self::JANSEN_DATA_FROM_BASIC . self::JANSEN_DATA_FROM_ADVANCED . self::JANSEN_DATA_WHERE . $whereCondition . $sort . $limit;
		$jansenStockDataDBResult = DBQuery::getInstance() -> select($query);
		ob_end_clean();

		while ($jansenStockDataData = $jansenStockDataDBResult -> fetchAssoc()) {
			//@formatter:off
			$data['rows'][$jansenStockDataData['EAN']] = array(
				'ean'				=>	$jansenStockDataData['EAN'],
				'externalItemID'	=>	$jansenStockDataData['ExternalItemID'],
				'physicalStock'		=>	$jansenStockDataData['PhysicalStock'],
				'itemID'			=>	$jansenStockDataData['ItemID'],
				'name'				=>	$jansenStockDataData['Name'],
				'date'				=>	isset($jansenStockDataData['Timestamp']) ? date('d.m.y, H:i:s', $jansenStockDataData['Timestamp']) : null,
				'data'				=>	array(
											'match'			=>	isset($jansenStockDataData['ItemID']),
											'exactMatch'	=>	$jansenStockDataData['ExactMatch'] == 1
										)
			);
			//@formatter:on
		}
		return $data;
	}

}
?>
