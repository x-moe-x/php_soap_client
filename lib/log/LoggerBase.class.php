<?php

require_once ROOT.'config/logger.inc.php';

/**
 * Global logger base class
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class LoggerBase
{

	/**
	 * Data from config file
	 * 
	 * @var array
	 */
	private $logConfig = array();
	
	/**
	 * 
	 * @var string
	 */
	private $identifierString = '';
	
	/**
	 * 
	 * @var int
	 */
	private $logLevel = 0;
	
	/**
	 * 
	 * @var string
	 */
	private $logFileDest = '';
	
	/**
	 * map log level to a log level name
	 * 
	 * @var array
	 */
	private $mapLevel2Name = array(
								PY_CRIT 	=> 'CRITICAL',
								PY_ERR		=> 'ERROR',
								PY_DEBUG 	=> 'DEBUG',
								PY_INFO 	=> 'INFO',
							);
	
	/**
	 * 
	 * @param string $identifier	name of the class or some identifier to factory a Logger.
	 */
	protected function __construct($identifier)
	{
		/*
		 * build indentifier for log output
		*/
		$this->identifierString = $identifier;
		
		if(defined('LOG_LEVEL'))
		{
			$this->logLevel = LOG_LEVEL;
		}

		if(defined('LOG_DIR') && strlen(LOG_DIR))
		{
			$this->logFileDest = LOG_DIR . '/' . LOG_FILENAME;
			
			if(!is_dir(LOG_DIR))
			{
				mkdir(LOG_DIR, 0755, true);
			}			
		}
		else 
		{
			echo __CLASS__.' LOG_DIR not set' . chr(10);
			exit;
		}
	}
	
	/**
	 * Log a message
	 * 
	 * @param string $message
	 * @param int $logLevel
	 * @param boolean $logLevelName
	 * 
	 * @return void
	 */
	protected function log($message, $logLevel, $logLevelName=true)
	{
		if(!is_string($message))
        {
        	$message = var_export($message, true);
        }
        
		if(	$logLevel==PY_CRIT || ($this->logLevel>0 && $logLevel<=$this->logLevel && strlen($message)) )
		{
			$output =		date('r') . ' '
						.	$this->identifierString
						.	($logLevelName 
								?	chr(9).'[' . $this->mapLevel2Name[$logLevel]. '] '
								:	'')
						.	$message
						.	chr(10);
			
			if(defined('LOG_OUTPUT') && LOG_OUTPUT===true)
			{
				echo $output;
			}
			
			file_put_contents($this->logFileDest, $output, FILE_APPEND);
		}
	}
	
	/**
	 * Reset log file destination
	 * 
	 * @param string $dest
	 */
	protected function setLogFileDest($dest)
	{
		$this->logFileDest = $dest;
	}
	
	/**
	 * 
	 * @return string
	 */
    public function createJavaStracktrace()
	{
		$stack = debug_backtrace();
		
		for($id=3; $id < sizeof($stack) ; ++$id) 
		{
			$stack2[$id] = @get_class($stack[$id]["object"]) . ".". $stack[$id]["function"] .":". $stack[$id]["line"];
		}
		
		return $stack2;
	}
}
?>