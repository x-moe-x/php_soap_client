<?php

require_once realpath(dirname(__FILE__) . '/../') . '/config/basic.inc.php';
require_once ROOT . 'lib/soap/experiment_loader/NetXpressSoapExperimentLoader.class.php';

ob_start();
$startTime = microtime(true);
$result = array();
NetXpressSoapExperimentLoader::getInstance() -> run(array('', 'CalculateHistogram', 'CalculateHistogram'));
ob_end_clean();
$timeDiff = microtime(true) - $startTime; 

$result['executionTime'] = number_format($timeDiff, 2);
$result['executionTimeUnit'] = 'seconds';

echo json_encode($result);
?>