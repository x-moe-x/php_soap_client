<?php

require_once ROOT . 'includes/FillObjectFromArray.php';
require_once ROOT . 'includes/RequestContainer.class.php';

class RequestContainer_SetPriceSets extends RequestContainer
{
	/**
	 * @return RequestContainer_SetPriceSets
	 */
	public function __construct()
	{
		parent::__construct(SoapCall_SetPriceSets::MAX_PRICE_SETS_PER_PAGE);
	}

	/**
	 * returns the assembled request
	 *
	 * @return PlentySoapRequest_SetPriceSets
	 */
	public function getRequest()
	{
		$oPlentySoapRequest_SetPriceSets = new PlentySoapRequest_SetPriceSets();
		$oPlentySoapRequest_SetPriceSets->PriceSetList = array();

		foreach ($this->items as &$aPriceSet)
		{
			/* @var $aPriceSet array */

			$oPlentySoapObject_SetPriceSets = new PlentySoapObject_SetPriceSets();

			fillObjectFromArray($oPlentySoapObject_SetPriceSets, $aPriceSet);

			$oPlentySoapRequest_SetPriceSets->PriceSetList[] = $oPlentySoapObject_SetPriceSets;
		}

		return $oPlentySoapRequest_SetPriceSets;
	}
}
