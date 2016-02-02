<?php

require_once ROOT . 'includes/FillObjectFromArray.php';
require_once ROOT . 'includes/RequestContainer.class.php';

/**
 * Class RequestContainer_SetPriceSets
 */
class RequestContainer_SetPriceSets extends RequestContainer
{
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
