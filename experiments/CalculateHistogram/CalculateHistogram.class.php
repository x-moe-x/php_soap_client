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

		$startTimestamp = 1366186219; 	//	17.04.2013, 10:10:19
		$endTimestamp = 1366399240;		//	19.04.2013, 21:20:40

		// retreive latest orders from db
		$query = 'SELECT * FROM `OrderHead` WHERE (`OrderTimestamp` >= '.$startTimestamp.' AND `OrderTimestamp` <= '.$endTimestamp. ') AND (`OrderStatus` < 8 OR `OrderStatus` >= 9) AND OrderType = "order" ORDER BY `OrderTimestamp`';

		$orderResult = DBQuery::getInstance()->select($query);

		$countA210 = 0;

		for ($orderRowNr = 0; $orderRowNr < $orderResult->getNumRows(); ++$orderRowNr )
		{
			$currentOrder = $orderResult->fetchAssoc();

			// should print list of 166 orders
			//$this->getLogger()->debug(__FUNCTION__.' : OrderID: '. $currentOrder['OrderID'] );

			// retreive latest items from db

			$query = 'SELECT * FROM `OrderItem` WHERE `OrderID` = '. $currentOrder['OrderID'];

			$itemResult = DBQuery::getInstance()->select($query);
			for ($itemRowNr = 0; $itemRowNr < $itemResult->getNumRows(); ++$itemRowNr)
			{
				$currentItem = $itemResult->fetchAssoc();

				//$this->getLogger()->debug(__FUNCTION__.' : ItemID: '. $currentItem['ItemID'] );

				if (intval($currentItem['ItemID']) == 210)
				{
					$countA210 += intval($currentItem['Quantity']);
					$this->getLogger()->debug(__FUNCTION__.' : Quantity: '. $currentItem['Quantity'].' OrderID: '.$currentItem['OrderID'] );
				}
			}
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