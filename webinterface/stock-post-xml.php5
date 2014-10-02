<?php
require_once 'includes/basic_forward.inc.php';

$page = isset($_POST['page']) ? $_POST['page'] : 1;
$rp = isset($_POST['rp']) ? $_POST['rp'] : 10;
$sortname = isset($_POST['sortname']) ? $_POST['sortname'] : 'ItemID';
$sortorder = isset($_POST['sortorder']) ? $_POST['sortorder'] : 'asc';
$query = isset($_POST['query']) ? $_POST['query'] : false;
$qtype = isset($_POST['qtype']) ? $_POST['qtype'] : false;
$filterMarking1D = isset($_POST['filterMarking1D']) ? $_POST['filterMarking1D'] : false;

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
	public $maximumStock = -1;
	public $currentStock = -1;

	public function __construct($row) {
		$this -> itemID = intval($row['ItemID']);
		$this -> itemNo = $row['ItemNo'];
		$this -> attributeValueSetID = intval($row['AttributeValueSetID']);
		$this -> name = $row['Name'];
		$this -> marking1ID = intval($row['Marking1ID']);
		$this -> vpe = intval($row['VPE']);
		$this -> vpe = $this -> vpe == 0 ? 1 : $this -> vpe;
		$this -> reorderLevel = intval($row['ReorderLevel']);
		$this -> supplierMinimumPurchase = intval($row['SupplierMinimumPurchase']);
		$this -> maximumStock = intval($row['MaximumStock']);
		$this -> currentStock = intval($row['NetStock']);
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
	public $writePermissionPrefix = null;

	public function __construct($row) {
		$this -> dailyNeed = floatval($row['DailyNeed']);
		$this -> lastUpdate = isset($row['LastUpdate']) ? date('d.m.y, H:i:s', $row['LastUpdate']) : null;

		$this -> rawDataA = isset($row['QuantitiesA']) && $row['QuantitiesA'] !== '0' ? $row['SkippedA'] . ':' . $row['QuantitiesA'] : null;
		$this -> rawDataB = isset($row['QuantitiesB']) && $row['QuantitiesB'] !== '0' ? $row['SkippedB'] . ':' . $row['QuantitiesB'] : null;

		$this -> valid = intval($row['Valid']) === 1;

		$this -> proposedReorderLevel = isset($row['ProposedReorderLevel']) ? intval($row['ProposedReorderLevel']) : null;
		$this -> proposedSupplierMinimumPurchase = isset($row['ProposedSupplierMinimumPurchase']) ? intval($row['ProposedSupplierMinimumPurchase']) : null;
		$this -> proposedMaximumStock = isset($row['ProposedMaximumStock']) ? intval($row['ProposedMaximumStock']) : null;
		$this -> writePermissionPrefix = (intval($row['WritePermission']) === 1) ? 'w' : (intval($row['WritePermissionError']) === 1 ? 'e' : 'x');

		if (!$this -> valid) {
			$this -> reorderLevelError = isset($row['ReorderLevelError']) ? 'Lieferzeit nicht konfiguriert' : null;
			$this -> supplierMinimumPurchaseError = isset($row['SupplierMinimumPurchaseError']) ? 'Lagerreichweite nicht konfiguriert' : null;
		}
	}

}

switch ($qtype) {
	case 'ItemID' :
		$qtype = 'ItemsBase.ItemID';
		break;
	case 'Name' :
		$qtype = '	CONCAT(CASE WHEN (ItemsBase.BundleType = "bundle") THEN
			"[Bundle] "
		WHEN (ItemsBase.BundleType = "bundle_item") THEN
			"[Bundle item] "
		ELSE
			""
		END, ItemsBase.Name, CASE WHEN (AttributeValueSets.AttributeValueSetID IS NOT null) THEN
			CONCAT(", ", AttributeValueSets.AttributeValueSetName)
		ELSE
			""
	END)';
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
	case 'Name' :
		$sortname = 'SortName';
		break;
	case 'CurrentStock' :
		$sortname = 'NetStock';
		break;
	default :
		break;
}

ob_start();
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
	CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
		"0"
	ELSE
		AttributeValueSets.AttributeValueSetID
	END AttributeValueSetID' . PHP_EOL;

