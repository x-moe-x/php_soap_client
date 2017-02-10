<?php
require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';

class SoapCall_GetAttributeValueSets extends PlentySoapCall {

	public function execute() {
		try {
			$request = new PlentySoapRequest_GetAttributeValueSets();
			$request->AttributeValueSets = new ArrayOfPlentysoaprequestobject_getattributevaluesets();
			$request->AttributeValueSets->item = array();

			for ($i = 36; $i < 36 + 50; $i++) {
				$requestObject = new PlentySoapRequestObject_GetAttributeValueSets();
				$requestObject->AttributeValueSetID = $i;

				$request->AttributeValueSets->item[] = $requestObject;
			}

			$response = $this->getPlentySoap()
				->GetAttributeValueSets($request);

			print_r($response);

		} catch (Exception $e) {
			$this->onExceptionAction($e);
		}
	}
}