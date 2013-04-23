<?php

require_once ROOT.'lib/db/DBQuery.class.php';
require_once ROOT.'lib/db/DBQueryResult.class.php';


/**
 * 1. retrteive data
 *
 * 2. stuff in histogram
 *
 * 3. show results
 *
 * @author x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class CalculateHistogram
{
	/**
	 *
	 * @var string
	 */
	private $identifier4Logger = '';

	public function __construct()
	{
		$this->identifier4Logger = __CLASS__;
	}

	public function execute()
	{
		$this->getLogger()->debug(__FUNCTION__.' : CalculateHistogram' );

		$startTimestamp = 1366153200;	//	17.04.2013
		$endTimestamp = 1366326000;		//	19.04.2013

		// retreive latest orders from db
		$query = 'SELECT * FROM `OrderHead` WHERE `OrderTimestamp` > '.$startTimestamp.' AND `OrderTimestamp` <= '.$endTimestamp;

		$result = DBQuery::getInstance()->select($query);

		for ($rowNr = 0; $rowNr < $result->getNumRows(); ++$rowNr )
		{
			$currentOrder = $result->fetchAssoc();
			$this->getLogger()->debug(__FUNCTION__.' : OrderID: '. $currentOrder['id''] );
		}
	}

	/**
	 *
	 * @return Logger
	 */
	protected function getLogger()
	{
		return Logger::instance($this->identifier4Logger);
	}
}


?>