<?php

require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'lib/db/DBQueryResult.class.php';
require_once ROOT . 'includes/SKUHelper.php';
require_once ROOT . 'includes/DBUtils2.class.php';

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

	/**
	 * @var array
	 */
	private $aArticleData;

	/**
	 * @var array
	 */
	private static $aExclusiveItemIDs = array(1919, 416, 1882, 201, 410);

	/**
	 * @return DetermineWritePermissions
	 */
	public function __construct() {
		$this -> identifier4Logger = __CLASS__;
		$this -> aArticleData = array();
	}

	/**
	 * calculate write permissions for every article variant
	 *
	 * @return void
	 */
	public function execute() {
		$this -> getLogger() -> debug(__FUNCTION__ . ' : Determine write permissions');

		$dbResult = DBQuery::getInstance() -> select($this -> getQuery());

		// for every item variant ...
		while ($aCurrentArticleVariant = $dbResult -> fetchAssoc()) {
			// ... store ItemID, AVSID, Marking1ID and corresponding WritePermission
			$aResult = array('ItemID' => $aCurrentArticleVariant['ItemID'], 'AttributeValueSetID' => $aCurrentArticleVariant['AttributeValueSetID']);

			// if item variant has green marking or yellow marking with positive reorder level ...
			if ((intval($aCurrentArticleVariant['Marking1ID']) == 16) || (intval($aCurrentArticleVariant['Marking1ID']) == 9) && (intval($aCurrentArticleVariant['ReorderLevel']) > 0)) {
				// ... then it has write permission
				$aResult['WritePermission'] = 1;
			} else {
				// ... otherwise not
				$aResult['WritePermission'] = 0;
			}

			// if write permission given, but there's an error ... (like no supplier delivery time, no stock turnover or it's an article variant)
			if ((intval($aResult['WritePermission']) == 1) && (!in_array($aCurrentArticleVariant['ItemID'], self::$aExclusiveItemIDs) || (intval($aCurrentArticleVariant['SupplierDeliveryTime']) <= 0) || (intval($aCurrentArticleVariant['StockTurnover']) <= 0)) || (intval($aCurrentArticleVariant['AttributeValueSetID']) !== 0)) {
				// ... then revoke write permission and set error
				$aResult['WritePermission'] = 0;
				$aResult['Error'] = 1;
			} else {
				// ... otherwise everything's ok
				$aResult['Error'] = 0;
			}

			$this -> aArticleData[] = $aResult;
		}

		$this -> storeToDB();
	}

	/**
	 * stores article data to db
	 *
	 * @return void
	 */
	private function storeToDB() {
		DBQuery::getInstance() -> insert('INSERT INTO `WritePermissions`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this -> aArticleData));
	}

	/**
	 *
	 * prepare query for data necessary for calculation
	 *
	 * @return string query
	 */
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
				CASE WHEN (ItemsWarehouseSettings.StockTurnover IS null) THEN
					"0"
				ELSE
					ItemsWarehouseSettings.StockTurnover
				END StockTurnover,
				CASE WHEN (ItemsWarehouseSettings.ReorderLevel IS null) THEN
					"0"
				ELSE
					ItemsWarehouseSettings.ReorderLevel
				END ReorderLevel,
				ItemSuppliers.SupplierDeliveryTime
			FROM
				ItemsBase
			LEFT JOIN
				AttributeValueSets
			ON
				ItemsBase.ItemID = AttributeValueSets.ItemID
			LEFT JOIN ItemSuppliers
				ON ItemsBase.ItemID = ItemSuppliers.ItemID
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

	/**
	 *
	 * @return Logger
	 */
	protected function getLogger() {
		return Logger::instance($this -> identifier4Logger);
	}

}
?>