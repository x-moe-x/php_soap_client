<?php
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/DBLastUpdate.php';
require_once ROOT . 'includes/EanGenerator.class.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * @author x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class JansenStockMatchForUpdate {

	/**
	 * @var string
	 */
	private $identifier4Logger;

	/**
	 * @return JansenStockMatchForUpdate
	 */
	public function __construct() {
		$this -> identifier4Logger = __CLASS__;
	}

	/**
	 * @return void
	 */
	public function execute() {
	}

	private function storeToDB() {
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
