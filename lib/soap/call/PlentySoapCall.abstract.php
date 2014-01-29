<?php
require_once ROOT.'lib/soap/client/PlentySoapClient.class.php';

/**
 * 
 * This class should be a mother class of every soap call class.
 * Look at the example folder for some live soap call examples.
 * 
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
abstract class PlentySoapCall
{
	/**
	 * true: I will run soap call again, if I get an exception
	 * 
	 * @var boolean
	 */
	private $retryOnException		=	true;

	/**
	 * Number of retries
	 * 
	 * @var integer
	 */
	private $retryOnExceptionCount	=	1;
	
	/**
	 * 
	 * @var integer
	 */
	private $retryCount				=	0;
	
	/**
	 * 
	 * @var boolean
	 */
	private $verbose				= true;
	
	/**
	 * Identifier string for Logger
	 * 
	 * @var string
	 */
	private	$identifier4Logger;
	
	
	public function __construct($identifier4Logger)
	{
		if(is_string($identifier4Logger) && strlen($identifier4Logger))
		{
			$this->identifier4Logger = $identifier4Logger;
		}
		else
		{
			$this->identifier4Logger = __CLASS__;
		}
	}
	
	/**
	 * 
	 * @return PlentySoap
	 */
	public function getPlentySoap()
	{
		return PlentySoapClient::getInstance()->getPlentySoap();
	}
	
	public function onExceptionAction(Exception $e)
	{
		$this->getLogger()->crit("Exception : ".$e->getMessage());
		
		// TODO check on http 500 
		
		/*
		 * check if token is valid
		 */
		if( $e->getMessage() === "Unauthorized Request - Invalid Token" )
		{
			/*
			 * token is not valid
			 * 
			 * so renew token
			 */
			PlentySoapClient::getInstance()->updateToken();
		}
		
		if( $this->getRetryOnException() == true )
		{
			if( $this->retryCount < $this->getRetryOnExceptionCount() )
			{
				$this->retryCount++;
				
				sleep(2);
				
				$this->execute();
			}
		}
	}
	
	public abstract function execute();
	
	/**
	 *
	 */
	public function resetRetryCounter()
	{
		$this->retryCount = 0;
	}
	
	/**
	 *
	 * @return Logger
	 */
	protected function getLogger()
	{
		return Logger::instance($this->identifier4Logger);
	}
	
	/**
	 * call getLogger()->debug($message) if $this->verbose
	 *
	 * @param string $message
	 */
	protected function debug($message)
	{
		if($this->verbose===true)
		{
			$this->getLogger()->debug($message);
		}
	}
	
	/**
	 * 
	 * @param boolean $verbose
	 */
	public function setVerbose($verbose)
	{
		if($verbose)
		{
			$this->verbose = true;
		}
		else
		{
			$this->verbose = false;
		}
	}
	
	/**
	 *
	 * @param boolean $retryOnException
	 * 
	 * @return SoapCallBase
	 */
	public function setRetryOnException($retryOnException)
	{
		$this->retryOnException = $retryOnException;
		
		return $this;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function getRetryOnException()
	{
		return $this->retryOnException;
	}
	
	/**
	 *
	 * @return number
	 */
	public function getRetryOnExceptionCount()
	{
		return $this->retryOnExceptionCount;
	}
	
	/**
	 *
	 * @param integer $retryOnExceptionCount
	 * 
	 * @return SoapCallBase
	 */
	public function setRetryOnExceptionCount($retryOnExceptionCount)
	{
		if( (int)$retryOnExceptionCount > 3 )
		{
			$this->retryOnExceptionCount = 3;
		}
		elseif( (int)$retryOnExceptionCount < 0 )
		{
			$this->retryOnExceptionCount = 0;
		}
		else
		{
			$this->retryOnExceptionCount = $retryOnExceptionCount;
		}
	
		return $this;
	}
	
}

?>