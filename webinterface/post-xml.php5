<?php
+ini_set('display_errors', 1);
+error_reporting(E_ALL);

$page = isset($_POST['page']) ? $_POST['page'] : 1;
$rp = isset($_POST['rp']) ? $_POST['rp'] : 10;
$sortname = isset($_POST['sortname']) ? $_POST['sortname'] : 'ItemID';
$sortorder = isset($_POST['sortorder']) ? $_POST['sortorder'] : 'asc';
$query = isset($_POST['query']) ? $_POST['query'] : false;
$qtype = isset($_POST['qtype']) ? $_POST['qtype'] : false;
$warehouseID = isset($_POST['warehouseID']) ? $_POST['warehouseID'] : 1;

class StaticData {
	public $itemID = -1;
	public $itemNo = null;
	public $name = null;
	public $attributeValueSetID = -1;
	public $marking1ID = -1;
	public $vpe = -1;
	public $reorderLevel = -1;
	public $supplierMinimumPurchase = -1;
	public $supplierDeliveryTime = -1;
	public $maximumStock = -1;

	public function __construct($sItemID, $sItemNo, $sName, $sAttributeValueSetID, $sAttributeValueSetName, $sMarking1ID, $sVpe, $sReorderLevel, $sSupplierMinimumPurchase, $sSupplierDeliveryTime, $sMaximumStock, $sBundleType) {
		$this -> itemID = intval($sItemID);
		$this -> itemNo = $sItemNo;
		$this -> attributeValueSetID = intval($sAttributeValueSetID);
		$this -> name = ($sBundleType === 'bundle' ? '[Bundle] ' : '') . ($this -> attributeValueSetID == 0 ? $sName : $sName . ', ' . $sAttributeValueSetName);
		$this -> marking1ID = intval($sMarking1ID);
		$this -> vpe = intval($sVpe);
		$this -> vpe = $this -> vpe == 0 ? 1 : $this -> vpe;
		$this -> reorderLevel = intval($sReorderLevel);
		$this -> supplierMinimumPurchase = intval($sSupplierMinimumPurchase);
		$this -> supplierMinimumPurchase = intval($sSupplierDeliveryTime);
		$this -> maximumStock = intval($sMaximumStock);
	}

}

class DynamicData {
	public $dailyNeed = -1.0;
	public $lastUpdate = null;
	public $valid = false;
	public $proposedReorderLevel = null;
	public $proposedSupplierMinimumPurchase = null;
	public $proposedMaximumStock = null;
	public $reorderLevelError = null;
	public $supplierMinimumPurchaseError = null;
	public $rawDataA = null;
	public $rawDataB = null;

	public function __construct($sDailyNeed, $sLastUpdate, $sValid, $sProposedReorderLevel, $sProposedSupplierMinimumPurchase, $sProposedMaximumStock, $sReorderLevelError, $sSupplierMinimumPurchaseError, $sQuantitiesA, $sQuantitiesB, $sSkippedA, $sSkippedB) {
		$this -> dailyNeed = floatval($sDailyNeed);
		$this -> lastUpdate = isset($sLastUpdate) ? date('d.m.y, H:i:s', $sLastUpdate) : null;

		$this -> rawDataA = isset($sQuantitiesA) && $sQuantitiesA !== '0' ? $sSkippedA . ':' . $sQuantitiesA : null;
		$this -> rawDataB = isset($sQuantitiesB) && $sQuantitiesB !== '0' ? $sSkippedB . ':' . $sQuantitiesB : null;

		$this -> valid = intval($sValid) === 1;
		if ($this -> valid) {
			$this -> proposedReorderLevel = intval($sProposedReorderLevel);
			$this -> proposedSupplierMinimumPurchase = intval($sProposedSupplierMinimumPurchase);
			$this -> proposedMaximumStock = intval($sProposedMaximumStock);
		} else {
			$this -> reorderLevelError = $sReorderLevelError;
			$this -> supplierMinimumPurchaseError = $sSupplierMinimumPurchaseError;
		}
	}

}

switch ($qtype) {
	case 'ItemID' :
		$qtype = 'ItemsBase.ItemID';
		break;
	default :
		break;
}