$select_advanced = $select_basic . ',
	CONCAT(CASE WHEN (CalculatedDailyNeeds.New = 1) THEN
			"[Neu] "
		ELSE
			""
		END,CASE WHEN (ItemsBase.BundleType = "bundle") THEN
			"[Bundle] "
		WHEN (ItemsBase.BundleType = "bundle_item") THEN
			"[Bundle Artikel] "
		ELSE
			""
		END, ItemsBase.Name, CASE WHEN (AttributeValueSets.AttributeValueSetID IS NOT null) THEN
			CONCAT(", ", AttributeValueSets.AttributeValueSetName)
		ELSE
			""
	END) AS Name,
	ItemsBase.Name AS SortName,
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
	CalculatedDailyNeeds.New,
	ItemsWarehouseSettings.ReorderLevel,
	ItemsWarehouseSettings.StockTurnover,
	ItemsWarehouseSettings.MaximumStock,
	ItemSuppliers.SupplierDeliveryTime,
	ItemSuppliers.SupplierMinimumPurchase,
	WritePermissions.WritePermission,
	WritePermissions.Error AS WritePermissionError,
	WriteBackSuggestion.Valid,
    WriteBackSuggestion.ReorderLevelError,
    WriteBackSuggestion.SupplierMinimumPurchaseError,
    WriteBackSuggestion.ReorderLevel AS ProposedReorderLevel,
    WriteBackSuggestion.SupplierMinimumPurchase AS ProposedSupplierMinimumPurchase,
    WriteBackSuggestion.MaximumStock  AS ProposedMaximumStock,
    CurrentStocks.NetStock' . PHP_EOL;

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
    END = WriteBackSuggestion.AttributeValueSetID
LEFT JOIN CurrentStocks
	ON ItemsBase.ItemID = CurrentStocks.ItemID
	AND CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
        "0"
    ELSE
        AttributeValueSets.AttributeValueSetID
    END = CurrentStocks.AttributeValueSetID
    AND ItemsBase.MainWarehouseID = CurrentStocks.WarehouseID' . PHP_EOL;

$where = 'WHERE
	ItemsBase.Inactive = 0' . PHP_EOL;

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

if ($filterMarking1D) {
	$where .= " AND Marking1ID IN ($filterMarking1D)" . PHP_EOL;
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
	$staticData = new StaticData($row);
	$dynamicData = new DynamicData($row);

	$dailyNeed_string = abs($dynamicData -> dailyNeed) < 0.01 ? '' : number_format($dynamicData -> dailyNeed, 2);
	$monthlyNeed_string = abs($dynamicData -> dailyNeed) < 0.01 ? '' : number_format($dynamicData -> dailyNeed * 30, 2);

	if ($dynamicData -> valid) {
		$reorderLevel_string = $dynamicData -> proposedReorderLevel . ':' . $staticData -> reorderLevel;
		$supplierMinimumPurchase_string = $dynamicData -> proposedSupplierMinimumPurchase . ':' . $staticData -> supplierMinimumPurchase;
		$maxStockSuggestion_string = $dynamicData -> proposedMaximumStock . ':' . $staticData -> maximumStock;
	} else {
		$reorderLevel_string = isset($dynamicData -> reorderLevelError) ? $dynamicData -> reorderLevelError : $dynamicData -> proposedReorderLevel . ':' . $staticData -> reorderLevel;
		if (isset($dynamicData -> supplierMinimumPurchaseError)) {
			$supplierMinimumPurchase_string = $dynamicData -> supplierMinimumPurchaseError;
			$maxStockSuggestion_string = $dynamicData -> supplierMinimumPurchaseError;
		} else {
			$supplierMinimumPurchase_string = $dynamicData -> proposedSupplierMinimumPurchase . ':' . $staticData -> supplierMinimumPurchase;
			$maxStockSuggestion_string = $dynamicData -> proposedMaximumStock . ':' . $staticData -> maximumStock;
		}
	}

	$xml .= "\t<row id='$staticData->itemID-0-$staticData->attributeValueSetID'>
        <cell><![CDATA[$staticData->itemID]]></cell>
        <cell><![CDATA[$staticData->itemNo]]></cell>
        <cell><![CDATA[$staticData->name]]></cell>
        <cell><![CDATA[$dynamicData->rawDataA]]></cell>
        <cell><![CDATA[$dynamicData->rawDataB]]></cell>
        <cell><![CDATA[$monthlyNeed_string]]></cell>
        <cell><![CDATA[$dailyNeed_string]]></cell>
        <cell><![CDATA[$staticData->currentStock]]></cell>
        <cell><![CDATA[$staticData->marking1ID]]></cell>
        <cell><![CDATA[{$dynamicData->writePermissionPrefix}:{$reorderLevel_string}]]></cell>
        <cell><![CDATA[{$dynamicData->writePermissionPrefix}:{$maxStockSuggestion_string}]]></cell>
        <cell><![CDATA[{$dynamicData->writePermissionPrefix}:{$supplierMinimumPurchase_string}]]></cell>
        <cell><![CDATA[$staticData->vpe]]></cell>
        <cell><![CDATA[$dynamicData->lastUpdate]]></cell>
	</row>\n";
}
ob_end_clean();

$xml .= '</rows>';
echo $xml;
