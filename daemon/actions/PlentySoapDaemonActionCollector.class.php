<?php

require_once 'PlentySoapDaemonAction.abstract.php';

/**
 * Collect soap daemon actions and find next actions for action stack
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class PlentySoapDaemonActionCollector
{
	/**
	 * dir of all action classes
	 * 
	 * @var string
	 */
	private $actionDir = '';
	
	/**
	 * list of action objects, which need to be executed
	 *
	 * @var array key=>value: classname=>object
	 */
	private $actionStack = array();
	
	/**
	 *
	 * @var PlentySoapDaemonActionCollector
	 */
	private static $instance = null;
	
	/**
	 * 
	 * @var boolean
	 */
	private $verboseFlagFromSoapDaemon = true;
	
	/**
	 * 
	 * @param boolean $verboseFlagFromSoapDaemon
	 */
	private function __construct($verboseFlagFromSoapDaemon)
	{
		/*
		 * current dir
		 */
		$this->actionDir = dirname(__FILE__) . '/';
		
		$this->verboseFlagFromSoapDaemon = $verboseFlagFromSoapDaemon;
	}
	
	/**
	 * singleton pattern
	 * 
	 * @param boolean $verboseFlagFromSoapDaemon
	 * 
	 * @return PlentySoapDaemonActionCollector
	 */
	public static function getInstance($verboseFlagFromSoapDaemon)
	{
		if( !isset(self::$instance) || !(self::$instance instanceof PlentySoapDaemonActionCollector))
		{
			self::$instance = new PlentySoapDaemonActionCollector($verboseFlagFromSoapDaemon);
		}
	
		return self::$instance;
	}
	
	/**
	 * put all actions to local actionStack
	 * 
	 */
	public function loadActionObjectList()
	{
		$this->actionStack = array();
		
		foreach (glob($this->actionDir.'PlentySoapDaemonAction_*.php') as $classFile) 
		{
			require_once $classFile;
			
			$clazz = basename($classFile,'.class.php');
			
			$o = new $clazz();
			
			if(isset($o) && $o instanceof PlentySoapDaemonAction)
			{
				$this->debug('add daemon to action stack: '.$clazz);
				
				$this->actionStack[$clazz] = $o;
			}
		}
	}
	
	/**
	 * get list of soap action class names which should be executed
	 *  
	 * @return array
	 */
	public function getNextActions()
	{
		$nextActionList = array();
		
		if(isset($this->actionStack) && is_array($this->actionStack))
		{
			foreach(array_keys($this->actionStack) as $actionClassName)
			{
				$o = $this->getActionObject($actionClassName);
				if(is_object($o)
						&& $o->isTimeForAnExecutionNow() )
				{
					$nextActionList[] = $actionClassName;
				}
			}
		}
		
		return $nextActionList;
	}
	
	/**
	 * set last run timestamp of given action class to current timestamp
	 * 
	 * @param unknown $actionClassName
	 */
	public function setLastRunTimestamp($actionClassName)
	{
		$o = $this->getActionObject($actionClassName);
		if(is_object($o))
		{
			$this->debug('set last run time for action: '.$actionClassName);
			
			$o->setLastRunTimestamp(time());
		}
	}
	
	/**
	 * returns action class object of given class name, if object is in stack
	 * 
	 * @param string $actionClassName
	 * @return PlentySoapDaemonAction|NULL
	 */
	public function getActionObject($actionClassName)
	{
		if(isset($this->actionStack[$actionClassName]) && $this->actionStack[$actionClassName] instanceof PlentySoapDaemonAction)
		{
			return $this->actionStack[$actionClassName];
		}
		else
		{
			$this->getLogger()->crit(__FUNCTION__.' class not found in action stack: '.$actionClassName);
		}
		
		return null;
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
	 * call getLogger()->debug($message) if $this->verboseFlagFromSoapDaemon===true
	 *
	 * @param string $message
	 */
	private function debug($message)
	{
		if($this->verboseFlagFromSoapDaemon===true)
		{
			$this->getLogger()->debug($message);
		}
	}
}

?>