switch ($sortname) {
	case 'Date' :
		$sortname = 'LastUpdate';
		break;
	case 'MonthlyNeed' :
		$sortname = 'DailyNeed';
		break;
	case 'Marking' :
		$sortname = 'Marking1ID';
		break;
	default :
		break;
}

ob_start();
require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';

function getMaxRows($query) {
	return DBQuery::getInstance() -> select($query) -> getNumRows();
}

function getTextLike($columName, $value) {
	return $columName . ' LIKE "%' . $value . '%" ';
}

function getIntLike($columName, $value) {
	return $columName . ' = "' . $value . '" ';
}

$select_basic = 'SELECT
    ItemsBase.ItemID,
	ItemsBase.Name,
	CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
		"0"
	ELSE
		AttributeValueSets.AttributeValueSetID
	END AttributeValueSetID' . PHP_EOL;

$select_advanced = $select_basic . ',
	ItemsBase.ItemNo,
	ItemsBase.Marking1ID,
	ItemsBase.Free4 AS VPE,
	ItemsBase.BundleType,
	CalculatedDailyNeeds.DailyNeed,
	CalculatedDailyNeeds.LastUpdate,
	CalculatedDailyNeeds.QuantitiesA,
	CalculatedDailyNeeds.SkippedA,
	CalculatedDailyNeeds.QuantitiesB,
	CalculatedDailyNeeds.SkippedB,
	ItemsWarehouseSettings.ReorderLevel,
	ItemsWarehouseSettings.StockTurnover,
	ItemsWarehouseSettings.MaximumStock,
	ItemSuppliers.SupplierDeliveryTime,
	ItemSuppliers.SupplierMinimumPurchase,
	WritePermissions.WritePermission,
	WritePermissions.Error,
	WriteBackSuggestion.Valid,
    WriteBackSuggestion.ReorderLevelError,
    WriteBackSuggestion.SupplierMinimumPurchaseError,
    WriteBackSuggestion.ReorderLevel AS ProposedReorderLevel,
    WriteBackSuggestion.SupplierMinimumPurchase AS ProposedSupplierMinimumPurchase,
    WriteBackSuggestion.MaximumStock  AS ProposedMaximumStock,
	CASE WHEN (AttributeValueSets.AttributeValueSetName IS null) THEN
		""
	ELSE
		AttributeValueSets.AttributeValueSetName
	END AttributeValueSetName' . PHP_EOL;

$from_basic = 'FROM ItemsBase
LEFT JOIN AttributeValueSets
	ON ItemsBase.ItemID = AttributeValueSets.ItemID
LEFT JOIN ItemsWarehouseSettings
    ON ItemsBase.ItemID = ItemsWarehouseSettings.ItemID
    AND CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
        "0"
    ELSE
        AttributeValueSets.AttributeValueSetID
    END = ItemsWarehouseSettings.AttributeValueSetID' . PHP_EOL;

$from_advanced = $from_basic . 'LEFT JOIN CalculatedDailyNeeds
    ON ItemsBase.ItemID = CalculatedDailyNeeds.ItemID
    AND CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
        "0"
    ELSE
        AttributeValueSets.AttributeValueSetID
    END = CalculatedDailyNeeds.AttributeValueSetID
LEFT JOIN ItemSuppliers
	ON ItemsBase.ItemID = ItemSuppliers.ItemID
LEFT JOIN WritePermissions
    ON ItemsBase.ItemID = WritePermissions.ItemID
    AND CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
        "0"
    ELSE
        AttributeValueSets.AttributeValueSetID
    END = WritePermissions.AttributeValueSetID
LEFT JOIN WriteBackSuggestion
    ON ItemsBase.ItemID = WriteBackSuggestion.ItemID
    AND CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
        "0"
    ELSE
        AttributeValueSets.AttributeValueSetID
    END = WriteBackSuggestion.AttributeValueSetID' . PHP_EOL;

$where = 'WHERE 1' . PHP_EOL;

if ($query && $qtype) {
	if (strpos($query, ',') !== false) {
		$queries = explode(',', str_replace(' ', '', $query));

		$where .= '
				AND (';
		for ($i = 0; $i < count($queries); $i++) {
			if ($i != 0) {
				$where .= '
				OR';
			} {
				$where .= '
					' . ($qtype == 'ItemsBase.ItemID' ? getIntLike($qtype, $queries[$i]) : getTextLike($qtype, $queries[$i]));
			}
		}
		$where .= '
				)';

	} else {
		$where .= '
				AND
					' . ($qtype == 'ItemsBase.ItemID' ? getIntLike($qtype, $query) : getTextLike($qtype, $query));
	}
}

