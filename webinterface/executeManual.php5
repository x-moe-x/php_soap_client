<?php

require_once 'includes/basic_forward.inc.php';
require_once ROOT . 'lib/soap/experiment_loader/NetXpressSoapExperimentLoader.class.php';
require_once ROOT . 'lib/log/Logger.class.php';
require_once ROOT . 'lib/db/DBQuery.class.php';

ob_start();
$startTime = microtime(true);

if (isset($_GET['action'])) {
	switch($_GET['action']) {
		case 'update' :
			Logger::instance('executeManual.php') -> debug('manually executing "update"');

			// update order db
			NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'SearchOrders'));

			// update article db
			NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetItemsBase'));
			NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetItemsWarehouseSettings'));
			NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetItemsSuppliers'));

			ob_end_clean();
			echo 'updating took ' . number_format(microtime(true) - $startTime, 2) . ' seconds';
			break;
		case 'calculate' :
			Logger::instance('executeManual.php') -> debug('manually executing "calculate"');
			// calculate daily need
			NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'CalculateHistogram', 'CalculateHistogram'));

			// calculate write back data
			NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'CalculateWriteBackData', 'CalculateWriteBackData'));

			// determine write permissions
			NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'DetermineWritePermissions', 'DetermineWritePermissions'));

			ob_end_clean();
			echo 'calculation took ' . number_format(microtime(true) - $startTime, 2) . ' seconds';
			break;
		case 'writeBack' :
			Logger::instance('executeManual.php') -> debug('manually executing "writeBack"');

			NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'SetItemsSuppliers'));
			NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'SetItemsWarehouseSettings'));

			ob_end_clean();
			echo 'write back took ' . number_format(microtime(true) - $startTime, 2) . ' seconds';
			break;
		case 'resetArticles' :
			Logger::instance('executeManual.php') -> debug('manually executing "resetArticles"');
			DBQuery::getInstance() -> truncate('TRUNCATE `ItemsBase`');
			DBQuery::getInstance() -> delete('DELETE FROM MetaLastUpdate WHERE Function = "SoapCall_GetItemsBase"');
			DBQuery::getInstance() -> truncate('TRUNCATE `ItemsWarehouseSettings`');
			DBQuery::getInstance() -> truncate('TRUNCATE `ItemSuppliers`');
			ob_end_clean();
			break;
		case 'resetOrders' :
			Logger::instance('executeManual.php') -> debug('manually executing "resetOrders"');
			DBQuery::getInstance() -> truncate('TRUNCATE `OrderHead`');
			DBQuery::getInstance() -> delete('DELETE FROM MetaLastUpdate WHERE Function = "SoapCall_SearchOrders"');
			DBQuery::getInstance() -> truncate('TRUNCATE `OrderItem`');
			ob_end_clean();
			break;
		default :
			ob_end_clean();
			echo 'action ' . $_GET['action'] . ' is undefined';
	}
} else {
	ob_end_clean();
	echo 'no action specified';
}
?>