<?php

require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/soap/experiment_loader/NetXpressSoapExperimentLoader.class.php';

ob_start();
$startTime = microtime(true);

if (isset($_GET['action'])) {
	switch($_GET['action']) {
		case 'update' :
			// update order db
			NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'SearchOrders'));

			// update article db
			NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetItemsBase'));
			NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetItemsWarehouseSettings'));
			NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetItemsSuppliers'));

			ob_end_clean();
			echo json_encode(array('executionTime' => number_format(microtime(true) - $startTime, 2), 'executionTimeUnit' => 'seconds', 'task' => 'updating'));
			break;
		case 'calculate' :
			// calculate daily need
			NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'CalculateHistogram', 'CalculateHistogram'));

			// calculate write back data
			NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'CalculateWriteBackData', 'CalculateWriteBackData'));

			// determine write permissions
			NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'DetermineWritePermissions', 'DetermineWritePermissions'));

			ob_end_clean();
			echo json_encode(array('executionTime' => number_format(microtime(true) - $startTime, 2), 'executionTimeUnit' => 'seconds', 'task' => 'calculation'));
			break;
		case 'writeBack' :
			NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'SetItemsSuppliers'));
			NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'SetItemsWarehouseSettings'));

			ob_end_clean();
			echo json_encode(array('executionTime' => number_format(microtime(true) - $startTime, 2), 'executionTimeUnit' => 'seconds', 'task' => 'write back'));
			break;
	}
}
?>