<?php

/*
 * usage:
 * 
 * shell> php cli/NetXpressSoapExperimentLoader.cli.php [Experiment]
 * shell> php cli/NetXpressSoapExperimentLoader.cli.php SearchOrdersAdvanced
 * 
 * If you want to see log output, than run this before:
 * shell> tail -f log/soap.log &
 * 
 */

require_once realpath(dirname(__FILE__).'/../').'/config/basic.inc.php';
require_once ROOT.'includes/ForcePHPVersion.php';
require_once ROOT.'lib/soap/experiment_loader/NetXpressSoapExperimentLoader.class.php';

NetXpressSoapExperimentLoader::getInstance()->run($argv);

?>