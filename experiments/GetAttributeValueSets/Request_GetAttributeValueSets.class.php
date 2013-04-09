<?php


class Request_GetAttributeValueSets 
{

	public function getRequest($oAttributeValueSetID, $oLang = null)
	{
		$oPlentySoapRequest_GetAttributeValueSets = new PlentySoapRequest_GetAttributeValueSets();
		
		$oPlentySoapRequest_GetAttributeValueSets->AttributeValueSets = new ArrayOfPlentysoaprequestobject_getattributevaluesets();
		
		$oPlentySoapRequest_GetAttributeValueSets->AttributeValueSets->item = new PlentySoapRequestObject_GetAttributeValueSets();
		
		$oPlentySoapRequest_GetAttributeValueSets->AttributeValueSets->item->AttributeValueSetID = $oAttributeValueSetID;
		 
		$oPlentySoapRequest_GetAttributeValueSets->AttributeValueSets->item->Lang = $oLang;
			
		return $oPlentySoapRequest_GetAttributeValueSets;
	}
	
}

?>