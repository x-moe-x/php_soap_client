<?php

require_once ROOT . 'includes/FillObjectFromArray.php';
require_once ROOT . 'includes/RequestContainer.class.php';

/**
 * Class RequestContainer_SetItemsPurchasePrice
 */
class RequestContainer_SetItemsPurchasePrice extends RequestContainer {

	/**
	 * returns the assembled request
	 * @return PlentySoapRequest_SetItemsPurchasePrice
	 */
	public function getRequest() {
		$result = new PlentySoapRequest_SetItemsPurchasePrice();
		$result->ItemsPurchasePrice = new ArrayOfPlentysoapobject_setitemspurchaseprice();
		$result->ItemsPurchasePrice->item = array();

		foreach ($this->items as $current) {
			$purchasePriceUpdate = new PlentySoapObject_SetItemsPurchasePrice();

			if (array_key_exists('SKU', $current)) {
				$sku = $current['SKU'];
			} elseif (array_key_exists('ItemID', $current)) {
				$priceId = 0;
				$avsId = 0;
				if (array_key_exists('PriceID', $current)) {
					$priceId = $current['PriceID'];
				}
				if (array_key_exists('AttributeValueSetID', $current)) {
					$avsId = $current['AttributeValueSetID'];
				}
				$sku = Values2SKU($current['ItemID'], $avsId, $priceId);
			}
			$purchasePriceUpdate->SKU = $sku;
			$purchasePriceUpdate->PurchasePrice = $current['PurchasePrice'];

			$result->ItemsPurchasePrice->item[] = $purchasePriceUpdate
		}

		return $result;
	}
}
