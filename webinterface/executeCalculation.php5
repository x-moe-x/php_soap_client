<?php

require_once realpath(dirname(__FILE__).'/../').'/config/basic.inc.php';
require_once ROOT.'lib/soap/experiment_loader/NetXpressSoapExperimentLoader.class.php';


NetXpressSoapExperimentLoader::getInstance()->run(array('','CalculateHistogram','CalculateHistogram'));

?>