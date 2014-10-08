<?php

require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/soap/experiment_loader/NetXpressSoapExperimentLoader.class.php';
require_once ROOT . 'includes/GetConfig.php';

if (Config::get('CalculationActive') === 1) {
	// update order db
	NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'SearchOrders'));

	// update article db
	NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetItemsBase'));
	NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetItemsPriceLists'));
	NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetItemsWarehouseSettings'));
	NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetItemsSuppliers'));

	// update warehouse db
	NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetWarehouseList'));

	// update stocks
	NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetCurrentStocks'));

	// update sales order referrer db
	NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'GetSalesOrderReferrer'));

	// calculate total netto values for last 6 months
	// TODO would be better as a daemon action
	NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'CalculateTotalNetto', 'CalculateTotalNetto'));

	// calculate daily need
	NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'CalculateDailyNeed', 'CalculateDailyNeed'));

	// calculate write back data
	NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'CalculateWriteBackData', 'CalculateWriteBackData'));

	// calculate amazon quantites
	NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'CalculateAmazonQuantities', 'CalculateAmazonQuantities'));

	// calculate amazon value k
	NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'CalculateAmazonWeightenedRunningCosts', 'CalculateAmazonWeightenedRunningCosts'));

	// determine write permissions
	NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'DetermineWritePermissions', 'DetermineWritePermissions'));

	// write back data
	if (Config::get('WritebackActive') === 1) {
		NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'SetItemsSuppliers'));
		NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'SetItemsWarehouseSettings'));
	} else {
		Logger::instance('simpleExecutionScript') -> debug('Writeback is inactive, skipping...');
	}
} else {
	Logger::instance('simpleExecutionScript') -> debug('Calculation is inactive, skipping...');
}
?>