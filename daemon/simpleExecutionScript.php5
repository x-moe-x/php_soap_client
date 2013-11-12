<?php

require_once realpath(dirname(__FILE__).'/../').'/config/basic.inc.php';
require_once ROOT.'lib/soap/experiment_loader/NetXpressSoapExperimentLoader.class.php';


// update order db
NetXpressSoapExperimentLoader::getInstance()->run(array('','SearchOrders'));

// update article db
NetXpressSoapExperimentLoader::getInstance()->run(array('','GetItemsBase'));
NetXpressSoapExperimentLoader::getInstance()->run(array('','GetItemsWarehouseSettings'));

// calculate daily need
NetXpressSoapExperimentLoader::getInstance()->run(array('','CalculateHistogram','CalculateHistogram'));

?>