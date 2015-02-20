<?php

require_once ROOT . 'includes/FillObjectFromArray.php';
require_once ROOT . 'includes/RequestContainer.class.php';

class RequestContainer_SetCurrentStocks extends RequestContainer
{

	/**
	 * @return RequestContainer_SetCurrentStocks
	 */
	public function __construct()
	{
		parent::__construct(SoapCall_SetCurrentStocks::MAX_STOCK_RECORDS_PER_PAGE);
	}

	/**
	 * returns the assembled request
	 *
	 * @return PlentySoapRequest_SetCurrentStocks
	 */
	public function getRequest()
	{
		$oPlentySoapRequest_SetCurrentStocks = new PlentySoapRequest_SetCurrentStocks();
		$oPlentySoapRequest_SetCurrentStocks->CurrentStocks = array();

		foreach ($this->items as &$aStock)
		{
			$oPlentySoapObject_SetCurrentStocks = new PlentySoapObject_SetCurrentStocks();

			fillObjectFromArray($oPlentySoapObject_SetCurrentStocks, $aStock);

			$oPlentySoapRequest_SetCurrentStocks->CurrentStocks[] = $oPlentySoapObject_SetCurrentStocks;
		}

		return $oPlentySoapRequest_SetCurrentStocks;
	}
}
