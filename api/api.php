<?php
require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'submodules/epiphany/src/Epi.php';
require_once 'ApiStock.class.php';
require_once 'ApiGeneralCosts.class.php';
require_once 'ApiAmazon.class.php';

Epi::setSetting('exceptions', true);
Epi::init('route');

// register stock api calls
getRoute() -> get('/config/stock', array('ApiStock', 'getConfigJSON'));
getRoute() -> get('/config/stock/(\w+)', array('ApiStock', 'getConfigJSON'));
getRoute() -> put('/config/stock/(\w+)/(\w+|\d+|\d+\.\d+)', array('ApiStock', 'setConfigJSON'));

// register general costs api calls
getRoute() -> get('/generalCost/(\d+|-\d+)/(\d+)', array('ApiGeneralCosts', 'getCostsJSON'));
getRoute() -> put('/generalCost/(\d+|-\d+)/(\d+)/(\w+|\d+|\d+\.\d+)', array('ApiGeneralCosts', 'setCostsJSON'));

// register amazon api calls
getRoute() -> get('/config/amazon', array('ApiAmazon', 'getConfigJSON'));
getRoute() -> get('/config/amazon/(\w+)', array('ApiAmazon', 'getConfigJSON'));
getRoute() -> put('/config/amazon/(\w+)/(\w+|\d+|\d+\.\d+)', array('ApiAmazon', 'setConfigJSON'));

getRoute() -> run();
?>
