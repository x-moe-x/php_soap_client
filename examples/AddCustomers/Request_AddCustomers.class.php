<?php

class Request_AddCustomers {

	public function getRequest()
	{
		$oPlentySoapRequest_AddCustomers	=	new PlentySoapRequest_AddCustomers();

		$oPlentySoapObject_Customer							=	new PlentySoapObject_Customer();
		$oPlentySoapObject_Customer->AdditionalName			=	"Maximilian";
		$oPlentySoapObject_Customer->ArchiveNumber			=	null;
		$oPlentySoapObject_Customer->City					=	"Musterstadt";
		$oPlentySoapObject_Customer->Company				=	null;
		$oPlentySoapObject_Customer->ContactPerson			=	null;
		$oPlentySoapObject_Customer->CustomerClass			=	0;
		$oPlentySoapObject_Customer->CustomerID				= 	null;
		$oPlentySoapObject_Customer->CountryID				=	1;
		$oPlentySoapObject_Customer->CustomerNumber			=	"SOAP_".rand(1000, 9999);
		$oPlentySoapObject_Customer->CustomerRating			=	null;
		$oPlentySoapObject_Customer->CustomerSince 	       	=	time();
		$oPlentySoapObject_Customer->DateOfBirth			=	1021542154;
		$oPlentySoapObject_Customer->DebitorAccount         =	null;
		$oPlentySoapObject_Customer->EbayName               =	"mmustermann";
		$oPlentySoapObject_Customer->ExternalCustomerID		=	"External SOAP_".rand(1000, 9999);
		$oPlentySoapObject_Customer->Fax					=	"0123456789";
		$oPlentySoapObject_Customer->FirstName				=	"Max";
		$oPlentySoapObject_Customer->FormOfAddress			=	0;
		$oPlentySoapObject_Customer->HouseNo				=	123;
		$oPlentySoapObject_Customer->Language				=	"de";
		$oPlentySoapObject_Customer->Mobile					=	"0177/74110815";
		$oPlentySoapObject_Customer->Street					=	"Musterstrasse";
		$oPlentySoapObject_Customer->ResponsibleID			=	0;
		$oPlentySoapObject_Customer->Surname				=	"Mustermann";
		$oPlentySoapObject_Customer->Telephone				=	"74110815";
		$oPlentySoapObject_Customer->ZIP					=	"12345";
		$oPlentySoapObject_Customer->Email					=	"Max@Mustermann.de";

		$oPlentySoapRequest_AddCustomers->Customers[]		=	$oPlentySoapObject_Customer;

		$oPlentySoapObject_Customer2							=	new PlentySoapObject_Customer();
		$oPlentySoapObject_Customer2->AdditionalName			=	"Maximilian";
		$oPlentySoapObject_Customer2->ArchiveNumber			=	null;
		$oPlentySoapObject_Customer2->City					=	"Musterstadt";
		$oPlentySoapObject_Customer2->Company				=	null;
		$oPlentySoapObject_Customer2->ContactPerson			=	null;
		$oPlentySoapObject_Customer2->CustomerClass			=	0;
		$oPlentySoapObject_Customer2->CustomerID				= 	null;
		$oPlentySoapObject_Customer2->CountryID				=	1;
		$oPlentySoapObject_Customer2->CustomerNumber			=	"SOAP_".rand(1000, 9999);
		$oPlentySoapObject_Customer2->CustomerRating			=	null;
		$oPlentySoapObject_Customer2->CustomerSince 	       	=	time();
		$oPlentySoapObject_Customer2->DateOfBirth			=	1021542154;
		$oPlentySoapObject_Customer2->DebitorAccount         =	null;
		$oPlentySoapObject_Customer2->EbayName               =	"mmustermann";
		$oPlentySoapObject_Customer2->ExternalCustomerID		=	"External SOAP_".rand(1000, 9999);
		$oPlentySoapObject_Customer2->Fax					=	"0123456789";
		$oPlentySoapObject_Customer2->FirstName				=	"Max";
		$oPlentySoapObject_Customer2->FormOfAddress			=	0;
		$oPlentySoapObject_Customer2->HouseNo				=	123;
		$oPlentySoapObject_Customer2->Language				=	"de";
		$oPlentySoapObject_Customer2->Mobile					=	"0177/74110815";
		$oPlentySoapObject_Customer2->Street					=	"Musterstrasse";
		$oPlentySoapObject_Customer2->ResponsibleID			=	0;
		$oPlentySoapObject_Customer2->Surname				=	"Mustermann";
		$oPlentySoapObject_Customer2->Telephone				=	"74110815";
		$oPlentySoapObject_Customer2->ZIP					=	"12345";
		$oPlentySoapObject_Customer2->Email					=	"Maximilian@Mustermann.de";;

		$oPlentySoapRequest_AddCustomers->Customers[]		=	$oPlentySoapObject_Customer2;

		return $oPlentySoapRequest_AddCustomers;
	}

}

?>