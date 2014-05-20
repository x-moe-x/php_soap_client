<?php
require_once ROOT . 'lib/db/DBQuery.class.php';

/**
 * @author x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class CalculateAmazonWeightenedRunningCosts {

	/**
	 * @var string
	 */
	private $identifier4Logger;

	private $startDate;

	/**
	 * @return CalculateAmazonWeightenedRunningCosts
	 */
	public function __construct() {
		$this->identifier4Logger = __CLASS__;

		$now = new DateTime();

		$this -> startDate = new DateTime($now -> format('Y-m-01'));
	}

	/**
	 * @return void
	 */
	public function execute() {
		// get total netto for amazon per warehouse/date

		// get total netto for all referrers

		// compute warehouse/date individual weights

		// apply weights and calculate cumulative percentage value
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
