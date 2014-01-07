<?php

require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'lib/db/DBQueryResult.class.php';
require_once ROOT . 'includes/SKUHelper.php';

/**
 * @author x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class DetermineWritePermissions {
	/**
	 *
	 * @var string
	 */
	private $identifier4Logger = '';

	public function __construct() {
		$this -> identifier4Logger = __CLASS__;
	}

	private function getQuery() {
		return '
			SELECT
				ItemsBase.ItemID,
				ItemsBase.Marking1ID,
				CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
					"0"
				ELSE
					AttributeValueSets.AttributeValueSetID
				END AttributeValueSetID,
				CASE WHEN (ItemsWarehouseSettings.ReorderLevel IS null) THEN
					"0"
				ELSE
					ItemsWarehouseSettings.ReorderLevel
				END ReorderLevel
			FROM
				ItemsBase
			LEFT JOIN
				AttributeValueSets
			ON
				ItemsBase.ItemID = AttributeValueSets.ItemID
			LEFT JOIN
				ItemsWarehouseSettings
			ON
				ItemsBase.ItemID = ItemsWarehouseSettings.ItemID
                AND CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
					"0"
                ELSE
					AttributeValueSets.AttributeValueSetID
                END = ItemsWarehouseSettings.AttributeValueSetID
			';
	}

	public function execute() {
		$this -> getLogger() -> debug(__FUNCTION__ . ' : Determine write permissions ...');
		$dbResult = DBQuery::getInstance() -> select($this -> getQuery());

		$result = array();
		while ($row = $dbResult -> fetchAssoc()) {
			$current = array();
			$current['ItemID'] = $row['ItemID'];
			$current['AttributeValueSetID'] = $row['AttributeValueSetID'];
			if (intval($row['Marking1ID']) === 16) {// green
				$current['WritePermission'] = 1;
			} else if ((intval($row['Marking1ID']) === 9) && (intval($row['ReorderLevel']) > 0)) {// yellow and positive reorder level
				$current['WritePermission'] = 1;
			} else {
				$current['WritePermission'] = 0;
			}
			$result[] = $current;
		}
		foreach ($result as $currentResult) {
			$query = 'REPLACE INTO `WritePermissions` '	. DBUtils::buildInsert($currentResult);
			DBQuery::getInstance()->replace($query);
		}
		$this -> getLogger() -> debug(__FUNCTION__ . ' : ... done');
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