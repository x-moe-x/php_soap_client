<?php
require_once 'includes/basic_forward.inc.php';
require_once 'includes/smarty.inc.php';
/*
$page = isset($_POST['page']) ? $_POST['page'] : 1;
$rp = isset($_POST['rp']) ? $_POST['rp'] : 10;
$sortname = isset($_POST['sortname']) ? $_POST['sortname'] : 'ItemID';
$sortorder = isset($_POST['sortorder']) ? $_POST['sortorder'] : 'asc';
$query = isset($_POST['query']) ? $_POST['query'] : false;
$qtype = isset($_POST['qtype']) ? $_POST['qtype'] : false;

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
	CONCAT(CASE WHEN (ItemsBase.BundleType = "bundle") THEN
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
	ItemsBase.Marking1ID' . PHP_EOL;

$from_basic = 'FROM ItemsBase
LEFT JOIN AttributeValueSets
	ON ItemsBase.ItemID = AttributeValueSets.ItemID' . PHP_EOL;

$from_advanced = $from_basic . PHP_EOL;

$where = 'WHERE
	ItemsBase.Inactive = 0' . PHP_EOL;

$sort = 'ORDER BY ' . $sortname . ' ' . $sortorder . PHP_EOL;

$start = (($page - 1) * $rp);

$limit = 'LIMIT ' . $start . ', ' . $rp;

$sql = $select_advanced . $from_advanced . $where . $sort . $limit;

$result = DBQuery::getInstance() -> select($sql);

$total = getMaxRows($select_basic . $from_basic . $where);

 header('Content-type: text/xml');
 $xml = "<?xml version='1.0' encoding='utf-8'?>\n<rows>\n\t<page>{$page}</page>\n\t<total>{$total}</total>\n";
 while ($row = $result -> fetchAssoc()) {

 $xml .= "\t<row id='{$row['ItemID']}-0-{$row['AttributeValueSetID']}'>
 <cell><![CDATA[{$row['ItemID']}]]></cell>
 <cell><![CDATA[{$row['ItemNo']}]]></cell>
 <cell><![CDATA[{$row['Name']}]]></cell>
 <cell><![CDATA[{$row['Marking1ID']}]]></cell>
 <cell><![CDATA[]]></cell>
 <cell><![CDATA[]]></cell>
 <cell><![CDATA[]]></cell>
 <cell><![CDATA[]]></cell>
 <cell><![CDATA[]]></cell>
 <cell><![CDATA[]]></cell>
 <cell><![CDATA[]]></cell>
 <cell><![CDATA[]]></cell>
 <cell><![CDATA[]]></cell>
 </row>\n";
 }
 ob_end_clean();

 $xml .= '</rows>';
 echo $xml;
 */

header('Content-type: text/xml');
$smarty -> display('amazon-post.tpl');
?>
