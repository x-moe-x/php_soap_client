<?php

require_once 'DBAbstractConnector.abstract.php';


/**
 * The base class of all database objects.
 * Actually only {@link DBQuery} exists as child class.
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class DBBase
{
	/**
	 * An instance of a DB connector class implementing the DBConnectorInterface.
	 * 
	 * @var DBAbstractConnector
	 */
	private $oDBConnector = null;
	
	/**
	 * Constructor
	 * 
	 * @param DBAbstractConnector	$oConnector		The connector to be used.
	 * 
	 * @return void
	 */
	protected function __construct(DBAbstractConnector $oConnector)
	{
		$this->oDBConnector = $oConnector;
	}
	
	/**
	 * Destructor
	 */
	public function __destruct()
	{
		
	}
	
	/**
	 * Get the DB connector.
	 * 
	 * @return DBAbstractConnector
	 */
	public function getConnector()
	{
	    return $this->oDBConnector;
	}
	
	/**
	 * Returns the error code of the last query.
	 * 
	 * @return integer
	 */
	public function getErrorCode()
	{
		return $this->getMySQLi()->errno;
	}
	
	/**
	 * Returns the error message of the last query.
	 * 
	 * @return string
	 */
	public function getErrorMessage()
	{
		return $this->getMySQLi()->error;
	}
	
	/**
	 * Get the DB handle (mysqli instance.
	 * 
	 * @return mysqli
	 */
	protected function getMySQLi()
	{
		return $this->oDBConnector->getMySQLi();
	}
		
	/**
	 * Return TRUE if the query starts with the given start string, otherwise FALSE.
	 * 
	 * @param string $query
	 * @param string $string
	 * 
	 * @return boolean
	 */
	protected function startsWith($query,$string)
	{
		/*
		 * A (SELECT ...) UNION (SELECT ...) query will be now recognized
		 */
		return 	substr(strtoupper(trim($query)),0,strlen($string)) == strtoupper(trim($string)) ||
				substr(strtoupper(trim($query)),0,strlen($string)+1) == strtoupper(trim('('.$string));
	}
	
	/**
	 * Escapes the given string using mysqli::real_escape_string.
	 * 
	 * @param string $string
	 * 
	 * @return string
	 */
	public function escapeString($string)
	{
		if(!$this->getMySQLi())
		{
			throw new Exception('There is no opened connection to the database!');
		}
		return $this->getMySQLi()->real_escape_string($string);
	}
}

?>