<?php

class Request_SetItemsTexts {
	
	
	public function getRequest()
	{
		$oPlentySoapRequest_SetItemsTexts	=	new PlentySoapRequest_SetItemsTexts();
		
		$oPlentySoapRequest_SetItemsTexts->ItemsList[]		=	$this->getItem_1();
		$oPlentySoapRequest_SetItemsTexts->ItemsList[]		=	$this->getItem_2();
		$oPlentySoapRequest_SetItemsTexts->ItemsList[]		=	$this->getItem_3();
		
		return $oPlentySoapRequest_SetItemsTexts;
	}
	
	private function getItem_1()
	{
		$random_1		=	rand(1000, 9999);
		
		$oPlentySoapObject_SetItemsTexts								=	new PlentySoapObject_SetItemsTexts();
		
		$oPlentySoapObject_SetItemsTexts->ItemID						=	1;
		$oPlentySoapObject_SetItemsTexts->DeleteAllExistingEntries		=	false;
		$oPlentySoapObject_SetItemsTexts->Lang							=	"de";
		$oPlentySoapObject_SetItemsTexts->MetaDescription				=	"Metadescription ItemID1";
		$oPlentySoapObject_SetItemsTexts->Name							=	"Name ItemID 1 / ".$random_1;
		$oPlentySoapObject_SetItemsTexts->Name2							=	"Name 2 ItemID 1 / ".$random_1;
		$oPlentySoapObject_SetItemsTexts->Name3							=	"Name 3 ItemID 1 / ".$random_1;
		$oPlentySoapObject_SetItemsTexts->ShortDescription				=	"ShortDescription ItemID 1 / ".$random_1;
		$oPlentySoapObject_SetItemsTexts->LongDescription				=	"LongDescription ItemID 1 / ".$random_1;
		$oPlentySoapObject_SetItemsTexts->TechnicalData					=	"LongDescription ItemID 1 / ".$random_1;
		
		return $oPlentySoapObject_SetItemsTexts;
	}
	
	private function getItem_2()
	{
		$random_2		=	rand(1000, 9999);
		
		$oPlentySoapObject_SetItemsTexts								=	new PlentySoapObject_SetItemsTexts();
		
		$oPlentySoapObject_SetItemsTexts->ItemID						=	1;
		$oPlentySoapObject_SetItemsTexts->DeleteAllExistingEntries		=	true;
		$oPlentySoapObject_SetItemsTexts->Lang							=	"de";
		$oPlentySoapObject_SetItemsTexts->MetaDescription				=	"Metadescription ItemID1";
		$oPlentySoapObject_SetItemsTexts->Name							=	"Name ItemID 1 / ".$random_2;
		$oPlentySoapObject_SetItemsTexts->Name2							=	"Name 2 ItemID 1 / ".$random_2;
		$oPlentySoapObject_SetItemsTexts->Name3							=	"Name 3 ItemID 1 / ".$random_2;
		$oPlentySoapObject_SetItemsTexts->ShortDescription				=	"ShortDescription ItemID 1 / ".$random_2;
		$oPlentySoapObject_SetItemsTexts->LongDescription				=	"LongDescription ItemID 1 / ".$random_2;
		$oPlentySoapObject_SetItemsTexts->TechnicalData					=	"LongDescription ItemID 1 / ".$random_2;
		
		return $oPlentySoapObject_SetItemsTexts;
	}
	
	private function getItem_3()
	{
		$random_3		=	rand(1000, 9999);
		
		$oPlentySoapObject_SetItemsTexts								=	new PlentySoapObject_SetItemsTexts();
		
		$oPlentySoapObject_SetItemsTexts->ItemID						=	1;
		$oPlentySoapObject_SetItemsTexts->DeleteAllExistingEntries		=	false;
		$oPlentySoapObject_SetItemsTexts->Lang							=	"de";
		$oPlentySoapObject_SetItemsTexts->MetaDescription				=	"Metadescription ItemID1";
		$oPlentySoapObject_SetItemsTexts->Name							=	"Name ItemID 1 / ".$random_3;
		$oPlentySoapObject_SetItemsTexts->Name2							=	null;
		$oPlentySoapObject_SetItemsTexts->Name3							=	"Name 3 ItemID 1 / ".$random_3;
		$oPlentySoapObject_SetItemsTexts->ShortDescription				=	"ShortDescription ItemID 1 / ".$random_3;
		$oPlentySoapObject_SetItemsTexts->LongDescription				=	null;
		$oPlentySoapObject_SetItemsTexts->TechnicalData					=	null;
		
		return $oPlentySoapObject_SetItemsTexts;
	}
}

?>