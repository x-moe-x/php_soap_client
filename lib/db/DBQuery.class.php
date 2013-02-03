<?php
/*
 * load config
 */
require_once realpath(dirname(__FILE__).'/../../').'/config/basic.inc.php';

require_once 'DBBase.class.php';
require_once 'DBUtils.class.php';
require_once 'DBConnector.class.php';
require_once 'DBQueryResult.class.php';

/**
 * Use this for simple queries
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class DBQuery extends DBBase
{
	/**
	 * A global instance of a DBQuery.
	 * 
	 * @var DBQuery
	 */
	private static $oDBQuery = null;
	
	/**
	 * Constructor
	 * 
	 * @param DBConnector $oConnector	The connector to use.
	 *  
	 * @return void
	 */
	protected function __construct(DBAbstractConnector $oConnector)
	{
		parent::__construct($oConnector);
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
	 * Beginnt eine gesammelte Transaktion
	 * 
	 * @return void
	 */
	public function begin()
	{
		$this->execute('BEGIN', 'BEGIN');
	}
	
	/**
	 * Schliest eine gesammelte Transaktion ab
	 * 
	 * @return void
	 */
	public function commit()
	{
		$this->execute('COMMIT', 'COMMIT');
	}
	
	/**
	 * @return void
	 */
	public function rollback()
	{
		$this->execute('ROLLBACK', 'ROLLBACK');
	}
	
	/**
	 * 
	 * @param string $query		The SELECT query to be executed.
	 * @param string $resultType	The class name of the result type to be used (should extend DBQueryResult). [optional, default='DBQueryResult']
	 * 
	 * @return DBResult
	 * 
	 * @throws Exception
	 */
	public function select($query,$resultType='DBQueryResult')
	{
		return $this->executeResult($query, 'SELECT', $resultType);
	}
	
	/**
	 * 
	 * @param string $query 		The SELECT query to be executed.
	 * @param string $resultType	The class name of the result type to be used (should extend DBQueryResult). [optional, default='DBQueryResult']
	 * 
	 * @return array
	 * 
	 * @throws Exception
	 */
	public function selectAssoc($query,$resultType='DBQueryResult')
	{
		return $this->executeAssoc($query, 'SELECT', $resultType);
	}
	
	/**
	 * 
	 * @param string $query
	 * 
	 * @return DBResult
	 * 
	 * @throws Exception
	 */
	public function show($query)
	{
		return $this->executeResult($query,'SHOW');
	}
	
	/**
	 * 
	 * @param string $query The SHOW query to be executed.
	 * 
	 * @return array
	 * 
	 * @throws Exception
	 */
	public function showAssoc($query)
	{
		return $this->executeAssoc($query,'SHOW');
	}
	
	/**
	 * 
	 * @param string $query
	 * 
	 * @return DBResult
	 * 
	 * @throws Exception
	 */
	public function describe($query)
	{
		return $this->executeResult($query,'DESCRIBE');
	}
	
	/**
	 * 
	 * @param string $query The DESCRIBE query to be executed.
	 * 
	 * @return array
	 * 
	 * @throws Exception
	 */
	public function describeAssoc($query)
	{
		return $this->executeAssoc($query,'DESCRIBE');
	}
	
	/**
	 * 
	 * @param string $query
	 * 
	 * @return integer The count of affected rows.
	 * 
	 * @throws Exception
	 */
	public function insert($query)
	{
		return $this->execute($query,'INSERT');
	}
	
	/**
	 *
	 * @param string $query
	 *
	 * @return integer 
	 *
	 * @throws Exception
	 */
	public function lock($query)
	{
		return $this->execute($query,'LOCK');
	}
	
	/**
	 *
	 * @param string $query
	 *
	 * @return integer 
	 *
	 * @throws Exception
	 */
	public function unlock($query)
	{
		return $this->execute($query,'UNLOCK');
	}
	
	/**
	 * 
	 * @param string $query
	 * 
	 * @return integer The count of affected rows.
	 * 
	 * @throws Exception
	 */	
	public function alter($query)
	{
		return $this->execute($query,'ALTER');
	}
	
	/**
	 * 
	 * @param string $query
	 * 
	 * @return integer The count of affected rows.
	 * 
	 * @throws Exception
	 */
	public function update($query)
	{
		return $this->execute($query,'UPDATE');
	}
	
	/**
	 * 
	 * @param string $query
	 * 
	 * @return integer The count of affected rows.
	 * 
	 * @throws Exception
	 */
	public function rename($query)
	{
		return $this->execute($query,'RENAME');
	}
	
	/**
	 * 
	 * @param string $query
	 * 
	 * @return integer The count of affected rows.
	 * 
	 * @throws Exception
	 */
	public function replace($query)
	{
		return $this->execute($query,'REPLACE');
	}
	
	/**
	 * 
	 * @param string $query
	 * 
	 * @return integer The count of affected rows.
	 * 
	 * @throws Exception
	 */
	public function delete($query)
	{
		return $this->execute($query,'DELETE');
	}
	
	/**
	 * 
	 * @param string $query
	 * 
	 * @return integer The count of affected rows.
	 * 
	 * @throws Exception
	 */
	public function create($query)
	{
		return $this->execute($query, 'CREATE');
	}
	
	/**
	 * 
	 * @param string $query
	 * 
	 * @return integer
	 * 
	 * @throws Exception
	 */
	public function grant($query)
	{
		return $this->execute($query, 'GRANT');
	}
	
	/**
	 * 
	 * @param string $query
	 * 
	 * @return integer The count of affected rows.
	 * 
	 * @throws Exception
	 */
	public function truncate($query)
	{
		return $this->execute($query, 'TRUNCATE');
	}
	
	/**
	 * 
	 * @param string $query
	 * 
	 * @return integer The count of affected rows.
	 * 
	 * @throws Exception
	 */
	public function drop($query)
	{
		return $this->execute($query, 'DROP');
	}
	
	/**
	 * 
	 * @param string $query
	 * 
	 * @return integer The count of affected rows.
	 * 
	 * @throws Exception
	 */
	public function set($query)
	{
		return $this->execute($query, 'SET');
	}

	/**
	 * 
	 * @param string $query
	 * 
	 * @return integer The count of affected rows.
	 * 
	 * @throws Exception
	 */
	public function optimize($query)
	{
		return $this->execute($query, 'OPTIMIZE');
	}
	
	/**
	 * For DELETE, INSERT, UPDATE and REPLACE queries this method returns the affected rows.
	 * 
	 * @return integer
	 */
	public function getAffectedRows()
	{
		return $this->getMySQLi()->affected_rows;
	}
	
	/**
	 * Returns the insert ID for the last INSERT query.
	 * 
	 * @return integer
	 */
	public function getInsertId()
	{
		return $this->getMySQLi()->insert_id;
	}
	
	/**
	 * Returns a global instance of DBQuery.
	 * 
	 * @param string $sIncDir Base include path of current system. This param is used by GlobalSystemUpdate. 
	 * 
	 * @return DBQuery
	 */
	public static function getInstance()
	{
		if(!(self::$oDBQuery instanceof DBQuery))
		{
			self::$oDBQuery = new DBQuery(DBConnector::getInstance());
		}
		return self::$oDBQuery;
	}
	
	/**
	 * Executes a SELECT, SHOW or DESCRIBE query.
	 * 
	 * @param string $query		The query to be executed.
	 * @param string $type			The query type.
	 * @param string $resultType	The class name of the result type to be used (should extend DBQueryResult). [optional, default='DBQueryResult']
	 * 
	 * @return DBResult
	 * 
	 * @throws Exception
	 */
	private function executeResult($query,$type,$resultType='DBQueryResult')
	{
		if(!$this->startsWith($query,$type))
		{
			$error = 'The used query is not a '.$type.' query!';
			
			$this->getLogger()->crit($error);
			
			throw new Exception($error);
		}
		
		if(is_null($this->getMySQLi()) || !($this->getMySQLi() instanceof mysqli) )
		{
			$error = 'No MySQL connection';
				
			$this->getLogger()->crit($error);
				
			throw new Exception($error);			
		}
		
		$oMySQLiResult = $this->getMySQLi()->query($query);
		
		if(!$oMySQLiResult || $this->getMySQLi()->errno)
		{			
			$this->getLogger()->crit($this->getMySQLi()->errno.':'.$this->getMySQLi()->error);
			
			throw new Exception($this->getMySQLi()->error, $this->getMySQLi()->errno);
		}
		
		$iRowCount = -1;
		if(stripos($query,'SQL_CALC_FOUND_ROWS') !== false)
		{
			try
			{
				$aNumRows = $this->selectAssoc('SELECT FOUND_ROWS() AS numRows');
				$iRowCount = (int)$aNumRows['numRows'];
			}
			catch(Exception $oExc){}
		}
		
		if($resultType != 'DBQueryResult' && !is_subclass_of($resultType, 'DBQueryResult'))
		{
			$resultType = 'DBQueryResult';
		}
		
		return new $resultType($oMySQLiResult,$iRowCount,$this->getMySQLi()->errno,$this->getMySQLi()->error);
	}
	
	/**
	 * Executes a SELECT, SHOW or DESCRIBE query and returns the first associative result row.
	 * 
	 * @param string $query		The query to be executed.
	 * @param string $type			The query type.
	 * @param string $resultType	The class name of the result type to be used (should extend DBQueryResult). [optional, default='DBQueryResult']
	 * 
	 * @return array
	 * 
	 * @throws Exception
	 */
	private function executeAssoc($query,$type,$resultType='DBQueryResult')
	{
		if(stripos(strtoupper($query),'LIMIT ') === false)
		{
			$query .= ' LIMIT 1';
		}
		$oResult = $this->executeResult($query,$type,$resultType);
		
		return $oResult->fetchAssoc();
	}
	
	/**
	 * Executes a DELETE, INSERT, REPLACE or UPDATE query.
	 * 
	 * @param string $query	The query to be executed.
	 * @param string $type		The query type.
	 * 
	 * @return integer The count of affected rows.
	 * 
	 * @throws Exception
	 */
	protected function execute($query,$type)
	{
		if(!$this->startsWith($query,$type))
		{
			$error = 'The used query is not a '.$type.' query!';
			
			$this->getLogger()->crit($error);
			
			throw new Exception($error);
		}

		if(!$this->getMySQLi()->query($query) || $this->getMySQLi()->errno)
		{	
			$this->getLogger()->crit($this->getMySQLi()->errno.':'.$this->getMySQLi()->error);
			
			throw new Exception($this->getMySQLi()->error, $this->getMySQLi()->errno);
		}
		
		return $this->getMySQLi()->affected_rows;
	}
	
}

?>