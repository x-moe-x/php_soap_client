<?php

require_once ROOT . 'includes/FillObjectFromArray.php';

class Request_SetPriceSets {

	/**
	 * @var array
	 */
	private $aPriceSets;

	/**
	 * @return Request_SetPriceSets
	 */
	public function __construct() {
		$this -> aPriceSets = array();
	}

	/**
	 * @param array $aPriceSet
	 * @return void
	 */
	public function addPriceSet(array $aPriceSet) {
		if (count($this -> aPriceSets) < SoapCall_SetPriceSets::MAX_PRICE_SETS_PER_PAGE) {
			$this -> aPriceSets[] = $aPriceSet;
		}
	}

	/**
	 * @return boolean
	 */
	public function isFull() {
		return count($this -> aPriceSets) === SoapCall_SetPriceSets::MAX_PRICE_SETS_PER_PAGE;
	}

	/**
	 * @return PlentySoapRequest_SetPriceSets
	 */
	public function getRequest() {
		$oPlentySoapRequest_SetPriceSets = new PlentySoapRequest_SetPriceSets();
		$oPlentySoapRequest_SetPriceSets -> PriceSetList = array();

		foreach ($this->aPriceSets as &$aPriceSet) {/* @var $aPriceSet array */

			$oPlentySoapObject_SetPriceSets = new PlentySoapObject_SetPriceSets();

			fillObjectFromArray($oPlentySoapObject_SetPriceSets, $aPriceSet);

			$oPlentySoapRequest_SetPriceSets -> PriceSetList[] = $oPlentySoapObject_SetPriceSets;
		}

		return $oPlentySoapRequest_SetPriceSets;
	}

}
?>