<?php
$page = isset($_POST['page']) ? $_POST['page'] : 1;
$rp = isset($_POST['rp']) ? $_POST['rp'] : 10;
$sortname = isset($_POST['sortname']) ? $_POST['sortname'] : 'ItemID';
$sortorder = isset($_POST['sortorder']) ? $_POST['sortorder'] : 'asc';
$query = isset($_POST['query']) ? $_POST['query'] : false;
$qtype = isset($_POST['qtype']) ? $_POST['qtype'] : false;
$warehouseID = isset($_POST['warehouseID']) ? $_POST['warehouseID'] : 1;

switch ($qtype) {
	case 'ItemID':
		$qtype = 'ItemsBase.ItemID';
		break;
	default:
		break;
}

switch ($sortname) {
	case 'Date':
		$sortname = 'LastUpdate';
		break;
	case 'MonthlyNeed':
		$sortname = 'DailyNeed';
		break;
	case 'Marking':
		$sortname = 'Marking1ID';
		break;
	default:
		
		break;
}

ob_start();
require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';


function getMaxRows($query) {
	return DBQuery::getInstance() -> select($query) -> getNumRows();
}

function getTextLike($columName, $value){
	return $columName.' LIKE "%'.$value.'%" ';
}
function getIntLike($columName, $value){
	return $columName.' = "'.$value.'" ';
}

$select_basic = '				SELECT
					ItemsBase.ItemID,
					ItemsBase.Name,
					CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
						"0"
					ELSE
						AttributeValueSets.AttributeValueSetID
					END AttributeValueSetID';
					
$select_advanced = $select_basic . ',
					ItemsBase.ItemNo,
					ItemsBase.Marking1ID,
					CalculatedDailyNeeds.DailyNeed,
					CalculatedDailyNeeds.LastUpdate,
					CalculatedDailyNeeds.Quantities,
					CalculatedDailyNeeds.Skipped,
					ItemsWarehouseSettings.ReorderLevel,
					ItemsWarehouseSettings.StockTurnover,
					CASE WHEN (AttributeValueSets.AttributeValueSetName IS null) THEN
						""
					ELSE
						AttributeValueSets.AttributeValueSetName
					END AttributeValueSetName';
					
$from_basic = '	FROM ItemsBase
				LEFT JOIN AttributeValueSets
					ON ItemsBase.ItemID = AttributeValueSets.ItemID
				LEFT JOIN ItemsWarehouseSettings
                    ON ItemsBase.ItemID = ItemsWarehouseSettings.ItemID
                    AND CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
                        "0"
                    ELSE
                        AttributeValueSets.AttributeValueSetID
                    END = ItemsWarehouseSettings.AttributeValueSetID';
					
$from_advanced = $from_basic . '
				LEFT JOIN CalculatedDailyNeeds
                    ON ItemsBase.ItemID = CalculatedDailyNeeds.ItemID
                    AND CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
                        "0"
                    ELSE
                        AttributeValueSets.AttributeValueSetID
                    END = CalculatedDailyNeeds.AttributeValueSetID';
					
$where = '
				WHERE
					ItemsWarehouseSettings.WarehouseID = ' . $warehouseID. ' ';

if ($query && $qtype){
	if (strpos($query,',') !== false){
		$queries = explode(',',str_replace(' ', '', $query));

		$where .= '
				AND (';
		for ($i = 0; $i < count($queries);$i++){
			if ($i != 0){
				$where .= '
				OR';
			} {
				$where .= '
					'.($qtype == 'ItemsBase.ItemID' ? getIntLike($qtype,$queries[$i]) : getTextLike($qtype,$queries[$i]));
			}
		}
		$where .= '
				)';

	} else {
		$where .= '
				AND
					'.($qtype == 'ItemsBase.ItemID' ? getIntLike($qtype,$query) : getTextLike($qtype,$query));
	}
}

$sort = '
				ORDER BY ' . $sortname . ' ' . $sortorder;

$start = (($page-1) * $rp);

$limit = '
				LIMIT ' . $start . ', ' . $rp;

$sql = $select_advanced . $from_advanced . $where . $sort . $limit;

$result = DBQuery::getInstance()->select($sql);

$total = getMaxRows($select_basic . $from_basic . $where);

$rows = array();

while ($row = $result -> fetchAssoc()) {
	$rows[] = $row;
}
ob_end_clean();

header('Content-type: text/xml');
$xml = '<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL;
$xml .= '<rows>'.PHP_EOL;
$xml .= '<page>'.$page.'</page>'.PHP_EOL;
$xml .= '<total>'.$total.'</total>'.PHP_EOL;
foreach($rows AS $row){
	$dailyNeed = floatval($row['DailyNeed']);
	$monthlyNeed = $dailyNeed * 30;
	$reorderLevel = intval($row['ReorderLevel']);
	$stockTurnover = intval($row['StockTurnover']);
	

	$name_string = intval($row['AttributeValueSetID']) == 0 ? $row['Name'] : $row['Name'] . ', ' . $row['AttributeValueSetName'];
	$dailyNeed_string = $dailyNeed == 0 ? '' : $dailyNeed;
	$monthlyNeed_string = $monthlyNeed == 0 ? '' : $monthlyNeed;
	$stockTurnover_string = $stockTurnover == 0 ? 'keine Lagerreichweite konfiguriert!' : ceil($stockTurnover * $dailyNeed) . ' (' . $reorderLevel . ')';
	$rawData_string = isset($row['Quantities']) ? $row['Skipped'] . ':' . $row['Quantities'] : null;
	$date_string = date('d.m.y, H:i:s', $row['LastUpdate']);
	
	$xml .= '<row id="'.$row['ItemID']. '-0-'.$row['AttributeValueSetID'].'">'.PHP_EOL;
	$xml .= '<cell><![CDATA['.$row['ItemID'].']]></cell>'.PHP_EOL;
	$xml .= '<cell><![CDATA['.$name_string.']]></cell>'.PHP_EOL;
	$xml .= '<cell><![CDATA[]]>' . $rawData_string . '</cell>' . PHP_EOL;
	$xml .= '<cell><![CDATA['.$monthlyNeed_string.']]></cell>'.PHP_EOL;
	$xml .= '<cell><![CDATA['.$dailyNeed_string.']]></cell>'.PHP_EOL;
	$xml .= '<cell><![CDATA['.$row['Marking1ID'].']]></cell>'.PHP_EOL;
	$xml .= '<cell><![CDATA['.$stockTurnover_string.']]></cell>'.PHP_EOL;
	$xml .= '<cell><![CDATA[]]>1</cell>'.PHP_EOL;
	$xml .= '<cell><![CDATA[]]>1</cell>'.PHP_EOL;
	$xml .= '<cell><![CDATA[]]>1</cell>'.PHP_EOL;
	$xml .= '<cell><![CDATA[' . $date_string . ']]></cell>' . PHP_EOL;
	/*	$xml .= "<cell><![CDATA[".utf8_encode($row['name'])."]]></cell>";
	//$xml .= "<cell><![CDATA[".print_r($_POST,true)."]]></cell>";
	$xml .= "<cell><![CDATA[".utf8_encode($row['printable_name'])."]]></cell>";
	$xml .= "<cell><![CDATA[".utf8_encode($row['iso3'])."]]></cell>";
	$xml .= "<cell><![CDATA[".utf8_encode($row['numcode'])."]]></cell>";*/
	$xml .= '</row>';
}

$xml .= '</rows>';
echo $xml;
