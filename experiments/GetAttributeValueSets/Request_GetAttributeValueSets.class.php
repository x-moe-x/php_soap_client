<?php


class Request_GetAttributeValueSets 
{

	public function getRequest($oAttributeValueSetIDs, $oLang = null)
	{
		
		$oPlentySoapRequest_GetAttributeValueSets = new PlentySoapRequest_GetAttributeValueSets();
		$oPlentySoapRequest_GetAttributeValueSets->AttributeValueSets = new ArrayOfPlentysoaprequestobject_getattributevaluesets();
		
		if (isset($oAttributeValueSetIDs) && is_array($oAttributeValueSetIDs))
		{
			$oPlentySoapRequest_GetAttributeValueSets->AttributeValueSets->item = array();
			
			foreach ($oAttributeValueSetIDs as $currentID)
			{
				$currentItem = new PlentySoapRequestObject_GetAttributeValueSets();
				$currentItem->AttributeValueSetID	= $currentID;
				$currentItem->Lang 					= $oLang;
				array_push($oPlentySoapRequest_GetAttributeValueSets->AttributeValueSets->item, $currentItem);				
			} 
		}
		else if (isset($oAttributeValueSetIDs))
		{
			$oPlentySoapRequest_GetAttributeValueSets->AttributeValueSets->item = new PlentySoapRequestObject_GetAttributeValueSets();
			
			$oPlentySoapRequest_GetAttributeValueSets->AttributeValueSets->item->AttributeValueSetID = $oAttributeValueSetIDs;
			 
			$oPlentySoapRequest_GetAttributeValueSets->AttributeValueSets->item->Lang = $oLang;
		}
		else
		{
			// TODO error			
		}
		
		return $oPlentySoapRequest_GetAttributeValueSets;
		
		
		
			
	}
	
}

?>