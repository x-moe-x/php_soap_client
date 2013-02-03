<?php
require_once 'LoggerBase.class.php';

/**
 * Global logger class
 * 
 * @example 
 * Logger::instance('soap')->debug("debug message");
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class Logger extends LoggerBase
{
	/**
	 * 
	 * @var Logger
	 */
	private static $Logger = array();
		
	/**
	 * 
	 * @param string $identifier	name of the class or some identifier to factory a Logger.
	 */
	protected function __construct($identifier)
	{
		parent::__construct($identifier);
	}
	
	/**
	 * is now singleton.
	 *
	 * @param string $identifier	name of the class or some identifier to factory a Logger.
	 * 
	 * @return Logger
	 */
	public static function instance($identifier)
	{
		$reload = false;
		
		/*
		 * should I build a new object
		 */
		if( !isset(self::$Logger[$identifier]) || !(self::$Logger[$identifier] instanceof Logger))
		{
			self::$Logger[$identifier] = new Logger($identifier);
		}
		
		return self::$Logger[$identifier];
	}

	/**
	 * log an info message
	 * 
	 * @param string $message
	 */
	public function info($message)
	{
		$this->log($message, PY_INFO);
	}
	
	/**
	 * Log an debug message.
	 * 
	 * @param String $message
	 * @return void
	 */
	public function debug($message)
	{
		$this->log($message, PY_DEBUG);
	}
	
	/**
	 * Log an error message
	 * 
	 * @param String $message
	 * @return void
	 */
	public function err($message)
	{
		$this->log($message, PY_ERR);
	}
	
	/**
	 * Log a critical message
	 * 
	 * @param $message
	 * @return void
	 */
	public function crit($message)
	{
		$this->log($message, PY_CRIT);
	}
	
	
	/**
	 * Log an Exception
	 * 
	 * @param Exception $exception
	 * @return void
	 */
	public function logException(Exception $exception)
	{
		$this->crit('Exception '
					.	($exception->getCode()!=0
							? 'Code: ' . $exception->getCode() 
							: '') 
					.	' ' . $exception->getMessage() . chr(10) 
					.	$exception->getTraceAsString() . chr(10)
		);
	}
}
?>