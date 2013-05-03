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

		$startTimestamp = 1364767200; 	//	01.04.2013, 00:00:00
		$endTimestamp = 1367359199;		//	30.04.2013, 23:59:59

		// retreive latest orders from db
		$query = 'SELECT OrderItem.*, OrderHead.OrderTimestamp  FROM OrderItem LEFT JOIN (OrderHead) ON (OrderHead.OrderID = OrderItem.OrderID) WHERE OrderItem.ItemID = 210 AND OrderHead.OrderTimestamp >= '.$startTimestamp.' AND OrderHead.OrderTimestamp <= '.$endTimestamp.' AND (OrderHead.OrderStatus < 8 OR OrderHead.OrderStatus >= 9) ORDER BY OrderItem.OrderID';

		$itemResult = DBQuery::getInstance()->select($query);

		$countA210 = 0;

		for ($itemRowNr = 0; $itemRowNr < $itemResult->getNumRows(); ++$itemRowNr)
		{
			$currentItem = $itemResult->fetchAssoc();

			$countA210 += intval($currentItem['Quantity']);
			$this->getLogger()->debug(__FUNCTION__.' : Quantity: '. $currentItem['Quantity'].' OrderID: '.$currentItem['OrderID'] );
		}
		$this->getLogger()->debug(__FUNCTION__.' : Total a210 found: '. $countA210 );
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