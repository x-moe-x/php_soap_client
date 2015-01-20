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
		nx.ExternalItemID
	FROM
		JansenStockData as js
	JOIN
		ItemsBase as nx
	ON
		(nx.EAN2 = js.EAN)
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

	public static function getJansenStockData($page = 1, $rowsPerPage = 10, $sortByColumn = 'EAN', $sortOrder = 'ASC') {
		$data = array('page' => $page, 'total' => null, 'rows' => array());

		ob_start();
		$whereCondition = "";

		$data['total'] = DBQuery::getInstance() -> select(self::JANSEN_DATA_SELECT_BASIC . self::JANSEN_DATA_FROM_BASIC . self::JANSEN_DATA_WHERE . $whereCondition) -> getNumRows();

		// get associated price id

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
