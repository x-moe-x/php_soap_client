<?php

/*
 * usage:
 * 
 * shell> php cli/PlentymarketsSOAPExampleLoader.cli.php [ExampleName]
 * shell> php cli/PlentymarketsSOAPExampleLoader.cli.php GetServerTime
 * 
 * If you want to see log output, than run this before:
 * shell> tail -f log/soap.log &
 * 
 */

require_once realpath(dirname(__FILE__).'/../').'/config/basic.inc.php';
require_once ROOT.'lib/soap/example_loader/PlentymarketsSOAPExampleLoader.class.php';

PlentymarketsSOAPExampleLoader::getInstance()->run($argv);

?>