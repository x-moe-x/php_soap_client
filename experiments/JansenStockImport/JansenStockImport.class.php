<?php
require_once ROOT . 'lib/db/DBQuery.class.php';

/**
 * @author x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class JansenStockImport {

	/**
	 * @var string
	 */
	private $identifier4Logger;

	/**
	 * @return JansenStockImport
	 */
	public function __construct() {
		$this -> identifier4Logger = __CLASS__;

		//TODO truncate db
	}

	/**
	 * @return void
	 */
	public function execute() {

		// if file modifikation date younger than last import ...

		// ... then read the file. for every line ...

		// ... ... eliminate dummy fields ...
		// ... ... check ean
		// ... ... if everything is ok ...
		// ... ... ... then store record
		// ... ... ... otherwise display error

		// ... then persistenly store all records in db

	}

	/**
	 *
	 * @return Logger
	 */
	protected function getLogger() {
		return Logger::instance($this -> identifier4Logger);
	}

}
?>
