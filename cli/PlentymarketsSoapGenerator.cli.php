<?php

/*
 * run this file by shell for generating soap objects
 * 
 * but at first edit: config/soap.inc.php 
 */

require_once realpath(dirname(__FILE__).'/../').'/config/basic.inc.php';
require_once ROOT.'lib/soap/generator/PlentymarketsSoapGenerator.class.php';

PlentymarketsSoapGenerator::getInstance()->run();

?>