$sort = '
				ORDER BY ' . $sortname . ' ' . $sortorder;

$start = (($page - 1) * $rp);

$limit = '
				LIMIT ' . $start . ', ' . $rp;

$sql = $select_advanced . $from_advanced . $where . $sort . $limit;

$result = DBQuery::getInstance() -> select($sql);

$total = getMaxRows($select_basic . $from_basic . $where);

header('Content-type: text/xml');
$xml = "<?xml version='1.0' encoding='utf-8'?>\n<rows>\n\t<page>{$page}</page>\n\t<total>{$total}</total>\n";
while ($row = $result -> fetchAssoc()) {
	$staticData = new StaticData($row['ItemID'], $row['ItemNo'], $row['Name'], $row['AttributeValueSetID'], $row['AttributeValueSetName'], $row['Marking1ID'], $row['VPE'], $row['ReorderLevel'], $row['StockTurnover'], $row['SupplierMinimumPurchase'], $row['SupplierDeliveryTime'], $row['MaximumStock'], $row['BundleType']);
	$dynamicData = new DynamicData($row['DailyNeed'], $row['LastUpdate'], $row['Valid'], $row['ProposedReorderLevel'], $row['ProposedSupplierMinimumPurchase'], $row['ProposedMaximumStock'], $row['ReorderLevelError'], $row['SupplierMinimumPurchaseError'], $row['QuantitiesA'], $row['QuantitiesB'], $row['SkippedA'], $row['SkippedB']);

	$dailyNeed_string = abs($dynamicData -> dailyNeed) < 0.01 ? '' : number_format($dynamicData -> dailyNeed, 2);
	$monthlyNeed_string = abs($dynamicData -> dailyNeed) < 0.01 ? '' : number_format($dynamicData -> dailyNeed * 30, 2);

	if ($dynamicData -> valid) {
		$reorderLevel_string = isset($dynamicData -> reorderLevelError) ? $dynamicData -> reorderLevelError : $dynamicData -> proposedReorderLevel . ':' . $staticData -> reorderLevel;
		$supplierMinimumPurchase_string = isset($dynamicData -> supplierMinimumPurchaseError) ? $dynamicData -> supplierMinimumPurchaseError : $dynamicData -> proposedSupplierMinimumPurchase . ':' . $staticData -> supplierMinimumPurchase;
		$maxStockSuggestion_string = isset($dynamicData -> supplierMinimumPurchaseError) ? $dynamicData -> supplierMinimumPurchaseError : $dynamicData -> proposedMaximumStock . ':' . $staticData -> maximumStock;

		$write_permission_prefix = intval($row['WritePermission']) === 1 ? 'w' : (intval($row['Error']) === 1 ? 'e' : 'x');
	}

	$xml .= "\t<row id='$staticData->itemID-0-$staticData->attributeValueSetID'>
        <cell><![CDATA[$staticData->itemID]]></cell>
        <cell><![CDATA[$staticData->itemNo]]></cell>
        <cell><![CDATA[$staticData->name]]></cell>
        <cell><![CDATA[$dynamicData->rawDataA]]></cell>
        <cell><![CDATA[$dynamicData->rawDataB]]></cell>
        <cell><![CDATA[$monthlyNeed_string]]></cell>
        <cell><![CDATA[$dailyNeed_string]]></cell>
        <cell><![CDATA[$staticData->marking1ID]]></cell>
        <cell><![CDATA[{$write_permission_prefix}:{$reorderLevel_string}]]></cell>
        <cell><![CDATA[]]>{$write_permission_prefix}:{$maxStockSuggestion_string}</cell>
        <cell><![CDATA[]]>{$write_permission_prefix}:{$supplierMinimumPurchase_string}</cell>
        <cell><![CDATA[]]>$staticData->vpe</cell>
        <cell><![CDATA[]]>1</cell>
        <cell><![CDATA[]]>1</cell>
        <cell><![CDATA[$dynamicData->lastUpdate]]></cell>
	</row>\n";
}
ob_end_clean();

$xml .= '</rows>';
echo $xml;
