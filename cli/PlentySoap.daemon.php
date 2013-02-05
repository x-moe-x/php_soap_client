<?php

/*
 * Use this script to start daemon/PlentySoapDaemon.class.php 
 * Look at the class file to get more informations.
 */

require_once realpath(dirname(__FILE__).'/../').'/config/basic.inc.php';
require_once ROOT.'daemon/PlentySoapDaemon.class.php';

PlentySoapDaemon::getInstance()->run();

?>