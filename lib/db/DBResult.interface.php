<?php

/**
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
interface DBResult
{
	
	/**
	 * Fetch a result row as an associative array.
	 * Returns NULL if there are no more rows in resultset.
	 * 
	 * @return array
	 */
	public function fetchAssoc();
	
	/**
	 * Get a result row as an enumerated array.
	 * 
	 * @return array
	 */
	public function fetchRow();
	
	/**
	 * Returns the current row of a result set as an object
	 * 
	 * @param string $sClassName	The name of the class to instantiate, set the properties of and return.
	 * 								If not specified, a stdClass object is returned. [optional, default=null]
	 * @param array	 $aParams		An optional array of parameters to pass to the constructor for sClassName objects. [optional, default=null]
	 * 
	 * @return object
	 */
	public function fetchObject($sClassName=null,$aParams=null);
	
	/**
	 * 
	 * @return integer
	 */
	public function getNumRows();
	
	/**
	 * Frees the memory associated with a result
	 * 
	 * @return void
	 */
	public function freeResult();
	
	/**
	 * Returns the mysqli->errno error code for the current query.
	 * 
	 * @return integer
	 */
	public function getErrorCode();
	
	/**
	 * Returns the mysqli->error error message for the current query.
	 * 
	 * @return string
	 */
	public function getErrorMessage();
}

?>