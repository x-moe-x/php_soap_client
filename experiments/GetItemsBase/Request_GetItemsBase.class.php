<?php


class Request_GetItemsBase 
{

	public function getRequest()
	{
		$oPlentySoapRequest_GetItemsBase = new PlentySoapRequest_GetItemsBase();;
		
		return $oPlentySoapRequest_GetItemsBase;			
	}
	
}

?>