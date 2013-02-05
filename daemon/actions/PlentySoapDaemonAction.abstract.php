<?php

/**
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
abstract class PlentySoapDaemonAction 
{
	/**
	 * should I log everything or not?
	 *
	 * @var boolean
	 */
	const VERBOSE = true;
	
	/**
	 * an interval is defined in minutes - smallest value is 5
	 * 
	 * @var integer
	 */
	private $timeInterval = 15;
	
	/**
	 * 
	 * @var unknown
	 */
	private $lastRunTimestamp = 0;
	
	/**
	 * 
	 * @var string
	 */
	private $identifier4Logger = '';
	
	public function __construct($identifier4Logger)
	{
		$this->identifier4Logger = $identifier4Logger;
	}
	
	public abstract function execute();
	
	/**
	 *
	 * @return Logger
	 */
	protected function getLogger()
	{
		return Logger::instance($this->identifier4Logger);
	}
	
	/**
	 * call getLogger()->debug($message) if VERBOSE===true
	 *
	 * @param string $message
	 */
	protected function debug($message)
	{
		if(self::VERBOSE===true)
		{
			$this->getLogger()->debug($message);
		}
	}
	
	/**
	 * @return boolean
	 */
	public function isTimeForAnExecutionNow()
	{
		if($this->getTimeInterval()<5)
		{
			$this->setTimeInterval(5);
		}

		if($this->getLastRunTimestamp()==0
				|| (time()-($this->getTimeInterval()*60)) >= $this->getLastRunTimestamp()
		)
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * @return the $timeInterval
	 */
	public function getTimeInterval() 
	{
		return (int)$this->timeInterval;
	}

	/**
	 * @return the $lastRunTimestamp
	 */
	public function getLastRunTimestamp() 
	{
		return $this->lastRunTimestamp;
	}

	/**
	 * @param number $timeInterval
	 */
	public function setTimeInterval($timeInterval) 
	{
		$timeInterval = (int)$timeInterval;
		
		if(!$timeInterval || $timeInterval<5)
		{
			$timeInterval = 5;
		}
		
		$this->timeInterval = $timeInterval;
	}

	/**
	 * @param unknown $lastRunTimestamp
	 */
	public function setLastRunTimestamp($lastRunTimestamp) 
	{
		$this->lastRunTimestamp = $lastRunTimestamp;
	}

}
?>