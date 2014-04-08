<?php

/*
 * Use this script to start daemon/PlentySoapDaemon.class.php 
 * Look at the class file to get more informations.
 */

require_once realpath(dirname(__FILE__).'/../').'/config/basic.inc.php';
require_once ROOT.'includes/ForcePHPVersion.php';
require_once ROOT.'daemon/PlentySoapDaemon.class.php';


/**
 * This function is important in order to finish the daemon process in a correct way
 */
if (function_exists('pcntl_signal'))
{
	declare(ticks = 1);

	function plentySoapDaemonSigHandler($signo)
	{
		switch ($signo)
		{
			case SIGTERM:
			case SIGINT:
				PlentySoapDaemon::getInstance()->stopDaemon();
				break;
		}
	}
	pcntl_signal(SIGTERM, 'plentySoapDaemonSigHandler');
	pcntl_signal(SIGINT, 'plentySoapDaemonSigHandler');
}

/**
 * Start daemon
 */
PlentySoapDaemon::getInstance()->run();

?>