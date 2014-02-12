<?php
$page = isset($_POST['page']) ? $_POST['page'] : 1;
$rp = isset($_POST['rp']) ? $_POST['rp'] : 10;
$sortname = isset($_POST['sortname']) ? $_POST['sortname'] : 'ItemID';
$sortorder = isset($_POST['sortorder']) ? $_POST['sortorder'] : 'asc';
$query = isset($_POST['query']) ? $_POST['query'] : false;
$qtype = isset($_POST['qtype']) ? $_POST['qtype'] : false;
$warehouseID = isset($_POST['warehouseID']) ? $_POST['warehouseID'] : 1;

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
    END = WritePermissions.AttributeValueSetID' . PHP_EOL;

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
	$dailyNeed = floatval($row['DailyNeed']);
	$monthlyNeed = $dailyNeed * 30;
	$reorderLevel = intval($row['ReorderLevel']);
	$stockTurnover = intval($row['StockTurnover']);
	$supplierDeliveryTime = intval($row['SupplierDeliveryTime']);
	$vpe = intval($row['VPE']);
	$vpe = $vpe == 0 ? 1 : $vpe;
	$proposedReorderLevel = ceil($supplierDeliveryTime * $dailyNeed);
	$orderSuggestion = ceil($stockTurnover * $dailyNeed);
	$orderSuggestion = $orderSuggestion % $vpe == 0 ? $orderSuggestion : $orderSuggestion + $vpe - $orderSuggestion % $vpe;
	$name_string = $row['BundleType'] === 'bundle' ? '[Bundle] ' : '';

	$name_string .= intval($row['AttributeValueSetID']) == 0 ? $row['Name'] : $row['Name'] . ', ' . $row['AttributeValueSetName'];
	$dailyNeed_string = $dailyNeed == 0 ? '' : $dailyNeed;
	$monthlyNeed_string = $monthlyNeed == 0 ? '' : $monthlyNeed;
	$reorderLevel_string = $supplierDeliveryTime == 0 ? "keine Lieferzeit konfiguriert" : $proposedReorderLevel . ':' . $reorderLevel;
	$orderSuggestion_string = $stockTurnover == 0 ? 'keine Lagerreichweite konfiguriert!' : $orderSuggestion . ':' . $row['SupplierMinimumPurchase'];
	$maxStockSuggestion_string = $stockTurnover == 0 ? 'keine Lagerreichweite konfiguriert!' : $orderSuggestion * 2 . ':' . $row['MaximumStock'];
	$rawDataA_string = isset($row['QuantitiesA']) && $row['QuantitiesA'] !== '0' ? $row['SkippedA'] . ':' . $row['QuantitiesA'] : null;
	$rawDataB_string = isset($row['QuantitiesB']) && $row['QuantitiesB'] !== '0' ? $row['SkippedB'] . ':' . $row['QuantitiesB'] : null;
	$date_string = isset($row['LastUpdate']) ? date('d.m.y, H:i:s', $row['LastUpdate']) : null;
	$write_permission_prefix = intval($row['WritePermission']) === 1 ? 'w' : (intval($row['Error']) === 1 ? 'e' : 'x');

	$xml .= "\t<row id='{$row['ItemID']}-0-{$row['AttributeValueSetID']}'>
        <cell><![CDATA[{$row['ItemID']}]]></cell>
        <cell><![CDATA[{$row['ItemNo']}]]></cell>
        <cell><![CDATA[{$name_string}]]></cell>
        <cell><![CDATA[]]>{$rawDataA_string}</cell>
        <cell><![CDATA[]]>{$rawDataB_string}</cell>
        <cell><![CDATA[{$monthlyNeed_string}]]></cell>
        <cell><![CDATA[{$dailyNeed_string}]]></cell>
        <cell><![CDATA[{$row['Marking1ID']}]]></cell>
        <cell><![CDATA[{$write_permission_prefix}:{$reorderLevel_string}]]></cell>
        <cell><![CDATA[]]>{$write_permission_prefix}:{$maxStockSuggestion_string}</cell>
        <cell><![CDATA[]]>{$write_permission_prefix}:{$orderSuggestion_string}</cell>
        <cell><![CDATA[]]>{$vpe}</cell>
        <cell><![CDATA[]]>1</cell>
        <cell><![CDATA[]]>1</cell>
        <cell><![CDATA[{$date_string}]]></cell>
	</row>\n";
}
ob_end_clean();

$xml .= '</rows>';
echo $xml;
