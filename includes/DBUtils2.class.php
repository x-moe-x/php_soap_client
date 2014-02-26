<?php

require_once '../lib/db/DBUtils.class.php';

class DBUtils2 {

	/**
	 * buildOnDuplicateKeyUpdateAll - returns sql string for sql on duplicate key update statement  `key_1`=VALUES('key_1'),..,`key_n`=VALUES('val_n')
	 * @param array $arr assoc array
	 */
	public static function buildOnDuplicateKeyUpdateAll(array $array) {
		$out = ' ';
		if ($array) {
			$out = ' ';
			foreach (array_keys($array) as $key) {
				$out .= '`' . DBUtils::quoteSmart($key) . '`=VALUES(`' . DBUtils::quoteSmart($key) . '`),';
			}
		}
		return substr($out, 0, -1) . ' ';
	}

}
?>