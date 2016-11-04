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
		$result = new PlentySoapRequest_SetPriceSets();
		$result->PriceSetList = new ArrayOfPlentysoapobject_setpricesets();
		$result->PriceSetList->item = array();

		foreach ($this->items as $priceSet)
		{
			$oPlentySoapObject_SetPriceSets = new PlentySoapObject_SetPriceSets();

			fillObjectFromArray($oPlentySoapObject_SetPriceSets, $priceSet);

			$result->PriceSetList->item[] = $oPlentySoapObject_SetPriceSets;
		}
		return $result;
	}
}