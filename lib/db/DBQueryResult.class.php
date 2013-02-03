<?php

require_once 'DBResult.interface.php';

/**
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class DBQueryResult implements DBResult
{
	/**
	 * 
	 * @var mysqli_result
	 */
	private $oMySQLiResult = null;
	
	/**
	 * 
	 * @var integer
	 */
	private $iNumRows = 0;
	
	/**
	 * The error code of the last query.
	 * 
	 * @var integer
	 */
	private $iErrorCode = 0;
	
	/**
	 * The error message of the last query.
	 * 
	 * @var string
	 */
	private $sErrorMessage = '';
	
	/**
	 * Constructor.
	 * 
	 * @param mysqli_result $oMySQLiResult
	 * @param integer $iNumRows
	 * @param integer $iErrorCode
	 * @param string $sErrorMessage
	 * 
	 * @return void
	 */
	public function __construct(mysqli_result $oMySQLiResult=null,$iNumRows=-1,$iErrorCode=0,$sErrorMessage='')
	{
		$this->
			setErrorCode($iErrorCode)->
			setErrorMessage($sErrorMessage)->
			setMySQLiResult($oMySQLiResult)->
			setNumRows($iNumRows);
	}
	
	public function __destruct()
	{
		$this->freeResult();
	}
	
	public function freeResult()
	{
		$this->getMySQLiResult()->free();
	}
	
	/**
	 * 
	 * @return array
	 */
	public function fetchAssoc()
	{
		return $this->getMySQLiResult()->fetch_assoc();
	}
	
	/**
	 * 
	 * @return array
	 */
	public function fetchRow()
	{
		return $this->getMySQLiResult()->fetch_row();
	}
	
	/**
	 * 
	 * @param string $sClassName
	 * @param array	 $aParams
	 * 
	 * @return object
	 */
	public function fetchObject($sClassName=null,$aParams=null)
	{
		return $this->getMySQLiResult()->fetch_object($sClassName,$aParams);
	}
	
	
	/**
	 * 
	 * @return integer
	 */
	public function getNumRows()
	{
		return $this->iNumRows;
	}
	
	/**
	 * 
	 * @return integer
	 */
	public function getRowCount()
	{
		return $this->getMySQLiResult()->num_rows;
	}
	
	/**
	 * 
	 * @param integer $iAllRowsCount
	 * 
	 * @return DBResult
	 */
	protected function setNumRows($iNumRows)
	{
		if((int)$iNumRows < 0)
		{
			$this->iNumRows = $this->getRowCount();
		}
		else
		{
			$this->iNumRows = (int)$iNumRows;
		}
		return $this;
	}
		
	/**
	 * 
	 * @return mysqli_result
	 */
	protected function getMySQLiResult()
	{
		return $this->oMySQLiResult;
	}
	
	/**
	 * 
	 * @param mysqli_result $oMySQLiResult
	 * 
	 * @return DBResult
	 */
	protected function setMySQLiResult(mysqli_result $oMySQLiResult=null)
	{
		if(is_null($oMySQLiResult) || !($oMySQLiResult instanceof mysqli_result))
		{
			throw new Exception('The given result object is not valid!');
		}
		$this->oMySQLiResult = $oMySQLiResult;
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DBResult::getErrorCode()
	 */
	public function getErrorCode()
	{
		return $this->iErrorCode;
	}
	
	/**
	 * Sets the error code of the last query.
	 * 
	 * @param integer $iErrorCode
	 * 
	 * @return DBQueryResult
	 */
	private function setErrorCode($iErrorCode)
	{
		$this->iErrorCode = (int)$iErrorCode;
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DBResult::getErrorMessage()
	 */
	public function getErrorMessage()
	{
		return $this->sErrorMessage;
	}
	
	/**
	 * Sets the error message of the last query.
	 * 
	 * @param string $sErrorMessage
	 * 
	 * @return DBQueryResult
	 */
	private function setErrorMessage($sErrorMessage)
	{
		$this->sErrorMessage = $sErrorMessage;
		return $this;
	}
	
}

?>