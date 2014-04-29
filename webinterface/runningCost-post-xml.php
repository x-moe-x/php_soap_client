<?php
require_once 'includes/helper_functions.inc.php';

$page = isset($_POST['page']) ? $_POST['page'] : 1;
$rp = isset($_POST['rp']) ? $_POST['rp'] : 10;
$sortname = isset($_POST['sortname']) ? $_POST['sortname'] : 'month';
$sortorder = isset($_POST['sortorder']) ? $_POST['sortorder'] : 'asc';
$query = isset($_POST['query']) ? $_POST['query'] : false;
$qtype = isset($_POST['qtype']) ? $_POST['qtype'] : false;

function getMonthDates(DateTime $fromDate, $nrOfMonthDates = 6) {
	$result = array();
	$result[] = $fromDate -> format('Ym01');
	for ($i = 1; $i <= $nrOfMonthDates; $i++) {
		$result[] = $fromDate -> sub(new DateInterval('P1M')) -> format('Ym01');
	}
	return array_reverse($result);
}

function collectData(array $months, array $warehouses) {
	// prepare empty table
	$result = array(-1 => array());

	foreach ($warehouses as $warehouse) {
		$result[$warehouse['id']] = array();
	}

	foreach ($result as &$warehouse) {
		foreach ($months as $month) {
			$warehouse[$month] = array('absolute' => null, 'percentage' => null);
		}
	}

	// get data from db
	$query = 'SELECT
	RunningCosts.Date,
	RunningCosts.WarehouseID,
	RunningCosts.AbsoluteAmount,
	RunningCosts.Percentage,
	TotalNetto.TotalNetto
FROM
	RunningCosts
LEFT JOIN
	TotalNetto
ON
	(RunningCosts.Date = TotalNetto.Date AND RunningCosts.WarehouseID = TotalNetto.WarehouseID)
WHERE
	RunningCosts.Date IN (' . implode(',', $months) . ')
AND
	RunningCosts.WarehouseID IN (-1,' . implode(',', array_map(function($warehouse) {
		return $warehouse['id'];
	}, $warehouses)) . ')';
	$dbResult = DBQuery::getInstance() -> select($query);

	// populate table
	while ($runningCostRecord = $dbResult -> fetchAssoc()) {
		if (array_key_exists($runningCostRecord['WarehouseID'], $result) && array_key_exists($runningCostRecord['Date'], $result[$runningCostRecord['WarehouseID']])) {
			if (intval($runningCostRecord['WarehouseID']) === -1) {
				$result[$runningCostRecord['WarehouseID']][$runningCostRecord['Date']]['percentage'] = $runningCostRecord['Percentage'];
			} else {
				$result[$runningCostRecord['WarehouseID']][$runningCostRecord['Date']]['absolute'] = $runningCostRecord['AbsoluteAmount'];
				if (floatval($runningCostRecord['AbsoluteAmount']) > 0) {
					$result[$runningCostRecord['WarehouseID']][$runningCostRecord['Date']]['percentage'] = number_format(100 * $runningCostRecord['AbsoluteAmount'] / $runningCostRecord['TotalNetto'], 2);
				}
			}
		}
	}
	return $result;
}

ob_start();
$months = getMonthDates(new DateTime());
$warehouses = getWarehouseList();
$data = collectData($months, $warehouses);
ob_end_clean();

header('Content-type: text/xml');
$xml = "<?xml version='1.0' encoding='utf-8'?>\n<rows>\n\t<page>1</page>\n\t<total>1</total>\n";
foreach ($months as $monthDate) {
	$currentMonth = new DateTime($monthDate);
	$xml .= "\t<row id='$monthDate'>
		<cell><![CDATA[".$currentMonth->format('M. Y')."]]></cell>
		<cell><![CDATA[{$data['-1'][$monthDate]['percentage']}]]></cell>\n";
	foreach ($warehouses as $warehouse) {
		$xml .= "\t\t<cell><![CDATA[{$data[$warehouse['id']][$monthDate]['absolute']}]]></cell>\n";
		$xml .= "\t\t<cell><![CDATA[{$data[$warehouse['id']][$monthDate]['percentage']}]]></cell>\n";
	}

	$xml .= "\t</row>\n";
}

$xml .= "</rows>\n";
echo $xml;
?>