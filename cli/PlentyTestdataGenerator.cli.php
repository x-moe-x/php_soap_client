<?php
/*
 * Simple testdata generator
 * 
 * Usage:
 * shell> php cli/PlentyTestdataGenerator.cli.php type:item lang:de qantity:300
 */

require_once realpath(dirname(__FILE__).'/../').'/config/basic.inc.php';
require_once ROOT.'testdata/PlentyTestdataGenerator.class.php';

PlentyTestdataGenerator::getInstance()->execute($argv);

?>