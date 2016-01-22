<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * Class SoapCall_GetSalesOrderReferrer
 */
class SoapCall_GetSalesOrderReferrer extends PlentySoapCall
{
	/**
	 * @var array
	 */
	private $processedSalesOrderReferrer;

	/**
	 * @return SoapCall_GetSalesOrderReferrer
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);

		$this->processedSalesOrderReferrer = array();
	}

	/**
	 * overrides PlentySoapCall's execute() method
	 *
	 * @return void
	 */
	public function execute()
	{
		try
		{
			/*
			 * do soap call
			 */
			$response = $this->getPlentySoap()->GetSalesOrderReferrer();

			if ($response->Success == true)
			{
				$this->responseInterpretation($response);
				$this->storeToDB();
			} else
			{
				$this->getLogger()->debug(__FUNCTION__ . ' Request Error');
			}

		} catch (Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}

	/**
	 * @param PlentySoapResponse_GetSalesOrderReferrer $response
	 *
	 * @return void
	 */
	private function responseInterpretation(PlentySoapResponse_GetSalesOrderReferrer $response)
	{
		if (is_array($response->SalesOrderReferrers->item))
		{
			foreach ($response->SalesOrderReferrers->item as $oPlentySoapObject_GetSalesOrderReferrer)
			{
				$this->processSalesOrderReferrer($oPlentySoapObject_GetSalesOrderReferrer);
			}
		} else
		{
			$this->processSalesOrderReferrer($response->SalesOrderReferrers->item);
		}
	}

	/**
	 * @param PlentySoapObject_GetSalesOrderReferrer $salesOrderReferrer
	 *
	 * @return void
	 */
	private function processSalesOrderReferrer($salesOrderReferrer)
	{
		// prepare SalesOrderReferrer for persistent storage
		$this->processedSalesOrderReferrer[] = array(
			'Name'                 => $salesOrderReferrer->Name,
			'PriceColumn'          => $salesOrderReferrer->PriceColumn,
			'SalesOrderReferrerID' => $salesOrderReferrer->SalesOrderReferrerID
		);
	}

	/**
	 * @return void
	 */
	private function storeToDB()
	{
		$countSalesOrderReferrer = count($this->processedSalesOrderReferrer);

		if ($countSalesOrderReferrer > 0)
		{
			$this->debug(__FUNCTION__ . " storing $countSalesOrderReferrer SalesOrderReferrer to db");
			// truncate table to prevent old leftovers
			DBQuery::getInstance()->begin();
			DBQuery::getInstance()->truncate('TRUNCATE TABLE `SalesOrderReferrer`');
			DBQuery::getInstance()->insert('INSERT INTO `SalesOrderReferrer`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->processedSalesOrderReferrer));
			DBQuery::getInstance()->commit();
		}
	}

}
