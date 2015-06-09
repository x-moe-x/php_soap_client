<?php
require_once ROOT . 'lib/db/DBUtils.class.php';

/**
 * Class DBUtils2
 */
class DBUtils2
{

	/**
	 * $arr = array(    1=> array('id=>1', 'name'=>'Daniel'),
	 *                    2=> array('id=>2', 'name'=>'Sarah')
	 *                )
	 *
	 * will be transformed to
	 *
	 *  (id, name) VALUES
	 *            ("1", "Daniel"),
	 *            ("2", "Sarah") ON DUPLICATE KEYS UPDATE `id`=VALUES(`id`),`name`=VALUES(`name`),
	 *
	 *
	 *
	 * >> "INSERT INTO tablename" not included
	 *
	 * @param array $array
	 * @param array $aUnquoteKeys
	 *
	 * @return string
	 */
	public static function buildMultipleInsertOnDuplikateKeyUpdate(array $array, $aUnquoteKeys = array())
	{

		$strRet = ' VALUES' . chr(10);
		$strUpdate = ' ON DUPLICATE KEY UPDATE ';
		$iCounter = 0;

		$aKeys = null;

		if ($array)
		{
			foreach ($array as $iKey => $aValue)
			{
				$iCounter++;
				$f = $v = '';

				if (!isset($aKeys))
				{
					$aKeys = array_keys($aValue);
					$strUpdate .= DBUtils2::buildOnDuplicateKeyUpdateAll($aValue);
				}

				foreach ($aKeys as $key)
				{
					$f .= '`' . DBUtils::quoteSmart($key) . '`,';
					if (in_array($key, $aUnquoteKeys) || strtoupper($aValue[$key]) == 'NULL' || strtoupper($aValue[$key]) == 'NOW()')
					{
						$v .= $aValue[$key] . ',';
					} elseif (!isset($aValue[$key]))
					{
						$v .= 'NULL,';
					} else
					{
						$v .= '"' . DBUtils::quoteSmart($aValue[$key]) . '",';
					}
				}

				$strRet .= ' (' . substr($v, 0, -1) . ')' . ($iCounter != sizeof($array) ? ', ' : '') . chr(10);

			}

		}

		return ' (' . substr($f, 0, -1) . ') ' . $strRet . $strUpdate;
	}

	/**
	 * buildOnDuplicateKeyUpdateAll - returns sql string for sql on duplicate key update statement
	 * `key_1`=VALUES('key_1'),..,`key_n`=VALUES('val_n')
	 *
	 * @param array $array associative array
	 *
	 * @return string
	 */
	public static function buildOnDuplicateKeyUpdateAll(array $array)
	{
		$out = ' ';
		if ($array)
		{
			$out = ' ';
			foreach (array_keys($array) as $key)
			{
				$out .= '`' . DBUtils::quoteSmart($key) . '`=VALUES(`' . DBUtils::quoteSmart($key) . '`),';
			}
		}

		return substr($out, 0, -1) . ' ';
	}
}
