<?php

require_once 'DBQuery.class.php';

/**
 * Some simple tools to quickly create a query
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class DBUtils
{
	/**
	 * The global DBQuery instance to be used.
	 * 
	 * @var DBQuery
	 */
	private static $DBQuery = null;
	
	/**
	 * Set the global DBQuery instance to be used.
	 * 
	 * @param DBQuery $DBQuery
	 */
	public static function setQueryInstance(DBQuery $DBQuery)
	{
		self::$DBQuery = $DBQuery;
	}
	
	/**
	 * buildInsert - returns sql string of (key_1,..,key_n) VALUES ('val_1',..,'val_n')
	 * @param array $aInsert assoc array
	 * @param array $aUnquoteKeys (optional) do not quote these keys
	 */
	public static function buildInsert(array $aInsert, $aUnquoteKeys=array())
	{
		$f = $v = '';

		if (is_array($aInsert))
		{
			foreach (array_keys($aInsert) as $key)
			{
				$f .= '`'.self::quoteSmart($key).'`,';
				
				if (in_array($key, $aUnquoteKeys) || 
					strtoupper($aInsert[$key]) == 'NULL' ||
					strtoupper($aInsert[$key]) == 'NOW()')
				{
					$v .= $aInsert[$key].',';
				}
				elseif(is_numeric($aInsert[$key]) && (is_int($aInsert[$key]) || is_float($aInsert[$key])))
				{
					$v .= $aInsert[$key].',';
				}
				else
				{
					$v .= '"'.self::quoteSmart($aInsert[$key]).'",';
				}
			}
		}
		return ' ('.substr($f,0,-1).') VALUES ('.substr($v,0,-1).')';
	}
	
	/**
	 * buildPreparedInsert - returns sql string of (key_1,..,key_n) VALUES ('val_1',..,'val_n')
	 * @param array $array assoc array
	 */
	public static function buildPreparedInsert(array $array)
	{
		$f = $sValues = '';

		if($array) 
		{
			foreach (array_keys($array) as $key) 
			{
				$f .= '`'.self::quoteSmart($key).'`,';
				if (strtoupper($array[$key])=='NULL'||
				    strtoupper($array[$key])=='NOW()') 
				{
					$sValues .= $array[$key].',';
				} 
				else 
				{
					$sValues .= '?,';
				}
			}
		}
		return ' ('.substr($f,0,-1).') VALUES ('.substr($sValues,0,-1).')';
	}
	
	/**
	 * $arr = array(	1=> array('id=>1', 'name'=>'Daniel'),
	 * 					2=> array('id=>2', 'name'=>'Sarah')
	 * 				)
	 * 
	 * will be transformed to
	 * 
	 *  (id, name) VALUES
	 *		  	("1", "Daniel"),
	 *	  		("2", "Sarah")
	 * 
	 * 
	 * 
	 * >> "INSERT INTO tablename" not included
	 */
	public static function buildMultipleInsert(array $array, $aUnquoteKeys = array()){
		
		$strRet = ' VALUES'.chr(10);
		$iCounter = 0;

		if($array) 
		{	
			foreach($array as $iKey=>$aValue)
			{	
				$iCounter++;
				$f = $v = '';
				
				foreach (array_keys($aValue) as $key) 
				{
					$f .= '`'.self::quoteSmart($key).'`,';
					if (in_array($key, $aUnquoteKeys)||
					    strtoupper($aValue[$key])=='NULL'||
					    strtoupper($aValue[$key])=='NOW()') 
					{
						$v .= $aValue[$key] . ',';
					}
					elseif( !isset($aValue[$key]) )
					{
						$v .= 'NULL,';
					}
					else 
					{
						$v .= '"'.self::quoteSmart($aValue[$key]).'",';
					}
				}
				
				$strRet .= ' ('.substr($v,0,-1).')'.($iCounter!=sizeof($array)?', ':'') . chr(10);
				
			}
			
		}
		return ' ('.substr($f,0,-1).') '.$strRet;
	}
	
	/**
	 * buildUpdate - returns sql string for sql update statement  SET `key_1`='val_1',..,`key_n`='val_n'
	 * 
	 * @param array $arr assoc array
	 * @param array $aUnquoteKeys (optional) do not quote these keys
	 */
	public static function buildUpdate(array $aUpdate, $aUnquoteKeys=array(), $return_set=true)
	{
		$out = ' ';
		if($aUpdate)
		{
			if($return_set)
			{
				$out=' SET ';
			}
			foreach(array_keys($aUpdate) as $key)
			{
				$out .= '`'.self::quoteSmart($key).'`=';
														
				if(in_array($key, $aUnquoteKeys) || strtoupper($aUpdate[$key])=='NULL'||strtoupper($aUpdate[$key])=='NOW()')
				{
					$out .= $aUpdate[$key] . ',';
				} 
				else 
				{
					$out .= '"'.self::quoteSmart($aUpdate[$key]).'",';
				}
			}
		}
		return substr($out, 0,-1).' ';
	}
	
	/**
	 * buildPreparedUpdate - returns sql string for sql update statement  SET `key_1`=?,..,`key_n`=?
	 * 
	 * @param array $arr assoc array
	 * @param boolean $returnSet (optional)
	 */
	public static function buildPreparedUpdate(array $array, $returnSet=true) 
    {
	    $out = ' ';
		if($array) 
		{
			if($returnSet)
			{
				$out=' SET ';
			}
			foreach (array_keys($array) as $key) 
			{
				$out .= '`'.self::quoteSmart($key).'`=';
				if( strtoupper($array[$key])=='NULL'||
				    strtoupper($array[$key])=='NOW()'
				) 
				{
					$out .= $array[$key].',';
				} 
				else 
				{
					$out .= '?,'; 
				}
			}
		}
		return substr($out, 0,-1).' ';
	}
	
	/**
	 * buildWhere -- returns sql string for sql where clause WHERE `key_1`='val_1' && .. && `key_n`='val_n'
	 *
	 * @param array $arr assoc array
	 * @return string
	 */
	public static function buildWhere(array $array,$sTablePrefix='', $bReturnWhere=true)
	{
		$out = '  ';
		if($array)
		{
			if($bReturnWhere)
			{
				$out = ' WHERE ';
			}
		    foreach (array_keys($array) as $key) 
		    {		
			    $out .= ' '.$sTablePrefix.'`'.self::quoteSmart($key).'`=';
			    if( $array[$key]=='NULL'||
				    $array[$key]=='NOW()'
				)
                {
					$out .= $array[$key].' &&';
                }
                elseif( is_numeric($array[$key]) && (is_int($array[$key]) || is_float($array[$key])))
                {
                	$out .= $array[$key].' &&';
                }
                else
                {
					$out .= '"'.self::quoteSmart($array[$key]).'" &&';
				}
			}
        }
		return substr($out, 0,-2);
	}
	
	/**
	 * buildOnDuplicateKeyUpdate - returns sql string for sql on duplicate key update statement  `key_1`='val_1',..,`key_n`='val_n'
	 * @param array $arr assoc array
	 * @param array $aUnquoteKeys (optional) do not quote these keys
	 */
	public static function buildOnDuplicateKeyUpdate(array $array, $aUnquoteKeys=array()) 
	{
	    $out = ' ';
		if ($array) 
		{
		    $out=' ';
			foreach (array_keys($array) as $key) 
			{
				$out .= '`'.self::quoteSmart($key).'`=';
				if (in_array($key, $aUnquoteKeys)||
				    strtoupper($array[$key])=='NULL'||
				    strtoupper($array[$key])=='NOW()') 
				{
					$out .= $array[$key] . ',';
				} 
				else 
				{
					$out .= '"'.self::quoteSmart($array[$key]).'",';
				}
			}
		}
		return substr($out, 0,-1).' ';
	}
	
	/**
	 * 
	 * 
	 * @param mixed $mValue
	 * @param boolean $bDontStripSlashes
	 * 
	 * @return mixed
	 */
	public static function quoteSmart($mValue)
	{
		if(is_object($mValue) || is_array($mValue))
		{
			return self::quoteSmartObject($mValue);
		}
		
		if ($mValue == '""')
		{
			return "";
		}

		/*
		 * Quote if not numeric
		 */
	    if(!is_numeric($mValue))
	    {
	    	if(!(self::$DBQuery instanceof DBQuery))
	    	{
	    		self::$DBQuery = DBQuery::getInstance();
	    	}
			$mValue = self::$DBQuery->escapeString($mValue);
	    }
	    return $mValue;
	}
	
	/**
	 * @param mixed		$oObject
	 * @param boolean	$bDontStripSlashes
	 * 
	 * @return mixed
	 */
	public static function quoteSmartObject($oObject)
	{
	    if(!is_object($oObject))
	    {
	        if(is_array($oObject))
	        {
	            foreach($oObject as $sKey => $mixed)
				{
					$oObject[$sKey] = self::quoteSmartObject($mixed);
				}
				
				return $oObject;
	        }
	        else
	        {
	            return self::quoteSmart($oObject);
	        }
	    }
	    
	    $aClassProperties = get_class_vars(get_class($oObject));
		
		if($aClassProperties)
		{
			foreach($aClassProperties as $strPropertyName => $nothing)
			{
				if(strlen($strPropertyName) && $oObject->$strPropertyName)
				{
					if(is_object($oObject->$strPropertyName))
					{
						$oObject->$strPropertyName = self::quoteSmartObject($oObject->$strPropertyName);
					}
					elseif(is_array($oObject->$strPropertyName))
					{
						foreach($oObject->$strPropertyName as $sKey => $mixed)
						{
							$oObject->$strPropertyName[$sKey] = self::quoteSmartObject($mixed);
						}
					}
					else
					{
						$oObject->$strPropertyName = self::quoteSmart($oObject->$strPropertyName);
					}
				}
			}
		}
		
	    return $oObject;
	}

}

?>