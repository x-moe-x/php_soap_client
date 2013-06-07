<?php

require_once ROOT.'lib/log/Logger.class.php';

/**
 * abstract db connector used by PlentyDBBase
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
abstract class DBAbstractConnector
{
	/**
	 * The name of the database.
	 * 
	 * @var string
	 */
	private $SQLDataBase;
	
	/**
	 * The name of the host.
	 * 
	 * @var string
	 */
	private $SQLDataSource;
	
	/**
	 * The name of the database user.
	 * 
	 * @var string
	 */
	private $SQLUsername;
	
	/**
	 * The password of the database user.
	 * 
	 * @var string
	 */
	private $SQLPasswd;
	
	/**
	 * The socket to be used for the connection to the database.
	 * 
	 * @var string
	 */
	private $SQLSocket;
	
	/**
	 * The port to be used for the connection to the database
	 *
	 * @var int
	 */
	private $SQLPort;

	/**
	 * A mysqli instance.
	 * 
	 * @var mysqli
	 */
	private $oMySQLi = null;
	
	/**
	 * Constructor
	 * 
	 * @return void
	 */
	protected function __construct()
	{

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
	 * Destructor.
	 * 
	 * @return void
	 */
	public function __destruct()
	{

	}
	
	/**
	 * Return TRUE if there is a valid and connected mysqli instance, otherwise FALSE.
	 * 
	 *  @return boolean
	 *  
	 *  @deprecated Do not use this method in PHP Versions < 5.3!
	 */
	final public function isConnectionOpen()
	{
		return $this->getMySQLi() instanceof mysqli && isset($this->getMySQLi()->thread_id) ? true : false;
	}
	
	/**
	 * Get the name of the database in use.
	 * 
	 * @return string
	 */
	public function getDataBase()
	{
		return $this->SQLDataBase;
	}

	/**
	 * Get the name of the source (host) of the database in use.
	 * 
	 * @return string
	 */
	public function getDataSource()
	{
		return $this->SQLDataSource;
	}

	/**
	 * Get the password for the database connection.
	 * 
	 * @return string
	 */
	public function getPassword()
	{
		return $this->SQLPasswd;
	}

	/**
	 * Get the name of the user for the database.
	 * 
	 * @return string
	 */
	public function getUserName()
	{
		return $this->SQLUsername;
	}
	
	/**
	 * Get the socket for the database.
	 * 
	 * @return string
	 */
	public function getSocket()
	{
		return $this->SQLSocket;
	}
	
	/**
	 * Get the port for the database.
	 *
	 * @return string
	 */
	public function getPort()
	{
		return $this->SQLPort;
	}

	/**
	 * Get the database handle (instance of mysqli).
	 * 
	 * @return mysqli
	 */
	public function getMySQLi()
	{
		return $this->oMySQLi;
	}

	/**
	 * Set the name of the database in use.
	 * 
	 * @param string $sDataBase
	 * 
	 * @return DBAbstractConnector
	 */
	protected function setDataBase($sDataBase)
	{
		$this->SQLDataBase = $sDataBase;
		return $this;
	}

	/**
	 * Set the name of the source (host) of the database in use.
	 * 
	 * @param string $sDataSource
	 * 
	 * @return DBAbstractConnector
	 */
	protected function setDataSource($sDataSource)
	{
		$this->SQLDataSource = $sDataSource;
		return $this;
	}

	/**
	 * Set the password for the database connection.
	 * 
	 * @param string $sPassword
	 * 
	 * @return DBAbstractConnector
	 */
	protected function setPassword($sPassword)
	{
		$this->SQLPasswd = $sPassword;
		return $this;
	}

	/**
	 * Set the name of the user for the database.
	 * 
	 * @param string $sUserName
	 * 
	 * @return DBAbstractConnector
	 */
	protected function setUserName($sUserName)
	{
		$this->SQLUsername = $sUserName;
		return $this;
	}
	
	/**
	 * Set the socket for the database.
	 * 
	 * @param string $sSocket
	 * 
	 * @return DBAbstractConnector
	 */
	protected function setSocket($sSocket)
	{
		$this->SQLSocket = $sSocket;
		return $this;
	}
	/**
	 * Set the port for the database.
	 *
	 * @param int $sPort
	 *
	 * @return DBAbstractConnector
	 */
	protected function setPort($sPort)
	{
		$this->SQLPort = $sPort;
		return $this;
	}
	
	/**
	 * Set the database handle (instance of mysqli).
	 * 
	 * @param mysqli $oMySQLi
	 * 
	 * @return DBAbstractConnector
	 */
	protected function setMySQLi(mysqli $oMySQLi)
	{
		$this->oMySQLi = $oMySQLi;
		return $this;
	}
	
	/**
	 * If $bFirstTry is set to TRUE, the method retries to connect if no connection could be etablished to the database.
	 * 
	 * @param boolean $bFirstTry [optional, default=true]
	 * 
	 * @return DBConnector
	 * 
	 * @throws Exception
	 */
	protected function openConnection($bFirstTry=true)
	{
		//$this->getLogger()->debug(__FUNCTION__.'() [?] Opening connection ... '.($bFirstTry ? ' first' : 'second').' try.');
		
		if(!$this->getMySQLi())
		{
			//$this->getLogger()->debug(__FUNCTION__.'() [?] Opening connection for ("'.$this->getDataBase().'", "'.$this->getDataSource().'", "'.$this->getUserName().'"');
			
			try 
			{
				$oMySQLi = new mysqli($this->getDataSource(), $this->getUserName(), $this->getPassword(), $this->getDataBase(), $this->getPort(), $this->getSocket());
			} 
			catch (Exception $e) 
			{
				/*
				 * do nothing in this case
				 * 
				 * by global definition every warning would be a fatal. without try-catch we could not log it, like we wanna log it. 
				 */
			}
			
			if(mysqli_connect_errno())
			{		
				/*
				 * system debug
				 */
				$this->getLogger()->crit(__FUNCTION__.' connection error : ('.mysqli_connect_errno().') '.mysqli_connect_error());
				
				if($bFirstTry)
				{
					
					/*
					 * do a retry only if it is not this error: (1045) Access denied for user...
					 */
					if(mysqli_connect_errno()!=1045)
					{
						/*
						 * system debug
						 */
						$this->getLogger()->debug(__FUNCTION__.' retrying in 3 seconds.....');
						
						sleep(3);
						
						return $this->openConnection(false);
					}
				}
				throw new Exception(mysqli_connect_error(),mysqli_connect_errno());
			}
			else
			{
				/*
				 * needed for utf8 !!
				 */
				$oMySQLi->query("SET NAMES 'utf8'");
				
				
				$this->setMySQLi($oMySQLi);
			}
			
		}
		
		return $this;
	}
	
	/**
	 * Close the connection to the database.
	 * 
	 * @return void
	 */
	protected  function closeConnection()
	{
		if($this instanceof DBAbstractConnector && $this->getMySQLi() instanceof mysqli)
		{
			$this->getLogger()->debug(__FUNCTION__.'() ['.$this->getMySQLi()->thread_id.'] Connection going to be closed!');
			$this->getMySQLi()->close();
			$this->oMySQLi = null;
		}
	}
	
	
}


?>