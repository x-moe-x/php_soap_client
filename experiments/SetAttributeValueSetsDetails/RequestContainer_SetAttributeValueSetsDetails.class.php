<?php
require_once ROOT . 'includes/FillObjectFromArray.php';
require_once ROOT . 'includes/RequestContainer.class.php';
require_once ROOT . 'includes/SKUHelper.php';

class RequestContainer_SetAttributeValueSetsDetails extends RequestContainer {

	/**
	 * returns the assembled request
	 * @return mixed
	 */
	public function getRequest() {
		$request = new PlentySoapRequest_SetAttributeValueSetsDetails();
		$request->AttributeValueSetsDetails = new ArrayOfPlentysoapobject_setattributevaluesetsdetails();
		$request->AttributeValueSetsDetails->item = array();

		foreach ($this->items as $attributeValueSetDetail) {
			$requestObject = new PlentySoapObject_SetAttributeValueSetsDetails();


			fillObjectFromArray($requestObject, array(
				'SKU'           => Values2SKU($attributeValueSetDetail['ItemID'], $attributeValueSetDetail['AttributeValueSetID'], $attributeValueSetDetail['PriceID']),
				'Variantnumber' => $attributeValueSetDetail['Variantnumber'],
				'Availability'  => $attributeValueSetDetail['Availability'],
				'PurchasePrice' => $attributeValueSetDetail['PurchasePrice'],
				'EAN1'          => $attributeValueSetDetail['EAN1'],
				'EAN2'          => $attributeValueSetDetail['EAN2'],
				'EAN3'          => $attributeValueSetDetail['EAN3'],
				'EAN4'          => $attributeValueSetDetail['EAN4'],
				'ASIN'          => $attributeValueSetDetail['ASIN'],
				'Oversale'      => $attributeValueSetDetail['Oversale'],
				'UVP'           => $attributeValueSetDetail['UVP'],
				'MaxStock'      => $attributeValueSetDetail['MaxStock'],
				'StockBuffer'   => $attributeValueSetDetail['StockBuffer'],
			));

			$request->AttributeValueSetsDetails->item[] = $requestObject;
		}

		return $request;
	}
}