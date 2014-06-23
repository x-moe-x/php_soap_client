<?php

/**
 * @author x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class CalculateAmazonQuantities {

	/**
	 * @var string
	 */
	private $identifier4Logger;

	/**
	 * @return CalculateAmazonQuantities
	 */
	public function __construct() {
		$this -> identifier4Logger = __CLASS__;
	}

	/**
	 * @return void
	 */
	public function execute() {
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
