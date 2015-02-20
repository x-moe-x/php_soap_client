<?php

require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'lib/db/DBQueryResult.class.php';
require_once ROOT . 'includes/SKUHelper.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * @author    x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class DetermineWritePermissions
{
	/**
	 * @var string
	 */
	private $identifier4Logger = '';

	/**
	 * @var array
	 */
	private $articleData;

	/**
	 * @return DetermineWritePermissions
	 */
	public function __construct()
	{
		$this->identifier4Logger = __CLASS__;
		$this->articleData = array();
	}

	/**
	 * calculate write permissions for every article variant
	 *
	 * @return void
	 */
	public function execute()
	{
		$this->getLogger()->debug(__FUNCTION__ . ' : Determine write permissions');

		$dbResult = DBQuery::getInstance()->select($this->getQuery());

		// for every item variant ...
		while ($currentArticleVariant = $dbResult->fetchAssoc())
		{
			// ... store ItemID, AVSID, Marking1ID and corresponding WritePermission
			$result = array('ItemID'              => $currentArticleVariant['ItemID'],
							 'AttributeValueSetID' => $currentArticleVariant['AttributeValueSetID']
			);

			// if item variant is no bundle and has black/green marking or yellow marking with positive reorder level ...
			if (($currentArticleVariant['BundleType'] !== 'bundle') && ((intval($currentArticleVariant['Marking1ID']) == 16) || (intval($currentArticleVariant['Marking1ID']) == 20) || (intval($currentArticleVariant['Marking1ID']) == 9) && (intval($currentArticleVariant['ReorderLevel']) > 0)))
			{
				// ... then it has write permission
				$result['WritePermission'] = 1;
			} else
			{
				// ... otherwise not
				$result['WritePermission'] = 0;
			}

			// if write permission given, but there's an error ... (like no supplier delivery time, no stock turnover, or it's a malformed article variant (SupplierMinimumPurchase != 0) or a bundle article)
			if ((intval($result['WritePermission']) == 1) && ((intval($currentArticleVariant['SupplierDeliveryTime']) <= 0) || (intval($currentArticleVariant['StockTurnover']) <= 0)) || ((intval($currentArticleVariant['AttributeValueSetID']) !== 0) && ((intval($currentArticleVariant['SupplierMinimumPurchase']) !== 0))))
			{
				// ... then revoke write permission and set error
				$result['WritePermission'] = 0;
				$result['Error'] = 1;
			} else
			{
				// ... otherwise everything's ok
				$result['Error'] = 0;
			}

			$this->articleData[] = $result;
		}

		$this->storeToDB();
	}

	/**
	 * stores article data to db
	 *
	 * @return void
	 */
	private function storeToDB()
	{
		DBQuery::getInstance()->insert('INSERT INTO `WritePermissions`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->articleData));
	}

	/**
	 *
	 * prepare query for data necessary for calculation
	 *
	 * @return string query
	 */
	private function getQuery()
	{
		return 'SELECT
	ItemsBase.ItemID,
	ItemsBase.Marking1ID,
	ItemsBase.BundleType,
	CASE WHEN (AttributeValueSets.AttributeValueSetID IS NULL) THEN
		"0"
	ELSE
		AttributeValueSets.AttributeValueSetID
	END AttributeValueSetID,
	CASE WHEN (ItemsWarehouseSettings.StockTurnover IS NULL) THEN
		"0"
	ELSE
		ItemsWarehouseSettings.StockTurnover
	END StockTurnover,
	CASE WHEN (ItemsWarehouseSettings.ReorderLevel IS NULL) THEN
		"0"
	ELSE
		ItemsWarehouseSettings.ReorderLevel
	END ReorderLevel,
	ItemSuppliers.SupplierDeliveryTime,
	ItemSuppliers.SupplierMinimumPurchase
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
    AND CASE WHEN (AttributeValueSets.AttributeValueSetID IS NULL) THEN
		"0"
    ELSE
		AttributeValueSets.AttributeValueSetID
    END = ItemsWarehouseSettings.AttributeValueSetID
WHERE
	ItemsBase.Inactive = 0';
	}

	/**
	 * @return Logger
	 */
	protected function getLogger()
	{
		return Logger::instance($this->identifier4Logger);
	}
}
