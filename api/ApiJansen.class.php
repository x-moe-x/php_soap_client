<?php
require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';

class ApiJansen {

	/**
	 * @var string
	 */
	const JANSEN_DATA_SELECT_BASIC = 'SELECT
	js.EAN';

	/**
	 * @var string
	 */
	const JANSEN_DATA_SELECT_ADVANCED = ',
	js.ExternalItemID,
	js.PhysicalStock,
	nx.ItemID,
	nx.Name';

	/**
	 * @var string
	 */
	const JANSEN_DATA_FROM_BASIC = "\nFROM
	JansenStockData as js\n";

	/**
	 * @var string
	 */
	const JANSEN_DATA_FROM_ADVANCED = "LEFT JOIN
	(SELECT
		js.EAN,
		nx.ItemID,
		nx.Name
	FROM
		JansenStockData as js
	JOIN
		ItemsBase as nx
	ON
		(nx.EAN2 = js.EAN)
	AND
		(nx.ExternalItemID = js.ExternalItemID)) as nx
ON nx.EAN = js.EAN"; // the nested select query is a workaround for a very slow left join in items base directly

	/**
	 * @var string
	 */
	const JANSEN_DATA_WHERE = "";

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
			$data['rows'][$jansenStockDataData['EAN']] = $jansenStockDataData;
		}
		return $data;
	}
}
?>
