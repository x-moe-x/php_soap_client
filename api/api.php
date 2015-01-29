<?php
require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'submodules/epiphany/src/Epi.php';
require_once 'ApiStock.class.php';
require_once 'ApiGeneralCosts.class.php';
require_once 'ApiRunningCosts.class.php';
require_once 'ApiAmazon.class.php';
require_once 'ApiExecute.class.php';
require_once 'ApiWarehouseGrouping.class.php';
require_once 'ApiEanProcessing.class.php';

Epi::setSetting('exceptions', true);
Epi::init('route');

//register execution api calls
getRoute() -> get('/execute/(\w+)', array('ApiExecute', 'sheduleTTaskJSON'));
getRoute() -> get('/executeWithOutput/(\w+)', array('ApiExecute', 'sheduleTTaskWithOutputJSON'));

// register stock api calls
getRoute() -> get('/config/stock', array('ApiStock', 'getConfigJSON'));
getRoute() -> get('/config/stock/(\w+)', array('ApiStock', 'getConfigJSON'));
getRoute() -> put('/config/stock/(\w+)/(\w+|\d+|\d+\.\d+)', array('ApiStock', 'setConfigJSON'));

// register general costs api callsGeneralCosts (old)
// getRoute() -> get('/generalCost/(\d+|-\d+)/(\d+)', array('ApiGeneralCosts', 'getCostsJSON'));
// getRoute() -> put('/generalCost/(\d+|-\d+)/(\d+)/(\w+|\d+|\d+\.\d+)', array('ApiGeneralCosts', 'setCostsJSON'));

// register general costs api callsGeneralCosts
getRoute() -> put('/generalCosts/(\d+)/(\d+|\d+\.\d+)', array('ApiGeneralCosts', 'setGeneralCostsJSON'));

// register running costs api calls
getRoute() -> get('/runningCosts/average', array('ApiHelper', 'getAverageCostsJSON'));
getRoute() -> put('/runningCosts/(\d+)/(\d+)/(\d+|\d+\.\d+)', array('ApiRunningCosts', 'setRunningCostsJSON'));

//register last update call
getRoute() -> get('/config/lastUpdate', array('ApiHelper', 'getLastUpdateJSON'));
getRoute() -> get('/config/lastUpdate/(\w+)', array('ApiHelper', 'getLastUpdateJSON'));

// register ean api calls
getRoute() -> get('/config/ean', array('ApiEanProcessing', 'getConfigJSON'));
getRoute() -> get('/config/ean/(\w+)', array('ApiEanProcessing', 'getConfigJSON'));
getRoute() -> put('/config/ean/(\w+)/(\d+)', array('ApiEanProcessing', 'setConfigJSON'));

// register amazon api calls
getRoute() -> get('/config/amazon', array('ApiAmazon', 'getConfigJSON'));
getRoute() -> get('/config/amazon/(\w+)', array('ApiAmazon', 'getConfigJSON'));
getRoute() -> put('/config/amazon/(\w+)/(\w+|\d+|\d+\.\d+)', array('ApiAmazon', 'setConfigJSON'));

getRoute() -> get('/amazonPrice/(\d+)-\d+-\d+', array('ApiAmazon', 'getPriceJSON'));
getRoute() -> get('/amazonPrice/(\d+)', array('ApiAmazon', 'getPriceJSON'));
getRoute() -> put('/amazonPrice/(\d+)-\d+-\d+/(\w+|\d+|\d+\.\d+)', array('ApiAmazon', 'setPriceJSON'));

// register warehouse grouping api calls
getRoute() -> get('/config/warehouseGrouping/(\w+)', array('ApiWarehouseGrouping', 'getConfigJSON'));
getRoute() -> put('/config/warehouseGrouping/(\w+)/(\d+)', array('ApiWarehouseGrouping', 'setConfigJSON'));

getRoute() -> get('/warehouseGrouping/warehouses', array('ApiWarehouseGrouping', 'getWarehouseListJSON'));
getRoute() -> get('/warehouseGrouping', array('ApiWarehouseGrouping', 'getGroupsJSON'));
getRoute() -> put('/warehouseGrouping/([\w-]+)', array('ApiWarehouseGrouping', 'createGroupJSON'));

getRoute() -> put('/warehouseGrouping/(\d+)/([\w-]+)', array('ApiWarehouseGrouping', 'changeGroupJSON'));
getRoute() -> put('/warehouseGrouping/delete/(\d+)', array('ApiWarehouseGrouping', 'deleteGroupJSON'));
getRoute() -> put('/warehouseGrouping/warehouseToGroup/(\d+)/(\d+)', array('ApiWarehouseGrouping', 'addWarehouseToGroupJSON'));
getRoute() -> put('/warehouseGrouping/warehouseToGroup/delete/(\d+)', array('ApiWarehouseGrouping', 'deleteWarehouseGroupAssociationJSON'));

try {
	getRoute() -> run();
} catch (EpiException $e) {
	if (strpos($e -> getMessage(), 'Could not find route') === 0) {
		http_response_code(404);
		echo 'Error 404: ' . $e -> getMessage();
	} else {
		echo 'Unknown error: ' . $e -> getMessage();
	}
}
?>
