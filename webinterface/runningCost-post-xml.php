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
	$normalizedDate = new DateTime($fromDate -> format('Ym01'));
	$result[] = $normalizedDate -> format('Ymd');
	for ($i = 1; $i <= $nrOfMonthDates; $i++) {
		$result[] = $normalizedDate -> sub(new DateInterval('P1M')) -> format('Ymd');
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
		$warehouse['average'] = array('absolute' => null, 'percentage' => null);
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

	// calculate averages
	$maxDate = count($months) - 1;
	foreach ($result as &$warehouse) {
		$allMonthsTotalAbsolute = 0;
		$allMonthsTotalPercentage = 0;
		for ($date = 0; $date < $maxDate; $date++) {
			$allMonthsTotalAbsolute += $warehouse[$months[$date]]['absolute'];
			$allMonthsTotalPercentage += $warehouse[$months[$date]]['percentage'];
		}
		$warehouse['average']['absolute'] = number_format($allMonthsTotalAbsolute / $maxDate, 2,'.','');
		$warehouse['average']['percentage'] = number_format($allMonthsTotalPercentage / $maxDate, 2,'.','');
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
		<cell><![CDATA[" . $currentMonth -> format('M. Y') . "]]></cell>
		<cell><![CDATA[{$data['-1'][$monthDate]['percentage']}]]></cell>\n";
	foreach ($warehouses as $warehouse) {
		$xml .= "\t\t<cell><![CDATA[{$data[$warehouse['id']][$monthDate]['absolute']}]]></cell>\n";
		$xml .= "\t\t<cell><![CDATA[{$data[$warehouse['id']][$monthDate]['percentage']}]]></cell>\n";
	}

	$xml .= "\t</row>\n";
}

// append average for each month exept the current one
$xml .= "\t<row id='Average'>
		<cell><![CDATA[Durchschnitt]]></cell>
		<cell><![CDATA[{$data['-1']['average']['percentage']}]]></cell>\n";

foreach ($warehouses as $warehouse) {
	$xml .= "\t\t<cell><![CDATA[{$data[$warehouse['id']]['average']['absolute']}]]></cell>\n";
	$xml .= "\t\t<cell><![CDATA[{$data[$warehouse['id']]['average']['percentage']}]]></cell>\n";
}

$xml .= "\t</row>\n";

$xml .= "</rows>\n";
echo $xml;
?>