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
	
	/**
	 * 
	 * @var boolean
	 */
	private $deactiveThisAction = false;
	
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

		if($this->deactiveThisAction===false
				&& ($this->getLastRunTimestamp()==0
						|| (time()-($this->getTimeInterval()*60)) >= $this->getLastRunTimestamp()
					)
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
	 * define the intervall in minutes. minimum value: 5
	 * once a day: 1440
	 * once a hour: 60
	 * every quarter of an hour: 15
	 * @param number $timeInterval = minutes
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

	/**
	 * require and instance soap call adapter class
	 *  
	 * @param string $soapCallName
	 * @return PlentySoapCall|Null
	 */
	protected function getSoapCallAdapterClass($soapCallName)
	{
		$file = ROOT.'daemon/adapter/'.$soapCallName.'/Adapter_'.$soapCallName.'.class.php';
		if(is_file($file))
		{
			$clazz = 'Adapter_'.$soapCallName;
			
			require_once $file;
			$o = new $clazz();
			
			if(isset($o) && $o instanceof PlentySoapCall)
			{
				return $o;
			}
		}
		
		return null;
	}
	
	/**
	 * set true, if this action should currently not executed by PlentySoapDaemon
	 * 
	 * @param boolean $deactiveThisAction
	 */
	protected function setDeactivateThisAction($deactiveThisAction)
	{
		if($deactiveThisAction)
		{
			$this->deactiveThisAction = true;
		}
		else
		{
			$this->deactiveThisAction = false;
		}
	}
	
	/**
	 * 
	 * @param string $className
	 * @return string
	 */
	protected function getClassPostfix($className)
	{
		return str_replace('PlentySoapDaemonAction_', '', $className);
	}
}
?>