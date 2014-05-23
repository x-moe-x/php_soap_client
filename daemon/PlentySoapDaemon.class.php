<?php

require_once ROOT.'lib/log/Logger.class.php';
require_once ROOT.'daemon/actions/PlentySoapDaemonActionCollector.class.php';

/**
 * This is a php tool, which can be used as a daemon.
 * It writes this pid file: /var/run/plentySoapDaemon.pid
 * Start this tool via cli/PlentySoap.daemon.php as root or an user, which can write pid files.
 *
 * If you work under linux, it would be a good idea to write an init script:
 * http://www.cyberciti.biz/tips/linux-write-sys-v-init-script-to-start-stop-service.html
 *
 * Also use monit, so monit can restart this tool, if it dies:
 * http://mmonit.com/monit/
 *
 * This daemon executes all action classes in daemon/actions/*
 * Read the example action class PlentySoapDaemonAction_GetOrderStatusList it might be a good best practice.
 *
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class PlentySoapDaemon
{
	const PID_FILE_DEST = '/tmp/plentySoapDaemon.pid';

	/**
	 * Sleep some seconds, if nothing needs to be done
	 *
	 * @var int
	 */
	const SLEEP = 3;

	/**
	 * should I log everything or not?
	 *
	 * @var boolean
	 */
	const VERBOSE = true;

	/**
	 *
	 * @var boolean
	 */
	private $stopDaemon = false;

	/**
	 *
	 * @var PlentySoapDaemon
	 */
	private static $instance = null;

	private function __construct()
	{

	}

	/**
	 * singleton pattern
	 *
	 * @return PlentySoapDaemon
	 */
	public static function getInstance()
	{
		if( !isset(self::$instance) || !(self::$instance instanceof PlentySoapDaemon))
		{
			self::$instance = new PlentySoapDaemon();
		}

		return self::$instance;
	}

	/**
	 * Control function
	 *
	 * @param array $params
	 */
	public function run()
	{
		/*
		 * the daemon should only be running once!
		 */
		if( $this->checkPidFile() )
		{
			$this->getLogger()->crit(__FUNCTION__ .' ERROR PlentySoapDaemon already running, shutting down!');
			exit;
		}

		$this->writePidFile();

		PlentySoapDaemonActionCollector::getInstance(self::VERBOSE)->loadActionObjectList();

		while(true)
		{
			/*
			 * can I execute an action?
			 */
			$soapActionList = PlentySoapDaemonActionCollector::getInstance(self::VERBOSE)->getNextActions();

			if(isset($soapActionList) && is_array($soapActionList))
			{
				/*
				 * execute actions
				 */
				foreach($soapActionList as $actionClassName)
				{
					$actionObject = PlentySoapDaemonActionCollector::getInstance(self::VERBOSE)->getActionObject($actionClassName);
					if(is_object($actionObject))
					{
						$this->debug(__FUNCTION__.' START '.$actionClassName.'->execute()');

						$actionObject->execute();

						$this->debug(__FUNCTION__.' END '.$actionClassName.'->execute()');

						$actionObject->setLastRunTimestamp(time());
					}
				}
			}

			if($this->stopDaemon===true)
			{
				$this->debug('I got a signal to shut down.');
				$this->rmPidFile();

				exit;
			}

			/*
			 * sleep and run again...
			 */
			sleep(self::SLEEP);

		}
	}

	/**
	 * Init shut down
	 */
	public function stopDaemon()
	{
		$this->debug(__FUNCTION__.' stopping daemon');

		$this->stopDaemon = true;
	}


	/**
	 * writes the PID file
	 */
	private function writePidFile()
	{
		if (function_exists('posix_getpid'))
		{
			$pid = posix_getpid();
			if(isset($pid) && $pid>0)
			{
				if (is_writable(dirname(self::PID_FILE_DEST)))
				{
					file_put_contents(self::PID_FILE_DEST, $pid);
				}
				else
				{
					$this->getLogger()->debug(__FUNCTION__.' I can not write pid file: Permission denied');
				}
			}
			else
			{
				$this->getLogger()->crit(__FUNCTION__ .' ERROR I can not write pid file, because I can not get my pid. Shutting down!');
				exit;
			}
			unset( $pid );
		}
	}

	/**
	 * returns true in case it finds a valid pid file (with the process it belongs to still running), else false
	 *
	 * @return true if a pid file was found, else false
	 */
	private function checkPidFile()
	{
		$file = self::PID_FILE_DEST;

		if(is_file($file))
		{
			$pid = file_get_contents($file);
			if($pid === false)
			{
				$this->globalLogger->crit(__FUNCTION__.' ERROR I can not read the pid file ' . $file);

				/*
				 * if we can't read the (existing) pid file, it's probably not valid
				 */
				unset($pid);
				unset($file);
				return false;
			}
			else
			{
				if( $this->check( (int)$pid) )
				{
					unset($pid);
					unset($file);
					return true;
				}
				else
				{
					unset($pid);
					unset($file);
					return false;
				}
			}
		}
		else
		{
			unset($file);
			return false;
		}
	}

	/**
	 * Check if pid exists and runs PlentySoap.daemon.php
	 *
	 * @param int $pid
	 */
	private function check($pid)
	{
		$pid = (int)$pid;
		if($pid>0)
		{
			$output = shell_exec('ps -p '.$pid);
			preg_match('/('.$pid.').*?PlentySoap\.daemon\.php/i', $output, $result);

			if(isset($result[1]) && $result[1]==$pid)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 *
	 * @return Logger
	 */
	private function getLogger()
	{
		return Logger::instance(__CLASS__);
	}

	/**
	 * call getLogger()->debug($message) if VERBOSE===true
	 *
	 * @param string $message
	 */
	private function debug($message)
	{
		if(self::VERBOSE===true)
		{
			$this->getLogger()->debug($message);
		}
	}
}
?>