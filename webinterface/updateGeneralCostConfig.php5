<?php
ob_start();

require_once 'includes/basic_forward.inc.php';
require_once 'includes/helper_functions.inc.php';
require_once ROOT . 'includes/GetConfig.php';

$result = array('Message' => null, 'Value' => null);

if (isset($_POST['key']) && isset($_POST['value'])) {
	// extract warehouse id & date
	if (preg_match('/(?:generalCosts_manual|warehouseCost_manual_(?\'warehouseId\'\d))_(?\'date\'\d{6}01)/', $_POST['key'], $matches) && preg_match('/(?:(?:20\d{2})(0[1-9]|1[0-2])(?:01))/', $matches['date'])) {

		// if warehouse id = -1 ...
		if ($matches['warehouseId'] === '') {
			// ... then update corresponding percentage-value
			// @formatter:off
			DBQuery::getInstance() -> insert('INSERT INTO `RunningCosts`' . DBUtils::buildInsert(array(
				'Date' =>			$matches['date'],
				'WarehouseID' =>	-1,
				'AbsoluteAmount' =>	'NULL',
				'Percentage' =>		$_POST['value']
			)).'ON DUPLICATE KEY UPDATE' . DBUtils::buildOnDuplicateKeyUpdate(array(
				'AbsoluteAmount' =>	'NULL',
				'Percentage' =>		$_POST['value']
			)));
			// @formatter:on
		}
		// otherwise: if warehouse in warehouse list TODO implement check!
		else if (array_key_exists($matches['warehouseId'], getWarehouseList())) {
			// ... then update corresponding absolute-value and clear corresponding percentage value
			// @formatter:off
			DBQuery::getInstance() -> insert('INSERT INTO `RunningCosts`' . DBUtils::buildInsert(array(
				'Date' =>			$matches['date'],
				'WarehouseID' =>	$matches['warehouseId'],
				'AbsoluteAmount' =>	$_POST['value'],
				'Percentage' =>		'NULL'
			)).'ON DUPLICATE KEY UPDATE' . DBUtils::buildOnDuplicateKeyUpdate(array(
				'AbsoluteAmount' =>	$_POST['value'],
				'Percentage' =>		'NULL'
			)));
			// @formatter:on
		} else {
			// ... otherwise report error
			$result['Message'] = 'wrong warehouse id';
		}

	} else {
		$result['Message'] = 'wrong argument format';
	}

} else {
	$result['Message'] = 'insufficient arguments';
}
ob_end_clean();

// generate json
echo json_encode($result);
?>